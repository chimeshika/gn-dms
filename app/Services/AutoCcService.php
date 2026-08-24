<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\SignatoryCategory;
use App\Models\District;
use App\Models\DsDivision;
use App\Models\LetterBatch;
use App\Models\Officer;
use App\Models\Signatory;

/**
 * Builds the automatic CC (Copies To) recipients for an official letter.
 *
 * Rules (Home Affairs Ministry correspondence):
 *   - Appointments / Promotions / Confirmations:
 *       01. දිස්ත්‍රික් ලේකම්, {officer_district}
 *       02. ප්‍රාදේශීය ලේකම්, {officer_ds_division}
 *   - Transfers (from = officer's current station, to = batch target):
 *       01. දිස්ත්‍රික් ලේකම්, {from_district}
 *       02. දිස්ත්‍රික් ලේකම්, {to_district}
 *       03. ප්‍රාදේශීය ලේකම්, {from_ds_division}
 *       04. ප්‍රාදේශීය ලේකම්, {to_ds_division}
 *   - Retirements additionally copy the Director General of Pensions and the
 *     Auditor General (විශ්‍රාම වැටුප් අධ්‍යක්ෂ ජනරාල් / විගණකාධිපති).
 */
class AutoCcService
{
    protected const DESIGNATION_DISTRICT_SECRETARY = 'දිස්ත්‍රික් ලේකම්';
    protected const DESIGNATION_DIVISIONAL_SECRETARY = 'ප්‍රාදේශීය ලේකම්';

    /**
     * @return array<int, string> Numbered CC lines.
     */
    public function buildForOfficer(Officer $officer, LetterBatch $batch): array
    {
        $lines = match ($batch->document_type) {
            DocumentType::Transfer => $this->transferLines($officer, $batch),
            DocumentType::Retirement => $this->retirementLines($officer),
            default => $this->stationLines($officer),
        };

        return $this->number($lines);
    }

    /**
     * Appointment / Promotion / Confirmation: the officer's own station.
     *
     * @return array<int, string>
     */
    protected function stationLines(Officer $officer): array
    {
        $lines = [];

        if ($district = $officer->district) {
            $lines[] = $this->line(self::DESIGNATION_DISTRICT_SECRETARY, $this->jurisdiction($district), $this->districtSignatory($district));
        }

        if ($ds = $officer->ds_division ?? $officer->dsDivision) {
            $lines[] = $this->line(self::DESIGNATION_DIVISIONAL_SECRETARY, $this->jurisdiction($ds), $this->divisionalSignatory($ds));
        }

        return $lines;
    }

    /**
     * Transfer: both the relinquished and the receiving stations.
     *
     * @return array<int, string>
     */
    protected function transferLines(Officer $officer, LetterBatch $batch): array
    {
        $fromDistrict = $officer->district;
        $toDistrict = $batch->district_id ? District::find($batch->district_id) : null;
        $fromDs = $officer->ds_division ?? $officer->dsDivision;
        $toDs = $batch->ds_division_id ? DsDivision::find($batch->ds_division_id) : null;

        $lines = [];

        if ($fromDistrict) {
            $lines[] = $this->line(self::DESIGNATION_DISTRICT_SECRETARY, $this->jurisdiction($fromDistrict), $this->districtSignatory($fromDistrict));
        }

        if ($toDistrict && $this->jurisdiction($toDistrict) !== $this->jurisdiction($fromDistrict)) {
            $lines[] = $this->line(self::DESIGNATION_DISTRICT_SECRETARY, $this->jurisdiction($toDistrict), $this->districtSignatory($toDistrict));
        }

        if ($fromDs) {
            $lines[] = $this->line(self::DESIGNATION_DIVISIONAL_SECRETARY, $this->jurisdiction($fromDs), $this->divisionalSignatory($fromDs));
        }

        if ($toDs && $this->jurisdiction($toDs) !== $this->jurisdiction($fromDs)) {
            $lines[] = $this->line(self::DESIGNATION_DIVISIONAL_SECRETARY, $this->jurisdiction($toDs), $this->divisionalSignatory($toDs));
        }

        return $lines;
    }

    /**
     * Retirement: pensions directorate + audit + the officer's station.
     *
     * @return array<int, string>
     */
    protected function retirementLines(Officer $officer): array
    {
        $lines = [
            $this->line('විශ්‍රාම වැටුප් අධ්‍යක්ෂ ජනරාල්', null, $this->signatoryByDesignation('විශ්‍රාම වැටුප් අධ්‍යක්ෂ ජනරාල්')),
            $this->line('විගණකාධිපති', null, $this->signatoryByDesignation('විගණකාධිපති')),
        ];

        if ($district = $officer->district) {
            $lines[] = $this->line(self::DESIGNATION_DISTRICT_SECRETARY, $this->jurisdiction($district), $this->districtSignatory($district));
        }

        if ($ds = $officer->ds_division ?? $officer->dsDivision) {
            $lines[] = $this->line(self::DESIGNATION_DIVISIONAL_SECRETARY, $this->jurisdiction($ds), $this->divisionalSignatory($ds));
        }

        return $lines;
    }

    protected function jurisdiction(mixed $model): string
    {
        if (is_string($model)) {
            return $model;
        }

        if (! $model) {
            return '';
        }

        return $model->name_si ?: ($model->name_en ?? '');
    }

    protected function line(string $designation, ?string $jurisdiction, ?Signatory $signatory): string
    {
        $line = $jurisdiction
            ? "{$designation}, {$jurisdiction}"
            : $designation;

        if ($signatory) {
            $line .= " ({$signatory->officer_name})";
        }

        return $line;
    }

    protected function districtSignatory(mixed $district): ?Signatory
    {
        if (! $district instanceof District) {
            return null;
        }

        return Signatory::query()
            ->where('is_active', true)
            ->where('category', SignatoryCategory::District->value)
            ->where('district_id', $district->id)
            ->first();
    }

    protected function divisionalSignatory(mixed $ds): ?Signatory
    {
        if (! $ds instanceof DsDivision) {
            return null;
        }

        return Signatory::query()
            ->where('is_active', true)
            ->where('category', SignatoryCategory::Divisional->value)
            ->where('ds_division_id', $ds->id)
            ->first();
    }

    protected function signatoryByDesignation(string $designation): ?Signatory
    {
        return Signatory::query()
            ->where('is_active', true)
            ->where('designation', $designation)
            ->whereNull('district_id')
            ->whereNull('ds_division_id')
            ->first();
    }

    /**
     * @param   array<int, string>  $lines
     * @return array<int, string>
     */
    protected function number(array $lines): array
    {
        $index = 0;

        return array_map(function (string $line) use (&$index) {
            $index++;

            return str_pad((string) $index, 2, '0', STR_PAD_LEFT).'. '.$line;
        }, $lines);
    }
}