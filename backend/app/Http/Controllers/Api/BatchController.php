<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentType;
use App\Enums\LetterStatus;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Letter;
use App\Models\LetterBatch;
use App\Services\AccessScope;
use App\Services\LetterService;
use App\Services\OfficerImportService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ZipArchive;

class BatchController extends Controller
{
    public function __construct(private LetterService $letters, private PdfService $pdfs, private OfficerImportService $imports) {}

    private function authorizeBatch(Request $request, ?LetterBatch $batch = null): void
    {
        abort_if($request->user()->isOfficer(), 403);
        if ($batch) {
            abort_unless(AccessScope::batches(LetterBatch::query(), $request->user())->whereKey($batch->id)->exists(), 404);
        }
    }

    private function authorizeLetter(Request $request, Letter $letter, bool $draft = false): void
    {
        $this->authorizeBatch($request, $letter->letterBatch);
        if ($draft) {
            abort_if($letter->status === LetterStatus::Final, 409, 'Finalized letters cannot be changed.');
        }
    }

    private function validateBatch(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150', 'document_type' => ['required', Rule::enum(DocumentType::class)],
            'my_ref_no' => 'nullable|string|max:100', 'cabinet_app_no' => 'nullable|string|max:100',
            'cabinet_app_date' => 'nullable|date', 'exam_date' => 'nullable|date', 'probation_effective_date' => 'nullable|date',
            'training_complete_date' => 'nullable|date', 'letter_date' => 'required|date', 'content_template' => 'nullable|string|max:100000',
            'district_id' => 'nullable|exists:districts,id',
            'ds_division_id' => ['nullable', Rule::exists('ds_divisions', 'id')->where('district_id', $request->input('district_id'))],
        ]);
    }

    public function index(Request $request)
    {
        $this->authorizeBatch($request);

        return response()->json(AccessScope::batches(LetterBatch::query(), $request->user())->withCount('letters')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $this->authorizeBatch($request);
        $batch = LetterBatch::create($this->validateBatch($request) + ['created_by' => $request->user()->id, 'status' => 'draft']);

        return response()->json($batch, 201);
    }

    public function show(Request $request, LetterBatch $batch)
    {
        $this->authorizeBatch($request, $batch);

        return response()->json($batch->load('letters.officer'));
    }

    public function update(Request $request, LetterBatch $batch)
    {
        $this->authorizeBatch($request, $batch);
        abort_if($batch->letters()->exists(), 409, 'Batch settings cannot change after letters have been generated.');
        $batch->update($this->validateBatch($request));

        return response()->json($batch);
    }

    public function destroy(Request $request, LetterBatch $batch)
    {
        $this->authorizeBatch($request, $batch);
        abort_if($batch->letters()->where('status', 'final')->exists(), 409, 'A batch with finalized letters cannot be deleted.');
        $batch->delete();

        return response()->json(['message' => 'Batch deleted.']);
    }

    public function import(Request $request, LetterBatch $batch)
    {
        $this->authorizeBatch($request, $batch);
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:4096']);
        $path = $request->file('file')->store('imports', 'local');
        try {
            $result = DB::transaction(function () use ($request, $batch, $path) {
                $result = $this->imports->import($path, $batch->ds_division_id, 'local', $request->user());
                foreach ($result['officers']->unique('id') as $officer) {
                    AccessScope::officer($request->user(), $officer->id);
                    $this->letters->generateForOfficer($batch, $officer, $request->user());
                }

                return $result;
            });
        } finally {
            Storage::disk('local')->delete($path);
        }

        return response()->json(['message' => $result['officers']->unique('id')->count().' officers imported. Draft letters generated.', 'skipped' => $result['skipped']]);
    }

    public function generate(Request $request, LetterBatch $batch)
    {
        $this->authorizeBatch($request, $batch);
        $data = $request->validate(['officer_ids' => 'required|array|min:1|max:500', 'officer_ids.*' => 'required|integer|distinct|exists:officers,id']);
        DB::transaction(function () use ($request, $batch, $data) {
            foreach ($data['officer_ids'] as $id) {
                $officer = AccessScope::officer($request->user(), $id);
                $this->letters->generateForOfficer($batch, $officer, $request->user());
            }
        });

        return response()->json(['message' => 'Draft letters generated.']);
    }

    public function letter(Request $request, Letter $letter)
    {
        $this->authorizeLetter($request, $letter);

        return response()->json($letter->load('officer', 'letterBatch', 'signatory', 'controllingOfficer'));
    }

    public function updateLetter(Request $request, Letter $letter)
    {
        $this->authorizeLetter($request, $letter, true);
        $letter->update($request->validate([
            'ref_no' => 'required|string|max:100', 'subject' => 'required|string|max:255', 'body' => 'nullable|string|max:100000',
            'cc_to' => 'nullable|array|max:50', 'cc_to.*' => 'string|max:500',
            'signatory_id' => ['nullable', Rule::exists('signatories', 'id')->where('is_active', true)],
            'controlling_officer_id' => ['nullable', Rule::exists('signatories', 'id')->where('is_active', true)],
        ]));

        return response()->json($letter);
    }

    public function deleteLetter(Request $request, Letter $letter)
    {
        $this->authorizeLetter($request, $letter, true);
        $letter->delete();

        return response()->json(['message' => 'Draft deleted.']);
    }

    public function finalize(Request $request, Letter $letter)
    {
        $this->authorizeLetter($request, $letter);
        DB::transaction(function () use ($request, $letter) {
            $letter = Letter::lockForUpdate()->findOrFail($letter->id);
            if ($letter->status === LetterStatus::Final) {
                return;
            }
            $letter->update(['pdf_path' => $this->pdfs->store($letter), 'status' => LetterStatus::Final]);
            $this->letters->applyStateChanges($letter->letterBatch, $letter->officer, $letter, $request->user());
            Document::create([
                'officer_id' => $letter->officer_id, 'document_type' => $letter->letterBatch->document_type,
                'ref_no' => $letter->ref_no, 'issue_date' => $letter->letter_date ?? now(),
                'file_path' => $letter->pdf_path, 'generated_by' => $request->user()->id,
            ]);
        });

        return response()->json(['message' => 'Letter finalized and archived.']);
    }

    public function pdf(Request $request, Letter $letter)
    {
        $this->authorizeLetter($request, $letter);
        $bytes = $letter->status === LetterStatus::Final && $letter->pdf_path
            ? Storage::disk('local')->get($letter->pdf_path) : $this->pdfs->render($letter);

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="letter-'.$letter->id.'.pdf"']);
    }

    public function downloadBatch(Request $request, LetterBatch $batch)
    {
        $this->authorizeBatch($request, $batch);
        $letters = $batch->letters()->where('status', 'final')->get();
        abort_if($letters->isEmpty(), 422, 'Finalize at least one letter before downloading.');
        $path = tempnam(sys_get_temp_dir(), 'gn-zip-');
        $zip = new ZipArchive;
        abort_unless($zip->open($path, ZipArchive::OVERWRITE) === true, 500, 'Unable to create archive.');
        try {
            foreach ($letters as $letter) {
                $zip->addFromString('letter-'.$letter->id.'.pdf', Storage::disk('local')->get($letter->pdf_path));
            }
            $zip->close();
        } catch (\Throwable $error) {
            $zip->close();
            @unlink($path);
            throw $error;
        }

        return response()->download($path, 'batch-'.$batch->id.'.zip')->deleteFileAfterSend(true);
    }
}
