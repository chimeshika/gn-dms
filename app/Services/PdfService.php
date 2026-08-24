<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\Medium;
use App\Models\Letter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfService
{
    public function __construct(
        protected LetterService $letterService,
    ) {
    }

    public function download(Letter $letter): mixed
    {
        $pdf = $this->build($letter->load('officer', 'letterBatch', 'signatory', 'controllingOfficer'));

        return $pdf->download($this->filenameFor($letter));
    }

    public function store(Letter $letter, string $disk = 'local'): string
    {
        $path = 'letters/' . $this->filenameFor($letter);
        Storage::disk($disk)->put($path, $this->render($letter));

        return $path;
    }

    public function render(Letter $letter): string
    {
        return $this->build($letter->load('officer', 'letterBatch', 'signatory', 'controllingOfficer'))->output();
    }

    protected function build(Letter $letter): mixed
    {
        $view = $this->templateFor($letter);

        if ($view === 'pdf.letter') {
            $font = $this->fontFamilyFor($letter);
            $html = Pdf::loadView('pdf.letter', compact('letter', 'font'));
        } else {
            $data = $this->letterService->viewData($letter);
            $html = Pdf::loadView($view, $data);
        }

        return $html->setPaper('a4', 'portrait');
    }

    protected function templateFor(Letter $letter): string
    {
        $type = $letter->letterBatch?->document_type;

        return match ($type) {
            DocumentType::Appointment, DocumentType::Certificate => 'pdf.templates.appointment',
            DocumentType::Promotion => 'pdf.templates.promotion',
            DocumentType::PromotionGrade2 => 'pdf.templates.promotion_grade_2',
            DocumentType::Confirmation => 'pdf.templates.appointment',
            DocumentType::Transfer => 'pdf.templates.transfer',
            DocumentType::Retirement => 'pdf.templates.retirement',
            default => 'pdf.letter',
        };
    }

    public function fontFamilyFor(Letter $letter): string
    {
        return match ($letter->officer?->medium) {
            Medium::Tamil => 'Noto Sans Tamil',
            Medium::Sinhala => 'Abhaya Libre',
            default => 'Abhaya Libre',
        };
    }

    protected function filenameFor(Letter $letter): string
    {
        $ref = Str::slug($letter->ref_no ?: 'letter', '_');

        return Str::slug($letter->officer?->display_name ?? 'officer', '_') . '_' . $ref . '.pdf';
    }
}
