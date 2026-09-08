<?php

namespace App\Services;

use App\Enums\SignatoryCategory;
use App\Models\Signatory;
use Illuminate\Support\Collection;

class SignatoryService
{
    public function __construct(
        protected AutoCcService $autoCcService,
    ) {}

    public function resolveForOfficer(\App\Models\Officer $officer, \App\Enums\DocumentType $type): ?Signatory
    {
        return $this->resolveSecretary();
    }

    public function resolveSecretary(): ?Signatory
    {
        return $this->findActiveByCategory(SignatoryCategory::Secretary)
            ?? $this->findActiveByCategory(SignatoryCategory::ControllingOfficer)
            ?? Signatory::query()->where('is_active', true)->first();
    }

    public function resolveControllingOfficer(): ?Signatory
    {
        return $this->findActiveByCategory(SignatoryCategory::ControllingOfficer)
            ?? $this->findActiveByCategory(SignatoryCategory::Secretary)
            ?? Signatory::query()->where('is_active', true)->first();
    }

    /**
     * Active signatories for a given category — for letter edit dropdowns.
     * Returns all active signatories of this category regardless of district/ds scope.
     */
    public function optionsFor(SignatoryCategory $category): Collection
    {
        return Signatory::query()
            ->where('is_active', true)
            ->where('category', $category->value)
            ->orderBy('officer_name')
            ->get();
    }

    /**
     * Find an active signatory by category, optionally scoped by division/district.
     */
    public function findForCategory(SignatoryCategory $category, ?int $dsDivisionId = null, ?int $districtId = null): ?Signatory
    {
        return Signatory::query()
            ->where('is_active', true)
            ->where('category', $category->value)
            ->when($dsDivisionId, fn ($q) => $q->where(fn ($scope) => $scope->where('ds_division_id', $dsDivisionId)->orWhereNull('ds_division_id')))
            ->when($districtId, fn ($q) => $q->where(fn ($scope) => $scope->where('district_id', $districtId)->orWhereNull('district_id')))
            ->orderBy('id')
            ->first();
    }

    public function buildAutoCc(\App\Models\Officer $officer, \App\Models\LetterBatch $batch): array
    {
        return $this->autoCcService->buildForOfficer($officer, $batch);
    }

    protected function findActiveByCategory(SignatoryCategory $category): ?Signatory
    {
        return Signatory::query()
            ->where('is_active', true)
            ->where('category', $category->value)
            ->whereNull('district_id')
            ->whereNull('ds_division_id')
            ->orderBy('id')
            ->first();
    }
}
