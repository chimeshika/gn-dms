<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterBatch;
use App\Models\Officer;
use App\Enums\LetterStatus;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class LetterController extends Controller
{
    /**
     * Display a listing of the letter batches.
     */
    public function index(): View
    {
        $batches = LetterBatch::withCount('letters')
            ->latest()
            ->paginate(15);

        return view('letters.index', compact('batches'));
    }

    /**
     * Display the specified letter batch details and its generated letters.
     */
    public function show(LetterBatch $batch): View
    {
        $letters = $batch->letters()
            ->with(['officer', 'signatory'])
            ->get();

        $sampleLetter = $letters->first();

        return view('letters.show', compact('batch', 'letters', 'sampleLetter'));
    }

    /**
     * Preview single letter PDF in browser (New Tab).
     */
    public function previewPdf(Letter $letter)
    {
        $letter->load(['officer', 'signatory', 'letterBatch']);

        $data = $this->prepareLetterData($letter);

        $pdf = Pdf::loadView('pdf.templates.appointment', $data)
            ->setPaper('a4', 'portrait');

        $safeFileName = 'preview_' . str_replace(['/', '\\'], '_', $letter->ref_no) . '.pdf';

        return $pdf->stream($safeFileName);
    }

    /**
     * Download or direct print single letter PDF.
     */
    public function pdf(Letter $letter)
    {
        $letter->load(['officer', 'signatory', 'letterBatch']);

        $data = $this->prepareLetterData($letter);

        $pdf = Pdf::loadView('pdf.templates.appointment', $data)
            ->setPaper('a4', 'portrait');

        $safeFileName = 'letter_' . str_replace(['/', '\\'], '_', $letter->ref_no) . '.pdf';

        return $pdf->download($safeFileName);
    }

    /**
     * Delete a letter permanently from the database.
     */
    public function destroy(Letter $letter): RedirectResponse
    {
        $batchId = $letter->letter_batch_id;

        if (method_exists($letter, 'forceDelete')) {
            $letter->forceDelete();
        } else {
            $letter->delete();
        }

        return redirect()->route('letters.show', $batchId)
            ->with('status', 'ලිපිය ඩේටාබේස් එකෙන්ම සාර්ථකව ඉවත් කරන ලදී (Permanently Deleted).');
    }

    /**
     * Bulk export letters as a ZIP file containing PDFs.
     */
    public function bulkPdf(LetterBatch $batch)
    {
        $letters = $batch->letters()->with(['officer', 'signatory'])->get();

        if ($letters->isEmpty()) {
            return back()->with('error', 'මෙම Batch එකෙහි ලිපි නොමැත.');
        }

        $zipFileName = 'letters_batch_' . $batch->id . '_' . time() . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($letters as $letter) {
                $data = $this->prepareLetterData($letter);
                $pdf = Pdf::loadView('pdf.templates.appointment', $data)->setPaper('a4', 'portrait');
                
                $fileName = 'Letter_' . str_replace(['/', '\\'], '_', $letter->ref_no) . '.pdf';
                $zip->addFromString($fileName, $pdf->output());
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Generate letters for selected officers in the batch.
     */
    public function generate(Request $request, LetterBatch $batch): RedirectResponse
    {
        $officerIds = $request->input('officer_ids', []);

        foreach ($officerIds as $officerId) {
            $letter = Letter::firstOrNew([
                'letter_batch_id' => $batch->id,
                'officer_id' => $officerId,
            ]);

            if (!$letter->exists) {
                $officer = Officer::find($officerId);
                $letter->ref_no = $batch->my_ref_no . '/' . ($officer->nic_no ?? rand(1000, 9999));
                $letter->status = LetterStatus::Draft ?? 'draft';
                $letter->save();
            }
        }

        return redirect()->route('letters.show', $batch)
            ->with('status', 'සියලුම ලිපි සාර්ථකව Generate කරන ලදී.');
    }

    /**
     * Import officers logic helper / placeholder.
     */
    public function import(Request $request, LetterBatch $batch): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        return redirect()->route('letters.show', $batch)
            ->with('status', 'නිලධාරීන්ගේ ලැයිස්තුව සාර්ථකව Import කරන ලදී.');
    }

    /**
     * Helper to prepare array data for PDF view mapping.
     */
    private function prepareLetterData(Letter $letter): array
    {
        $officer = $letter->officer;
        $batch = $letter->letterBatch;
        $signatory = $letter->signatory;

        return [
            'refNo' => $letter->ref_no ?? $batch?->my_ref_no,
            'letterDate' => now()->format('Y-m-d'),
            'officerName' => $officer?->full_name_si ?? $officer?->full_name_en,
            'officerSalutation' => ($officer?->gender === 'Female' || $officer?->gender === 'Mrs') ? 'මහත්මිය' : 'මහතා',
            'nicNo' => $officer?->nic_no,
            'addressLines' => array_filter([
                $officer?->address_line1,
                $officer?->address_line2,
                $officer?->address_line3,
            ]),
            'dsDivisionSi' => $officer?->ds_division ?? $officer?->dsDivision?->name_si,
            'gnDivision' => $officer?->gn_division ?? $officer?->gnDivision?->name_si,
            'examDate' => $batch?->exam_date?->format('Y.m.d'),
            'probationEffectiveDate' => $batch?->probation_effective_date?->format('Y.m.d'),
            'cabinetAppNo' => $batch?->cabinet_app_no,
            'cabinetAppDate' => $batch?->cabinet_app_date?->format('Y.m.d'),
            'signatoryName' => $signatory?->officer_name ?? $signatory?->name_si,
            'signatoryDesignation' => $signatory?->designation ?? $signatory?->designation_si,
            'signatureDataUri' => $signatory?->signature_path ? Storage::url($signatory->signature_path) : null,
            'ccLines' => $batch?->cc_lines ?? [],
            'font' => 'Iskoola Pota',
        ];
    }
}