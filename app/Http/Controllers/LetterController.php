<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\LetterStatus;
use App\Enums\SignatoryCategory;
use App\Models\Letter;
use App\Models\LetterBatch;
use App\Models\Officer;
use App\Services\LetterService;
use App\Services\OfficerImportService;
use App\Services\PdfService;
use App\Services\SignatoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class LetterController extends Controller
{
    public function __construct(
        protected LetterService $letterService,
        protected PdfService $pdfService,
        protected OfficerImportService $importService,
        protected SignatoryService $signatories,
    ) {
    }

    public function index(): View
    {
        return view('letters.index', [
            'batches' => LetterBatch::withCount('letters')->with('creator')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('letters.create', [
            'documentTypes' => DocumentType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:150'],
            'document_type'            => ['nullable', 'in:' . implode(',', DocumentType::values())],
            'my_ref_no'                => ['nullable', 'string', 'max:100'],
            'cabinet_app_no'           => ['nullable', 'string', 'max:100'],
            'cabinet_app_date'         => ['nullable', 'date'],
            'exam_date'                => ['nullable', 'date'],
            'probation_effective_date' => ['nullable', 'date'],
            'training_complete_date'   => ['nullable', 'date'],
            'letter_date'              => ['nullable', 'date'],
            'content_template'         => ['nullable', 'string'],
            'ds_division_id'           => ['nullable', 'exists:ds_divisions,id'],
            'district_id'              => ['nullable', 'exists:districts,id'],
        ]);

        $batch = LetterBatch::create([
            'name'                     => $data['name'],
            'document_type'            => $data['document_type'] ?? DocumentType::Appointment->value,
            'my_ref_no'                => $data['my_ref_no'] ?? null,
            'cabinet_app_no'           => $data['cabinet_app_no'] ?? null,
            'cabinet_app_date'         => $data['cabinet_app_date'] ?? null,
            'exam_date'                => $data['exam_date'] ?? null,
            'probation_effective_date' => $data['probation_effective_date'] ?? null,
            'training_complete_date'   => $data['training_complete_date'] ?? null,
            'letter_date'              => $data['letter_date'] ?? null,
            'content_template'         => $data['content_template'] ?? null,
            'ds_division_id'           => $data['ds_division_id'] ?? null,
            'district_id'              => $data['district_id'] ?? null,
            'status'                   => LetterStatus::Draft->value,
            'created_by'               => auth()->id(),
        ]);

        return redirect()->route('filament.admin.resources.letter-batches.index')
            ->with('success', "Batch \"{$batch->name}\" created successfully.");
    }

    public function show(LetterBatch $batch): View
    {
        $batch->load(['letters.officer', 'letters.signatory', 'letters.controllingOfficer', 'creator']);

        $sampleLetter = $batch->letters()->with(['officer', 'signatory', 'controllingOfficer'])->first();

        return view('letters.show', [
            'batch'        => $batch,
            'letters'      => $batch->letters,
            'sampleLetter' => $sampleLetter,
        ]);
    }

    public function import(Request $request, LetterBatch $batch): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:4096'],
        ]);

        $path = $request->file('file')->store('imports', 'local');

        $result = $this->importService->import(
            $path,
            $batch->ds_division_id,
            'local'
        );

        $count = $result['officers']->count();
        $skipped = count($result['skipped']);

        if ($count > 0) {
            DB::transaction(function () use ($batch, $result) {
                foreach ($result['officers'] as $officer) {
                    $this->letterService->generateForOfficer($batch, $officer, auth()->user());
                }
            });

            $msg = "Imported {$count} officer(s) and generated draft letters successfully.";
            if ($skipped > 0) {
                $msg .= " Skipped {$skipped} invalid row(s).";
            }

            return redirect()->route('letters.show', $batch)
                ->with('status', $msg);
        }

        return redirect()->route('letters.show', $batch)
            ->with('error', 'No officers imported. Ensure your file has required columns: nic_no, full_name_en.');
    }

    public function generate(Request $request, LetterBatch $batch): RedirectResponse
    {
        $request->validate([
            'officer_ids'   => ['required', 'array', 'min:1'],
            'officer_ids.*' => ['integer', 'exists:officers,id'],
        ]);

        $officers = Officer::whereIn('id', $request->input('officer_ids'))->get();

        foreach ($officers as $officer) {
            $this->letterService->generateForOfficer($batch, $officer, auth()->user());
        }

        return redirect()->route('letters.show', $batch)
            ->with('status', 'Letters generated for ' . $officers->count() . ' officer(s).');
    }

    public function editLetter(Letter $letter): View
    {
        $letter->load(['officer', 'signatory', 'controllingOfficer', 'letterBatch']);

        return view('letters.edit', [
            'letter'             => $letter,
            'secretaryOptions'   => $this->signatories->optionsFor(SignatoryCategory::Secretary),
            'controllingOptions' => $this->signatories->optionsFor(SignatoryCategory::ControllingOfficer),
        ]);
    }

    public function updateLetter(Request $request, Letter $letter): RedirectResponse
    {
        $data = $request->validate([
            'ref_no'                 => ['nullable', 'string', 'max:100'],
            'subject'                => ['nullable', 'string', 'max:255'],
            'body'                   => ['nullable', 'string'],
            'cc_to'                  => ['nullable', 'array'],
            'signatory_id'           => ['nullable', 'integer', 'exists:signatories,id'],
            'controlling_officer_id' => ['nullable', 'integer', 'exists:signatories,id'],
        ]);

        $letter->update([
            'ref_no'                 => $data['ref_no'] ?? $letter->ref_no,
            'subject'                => $data['subject'] ?? $letter->subject,
            'body'                   => $data['body'] ?? null,
            'cc_to'                  => $data['cc_to'] ?? $letter->cc_to,
            'signatory_id'           => $data['signatory_id'] ?? $letter->signatory_id,
            'controlling_officer_id' => $data['controlling_officer_id'] ?? $letter->controlling_officer_id,
        ]);

        return back()->with('status', 'Letter draft saved.');
    }

    /**
     * Delete a draft letter from the batch.
     */
    public function destroy(Letter $letter): RedirectResponse
    {
        $batch = $letter->letterBatch;
        $letter->delete();

        return redirect()->route('letters.show', $batch)
            ->with('status', 'Letter deleted successfully.');
    }

    /**
     * Finalize: apply state changes on the officer, log service history, archive PDF.
     */
    public function finalize(Letter $letter): RedirectResponse
    {
        if ($letter->status === LetterStatus::Final) {
            return back()->with('status', 'Letter is already final.');
        }

        $batch = $letter->letterBatch;

        DB::transaction(function () use ($letter, $batch) {
            $letter->update([
                'pdf_path' => $this->pdfService->store($letter),
                'status'   => LetterStatus::Final,
            ]);

            $this->letterService->applyStateChanges($batch, $letter->officer, $letter, auth()->user());
        });

        return back()->with('status', 'Letter finalized. Officer record updated and document archived.');
    }

    public function pdf(Letter $letter): Response
    {
        return $this->pdfService->download($letter->load('officer'));
    }

    /**
     * Preview a single letter PDF inline (for sample preview without download).
     */
    public function previewPdf(Letter $letter): Response
    {
        $letter->load(['officer', 'signatory', 'controllingOfficer', 'letterBatch']);

        $html = $this->pdfService->render($letter);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Bulk PDF download as a ZIP archive of all finalized letters in the batch.
     */
    public function bulkPdf(LetterBatch $batch): StreamedResponse
    {
        $letters = $batch->letters()->with('officer')->where('status', LetterStatus::Final)->get();

        return response()->streamDownload(function () use ($letters) {
            $zip = new ZipArchive;
            $tmp = tempnam(sys_get_temp_dir(), 'gn_dms_') . '.zip';

            if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                abort(500, 'Unable to create archive.');
            }

            foreach ($letters as $letter) {
                $zip->addFromString(
                    str_replace(['/', '\\'], '_', $letter->ref_no) . '.pdf',
                    $this->pdfService->render($letter)
                );
            }

            $zip->close();
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'letters-' . $batch->id . '.zip', ['Content-Type' => 'application/zip']);
    }
}
