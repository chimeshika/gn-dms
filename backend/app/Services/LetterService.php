<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\Gender;
use App\Enums\LetterStatus;
use App\Enums\OfficerGrade;
use App\Enums\ServiceStatus;
use App\Enums\SignatoryCategory;
use App\Models\Letter;
use App\Models\LetterBatch;
use App\Models\Officer;
use App\Models\Signatory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LetterService
{
    public function __construct(
        protected SignatoryService $signatoryService
    ) {}

    public function generateForOfficer(LetterBatch $batch, Officer $officer, ?User $user = null): Letter
    {
        $existing = Letter::where('letter_batch_id', $batch->id)->where('officer_id', $officer->id)->first();
        if ($existing) {
            return $existing;
        }
        $dsDivisionId = $officer->current_ds_division_id ?? $batch->ds_division_id;
        $districtId   = $officer->current_district_id ?? $batch->district_id;

        $signatory = $this->signatoryService->findForCategory(
            SignatoryCategory::Secretary,
            $dsDivisionId,
            $districtId
        ) ?? $this->signatoryService->resolveSecretary();

        $controllingOfficer = $this->signatoryService->findForCategory(
            SignatoryCategory::ControllingOfficer,
            $dsDivisionId,
            $districtId
        ) ?? $this->signatoryService->resolveControllingOfficer();

        $ccTo     = $this->signatoryService->buildAutoCc($officer, $batch);
        $template = $batch->content_template ?? $this->getDefaultTemplate($batch);
        $body     = $this->parsePlaceholders($template, $officer, $batch);
        $refNo    = ($batch->my_ref_no ? $batch->my_ref_no . '/' : 'GN/LETTER/') . ($officer->nic_no ?? $officer->id);

        return Letter::updateOrCreate(
            [
                'letter_batch_id' => $batch->id,
                'officer_id'      => $officer->id,
            ],
            [
                'ref_no'                 => $refNo,
                'subject'                => $batch->name ?? $batch->document_type->label(),
                'body'                   => $body,
                'cc_to'                  => $ccTo,
                'signatory_id'           => $signatory?->id,
                'controlling_officer_id' => $controllingOfficer?->id,
                'letter_date'            => $batch->letter_date ?? now(),
                'status'                 => LetterStatus::Draft->value,
                'created_by'             => $user?->id ?? auth()->id(),
            ]
        );
    }

    public function parsePlaceholders(string $template, Officer $officer, LetterBatch $batch): string
    {
        $replacements = $this->buildReplacements($officer, $batch);

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Compile a letter body — resolving all {placeholder} tokens at render time.
     */
    public function compileBody(Letter $letter): string
    {
        $officer = $letter->officer;
        $batch   = $letter->letterBatch;

        if (! $officer || ! $batch) {
            return $letter->body ?? '';
        }

        $officer->loadMissing(['district', 'dsDivision', 'gnDivision']);
        $batch->loadMissing(['dsDivision', 'district']);

        $replacements = $this->buildReplacements($officer, $batch);

        return str_replace(array_keys($replacements), array_values($replacements), $letter->body ?? '');
    }

    /**
     * Build the full replacements map for an officer + batch combination.
     */
    protected function buildReplacements(Officer $officer, LetterBatch $batch): array
    {
        $officer->loadMissing(['district', 'dsDivision', 'gnDivision']);
        $batch->loadMissing(['dsDivision', 'district']);

        $genderVal = is_object($officer->gender) ? ($officer->gender->value ?? (string)$officer->gender) : (string) $officer->gender;
        $mediumVal = is_object($officer->medium) ? ($officer->medium->value ?? (string)$officer->medium) : (string) $officer->medium;

        $gradeVal = $officer->current_grade;
        if (is_object($gradeVal)) {
            $gradeVal = method_exists($gradeVal, 'label') ? $gradeVal->label() : ($gradeVal->value ?? (string) $gradeVal);
        }

        $serviceStatusVal = $officer->service_status;
        if (is_object($serviceStatusVal)) {
            $serviceStatusVal = method_exists($serviceStatusVal, 'label') ? $serviceStatusVal->label() : ($serviceStatusVal->value ?? (string) $serviceStatusVal);
        }

        // Location Fallbacks (Support both Objects/Relations & Plain Strings)
        $districtName = is_object($officer->district) 
            ? ($officer->district->name_en ?? $officer->district->name_si ?? '') 
            : ($officer->district ?? $officer->districtRelation?->name_en ?? $batch->district?->name_en ?? '');

        $dsDivisionName = is_object($officer->ds_division) 
            ? ($officer->ds_division->name_en ?? $officer->ds_division->name_si ?? '') 
            : ($officer->ds_division ?? $officer->dsDivision?->name_en ?? $batch->dsDivision?->name_en ?? '');

        $gnDivisionName = is_object($officer->gn_division) 
            ? ($officer->gn_division->name_en ?? $officer->gn_division->name_si ?? '') 
            : ($officer->gn_division ?? $officer->gnDivision?->name_en ?? '');

        // Address Lines handling
        $address1 = $officer->address_line1 ?? '';
        $address2 = $officer->address_line2 ?? '';
        $address3 = $officer->address_line3 ?? '';

        $address = implode(', ', array_filter([$address1, $address2, $address3]));

        $letterDate = $batch->letter_date
            ? Carbon::parse($batch->letter_date)->format('Y.m.d')
            : now()->format('Y.m.d');

        $replacements = [
            // ── Officer fields ──
            '{full_name_en}'            => $officer->full_name_en ?? '',
            '{nic_no}'                  => $officer->nic_no ?? '',
            '{address_line1}'           => $address1,
            '{address_line2}'           => $address2,
            '{address_line3}'           => $address3,
            '{dob}'                     => $officer->dob ? Carbon::parse($officer->dob)->format('Y.m.d') : '',
            '{gender}'                  => $genderVal ?? '',
            '{medium}'                  => $mediumVal ?? '',
            '{first_appointment_date}'  => $officer->first_appointment_date ? Carbon::parse($officer->first_appointment_date)->format('Y.m.d') : '',
            '{current_grade}'           => $gradeVal ?? '',
            '{grade}'                   => $gradeVal ?? '',
            '{service_status}'          => $serviceStatusVal ?? '',

            // ── Location fields ──
            '{district}'                => $districtName,
            '{ds_division}'             => $dsDivisionName,
            '{gn_division}'             => $gnDivisionName,

            // ── Batch fields ──
            '{my_ref_no}'               => $batch->my_ref_no ?? '',
            '{cabinet_app_no}'          => $batch->cabinet_app_no ?? '',
            '{cabinet_app_date}'        => $batch->cabinet_app_date ? Carbon::parse($batch->cabinet_app_date)->format('Y.m.d') : '',
            '{exam_date}'               => $batch->exam_date ? Carbon::parse($batch->exam_date)->format('Y.m.d') : '',
            '{probation_effective_date}' => $batch->probation_effective_date ? Carbon::parse($batch->probation_effective_date)->format('Y.m.d') : '',
            '{training_complete_date}'  => $batch->training_complete_date ? Carbon::parse($batch->training_complete_date)->format('Y.m.d') : '',
            '{letter_date}'             => $letterDate,

            // ── Aliases ──
            '{officer_name}'            => $officer->full_name_en ?? '',
            '{officer_name_en}'         => $officer->full_name_en ?? '',
            '{name}'                    => $officer->full_name_en ?? '',
            '{address}'                 => $address,
            '{date}'                    => $letterDate,
            '{ref_no}'                  => $batch->my_ref_no ?? '',
            '{designation}'             => $gradeVal ?? '',
        ];

        return array_map(function ($value) {
            if ($value instanceof \BackedEnum) {
                return method_exists($value, 'label') ? $value->label() : (string) $value->value;
            }
            return (string) ($value ?? '');
        }, $replacements);
    }

    public function applyStateChanges(LetterBatch $batch, Officer $officer, Letter $letter, ?User $user = null): void
    {
        $docType = $batch->document_type;
        $oldStatus = $officer->service_status;

        $statusUpdate = match ($docType) {
            DocumentType::Appointment => ServiceStatus::Appointed,
            DocumentType::Confirmation => ServiceStatus::Confirmed,
            DocumentType::Promotion, DocumentType::PromotionGrade2 => ServiceStatus::Promoted,
            DocumentType::Transfer => ServiceStatus::Transferred,
            DocumentType::Retirement => ServiceStatus::Retired,
            DocumentType::Disciplinary => ServiceStatus::Interdicted,
            default => $officer->service_status,
        };

        $gradeUpdate = match ($docType) {
            DocumentType::Promotion => 'grade_i',
            DocumentType::PromotionGrade2 => 'grade_ii',
            default => $officer->current_grade,
        };

        $confirmationStatus = match ($docType) {
            DocumentType::Confirmation => 'confirmed',
            default => $officer->confirmation_status,
        };

        $updateData = ['service_status' => is_object($statusUpdate) ? $statusUpdate->value : $statusUpdate];
        if ($gradeUpdate !== $officer->current_grade) {
            $updateData['current_grade'] = is_object($gradeUpdate) ? $gradeUpdate->value : $gradeUpdate;
        }
        if ($confirmationStatus !== $officer->confirmation_status) {
            $updateData['confirmation_status'] = $confirmationStatus;
        }

        $officer->update($updateData);

        if ($docType === DocumentType::Certificate) {
            return;
        }

        $eventType = $docType->eventType();
        $officer->serviceHistories()->create([
            'event_type'     => $eventType->value,
            'ref_no'         => $letter->ref_no,
            'description'    => $docType->label() . ' (' . ($batch->name ?? '') . ')',
            'effective_date' => $batch->probation_effective_date ?? $batch->letter_date ?? now(),
            'old_value'      => $oldStatus,
            'new_value'      => is_object($statusUpdate) ? $statusUpdate->value : $statusUpdate,
            'created_by'     => $user?->id ?? auth()->id(),
        ]);
    }

    public function viewData(Letter $letter): array
    {
        $letter->loadMissing([
            'officer.district', 'officer.dsDivision', 'officer.gnDivision',
            'signatory', 'controllingOfficer', 'letterBatch',
        ]);

        $officer = $letter->officer;
        $batch   = $letter->letterBatch;

        $officer->loadMissing(['district', 'dsDivision', 'gnDivision']);
        $batch->loadMissing(['dsDivision', 'district']);

        $addressLines = array_filter([
            $officer->address_line1,
            $officer->address_line2,
            $officer->address_line3,
        ]);

        $salutation = match ($officer->gender) {
            Gender::Female => 'Madam',
            default => 'Sir',
        };

        $districtEn = is_object($officer->district) 
            ? ($officer->district->name_en ?? '') 
            : ($officer->district ?? $officer->districtRelation?->name_en ?? $batch->district?->name_en ?? '');

        $dsDivisionEn = is_object($officer->ds_division) 
            ? ($officer->ds_division->name_en ?? '') 
            : ($officer->ds_division ?? $officer->dsDivision?->name_en ?? $batch->dsDivision?->name_en ?? '');

        $gnDivisionEn = is_object($officer->gn_division) 
            ? ($officer->gn_division->name_en ?? '') 
            : ($officer->gn_division ?? $officer->gnDivision?->name_en ?? '');

        $serviceStatus = is_object($officer->service_status)
            ? ($officer->service_status->value ?? (string) $officer->service_status)
            : ($officer->service_status ?? 'appointed');

        $gradeLabel = $officer->current_grade;
        if (is_object($gradeLabel)) {
            $gradeLabel = method_exists($gradeLabel, 'label') ? $gradeLabel->label() : ($gradeLabel->value ?? 'grade_iii');
        }

        return [
            'refNo'               => $letter->ref_no,
            'letterDate'          => $letter->letter_date?->format('Y.m.d') ?? now()->format('Y.m.d'),
            'letterDateFormatted' => $letter->letter_date?->format('d-m-Y') ?? now()->format('d-m-Y'),
            'font'                => $this->resolveFont($officer),

            'officerName'         => $officer->full_name_en ?? '',
            'officerSalutation'   => $salutation,
            'nicNo'               => $officer->nic_no ?? '',
            'currentGrade'        => $gradeLabel ?? 'grade_iii',
            'serviceStatus'       => $serviceStatus,
            'firstAppointmentDate' => $officer->first_appointment_date?->format('Y.m.d') ?? '',
            'dob'                 => $officer->dob?->format('Y.m.d') ?? '',
            'addressLines'        => $addressLines,

            'districtEn'          => $districtEn,
            'dsDivisionEn'        => $dsDivisionEn,
            'dsDivisionSi'        => $officer->dsDivision?->name_si ?: $dsDivisionEn,
            'fromDsDivisionSi'    => $officer->dsDivision?->name_si ?: $dsDivisionEn,
            'toDsDivisionSi'      => $batch->dsDivision?->name_si ?: ($batch->dsDivision?->name_en ?? ''),
            'gnDivision'          => $gnDivisionEn,

            'examDate'                => $batch->exam_date?->format('Y.m.d') ?? '',
            'probationEffectiveDate'  => $batch->probation_effective_date?->format('Y.m.d') ?? '',
            'trainingCompleteDate'    => $batch->training_complete_date?->format('Y.m.d') ?? '',
            'cabinetAppNo'            => $batch->cabinet_app_no ?? '',
            'cabinetAppDate'          => $batch->cabinet_app_date?->format('Y.m.d') ?? '',
            'myRefNo'                 => $batch->my_ref_no ?? '',

            'signatoryName'              => $letter->signatory?->officer_name ?? '',
            'signatoryDesignation'       => $letter->signatory?->designation ?? '',
            'signatoryDesignationFull'   => $this->formatSignatoryFull($letter->signatory),
            'controllingOfficerName'     => $letter->controllingOfficer?->officer_name ?? '',
            'controllingOfficerDesignation' => $letter->controllingOfficer?->designation ?? '',
            'controllingOfficerDesignationFull' => $this->formatSignatoryFull($letter->controllingOfficer),

            'signatureDataUri' => $this->signatureDataUri($letter->signatory),

            'ccLines' => $letter->cc_to ?? [],
            'body'    => $this->compileBody($letter),
        ];
    }

    protected function getDefaultTemplate(LetterBatch $batch): string
    {
        return match ($batch->document_type) {
            DocumentType::Appointment => '<p>{full_name_en}</p><p>{address_line1}</p><p>{address_line2}</p><p>{address_line3}</p><p>NIC No: {nic_no}</p>',
            default => '<p>{full_name_en}</p><p>NIC: {nic_no}</p><p>{district} / {ds_division}</p>',
        };
    }

    public function hasCustomBody(Letter $letter): bool
    {
        return filled($letter->letterBatch->content_template)
            || $letter->body !== $this->parsePlaceholders($this->getDefaultTemplate($letter->letterBatch), $letter->officer, $letter->letterBatch);
    }

    protected function resolveFont(Officer $officer): string
    {
        return 'Abhaya Libre';
    }

    protected function formatSignatoryFull(?Signatory $signatory): string
    {
        if (!$signatory) {
            return '';
        }

        $parts = array_filter([
            $signatory->officer_name,
            $signatory->designation,
        ]);

        return implode(' - ', $parts) ?: '';
    }

    protected function signatureDataUri(?Signatory $signatory): ?string
    {
        if (!$signatory?->digital_signature_path) {
            return null;
        }

        $path = $signatory->digital_signature_path;

        if (Storage::disk('public')->exists($path)) {
            $raw = Storage::disk('public')->get($path);

            return 'data:image/png;base64,' . base64_encode($raw);
        }

        return null;
    }
}
