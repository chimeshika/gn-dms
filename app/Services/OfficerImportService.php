<?php

namespace App\Services;

use App\Enums\OfficerGrade;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Imports\OfficerRowImport;
use App\Models\Officer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class OfficerImportService
{
    /**
     * Canonical header => accepted aliases.
     */
    protected const COLUMN_ALIASES = [
        'nic_no'                 => ['nic', 'nic_number', 'nic no', 'nic no.', 'national id', 'national id no', 'national identity number'],
        'full_name_en'           => ['full name en', 'name en', 'english name', 'full name', 'name'],
        'full_name_si'           => ['full name si', 'name si', 'sinhala name', 'full name (si)'],
        'full_name_ta'           => ['full name ta', 'name ta', 'tamil name', 'full name (ta)'],
        'dob'                    => ['date of birth', 'birth date', 'birthday', 'dob'],
        'gender'                 => ['sex'],
        'medium'                 => ['language', 'medium of instruction'],
        'address_line1'          => ['address line 1', 'address 1', 'address line1', 'address1', 'address', 'officer_address_line1'],
        'address_line2'          => ['address line 2', 'address 2', 'address line2', 'address2', 'officer_address_line2'],
        'address_line3'          => ['address line 3', 'address 3', 'address line3', 'address3', 'officer_address_line3'],
        'first_appointment_date' => ['first appointment date', 'appointment date', 'appointment_date', 'date of appointment', 'joined date', 'joined'],
        'current_grade'          => ['current grade', 'grade', 'officer grade', 'rank'],
        'district'               => ['district name', 'district_name', 'district'],
        'ds_division'            => ['ds division', 'ds division name', 'ds_division', 'ds_division_name', 'divisional secretariat', 'ds'],
        'gn_division'            => ['gn division', 'gn division name', 'gn_division', 'gn_division_name', 'gn'],
    ];

    /**
     * Import officers from an Excel/CSV file.
     */
    public function import(string $filePath, ?int $batchDsDivisionId = null, string $disk = 'local'): array
    {
        $rawRows = $this->readRows($filePath, $disk);

        if ($rawRows->isEmpty()) {
            return ['officers' => collect(), 'skipped' => []];
        }

        $headerMap = $this->buildHeaderMap($rawRows->first());

        $officers = collect();
        $skipped = [];
        $rowNumber = 1;

        foreach ($rawRows as $rawRow) {
            $rowNumber++;
            $row = $this->mapRow($rawRow, $headerMap);

            $nic = $this->sanitizeNic($row['nic_no'] ?? '');
            $fullNameEn = trim((string) ($row['full_name_en'] ?? ''));

            if ($nic === '' || $fullNameEn === '') {
                $skipped[] = ['row' => $rowNumber, 'reason' => 'nic_no or full_name_en missing'];
                continue;
            }

            $grade = $this->resolveGrade($row['current_grade'] ?? null);
            $dob = $this->parseDate($row['dob'] ?? null);
            $appointmentDate = $this->parseDate($row['first_appointment_date'] ?? null);

            // Direct Text Values (IDs සෙවීම නතර කර Text ම එකතු කිරීම)
            $districtName   = trim((string) ($row['district'] ?? ''));
            $dsDivisionName = trim((string) ($row['ds_division'] ?? ''));
            $gnDivisionName = trim((string) ($row['gn_division'] ?? ''));

            $officer = Officer::updateOrCreate(
                ['nic_no' => $nic],
                [
                    'full_name_en'           => $fullNameEn,
                    'full_name_si'           => $row['full_name_si'] ?? null,
                    'full_name_ta'           => $row['full_name_ta'] ?? null,
                    'gender'                 => Officer::resolveGender($row['gender'] ?? null),
                    'medium'                 => Officer::resolveMedium($row['medium'] ?? null),
                    'dob'                    => $dob,
                    'first_appointment_date' => $appointmentDate,
                    'address_line1'          => $row['address_line1'] ?? null,
                    'address_line2'          => $row['address_line2'] ?? null,
                    'address_line3'          => $row['address_line3'] ?? null,
                    'current_grade'          => $grade?->value ?? OfficerGrade::GradeIII->value,
                    
                    // Column names match direct DB strings
                    'district'               => $districtName !== '' ? $districtName : null,
                    'ds_division'            => $dsDivisionName !== '' ? $dsDivisionName : null,
                    'gn_division'            => $gnDivisionName !== '' ? $gnDivisionName : null,
                ]
            );

            // Ensure the officer has a linked user account using firstOrCreate to avoid Duplicate Entry errors.
            if (! $officer->user_id) {
                $domain = config('app.pending_email_domain', 'pending.gn.local');
                $email  = strtolower($nic) . '@' . $domain;

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $fullNameEn,
                        'password' => bcrypt(Str::random(16)),
                        'nic_no'   => $nic,
                        'role'     => UserRole::Officer,
                        'status'   => UserStatus::PendingVerification,
                    ]
                );

                $officer->update(['user_id' => $user->id]);
            }

            $officers->push($officer);
        }

        return compact('officers', 'skipped');
    }

    protected function buildHeaderMap(mixed $firstRow): array
    {
        $firstRowArray = $firstRow instanceof \Illuminate\Support\Collection ? $firstRow->toArray() : (array) $firstRow;
        $map = [];

        foreach (array_keys($firstRowArray) as $rawHeader) {
            $normalised = strtolower(trim(str_replace(['-', '_'], ' ', (string) $rawHeader)));
            $canonical = null;

            foreach (self::COLUMN_ALIASES as $col => $aliases) {
                if ($normalised === $col || in_array($normalised, $aliases, true)) {
                    $canonical = $col;
                    break;
                }
            }

            if (! $canonical && array_key_exists($normalised, self::COLUMN_ALIASES)) {
                $canonical = $normalised;
            }

            $map[$rawHeader] = $canonical ?? $normalised;
        }

        return $map;
    }

    protected function mapRow(mixed $rawRow, array $headerMap): array
    {
        $rawArray = $rawRow instanceof \Illuminate\Support\Collection ? $rawRow->toArray() : (array) $rawRow;
        $mapped = [];

        foreach ($rawArray as $key => $value) {
            $canonical = $headerMap[$key] ?? strtolower(trim((string) $key));
            $mapped[$canonical] = is_string($value) ? trim($value) : $value;
        }

        return $mapped;
    }

    protected function readRows(string $filePath, string $disk): Collection
    {
        $absolutePath = $this->resolvePath($filePath, $disk);

        if (! $absolutePath || ! is_file($absolutePath)) {
            return collect();
        }

        return Excel::toCollection(new OfficerRowImport, $absolutePath)->first() ?? collect();
    }

    protected function resolvePath(string $filePath, string $disk): ?string
    {
        if (preg_match('/^[A-Z]:\\\\/i', $filePath) || str_starts_with($filePath, '/')) {
            return is_file($filePath) ? $filePath : null;
        }

        $diskPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($filePath);

        if (is_file($diskPath)) {
            return $diskPath;
        }

        $defaultPath = storage_path("app/{$filePath}");

        return is_file($defaultPath) ? $defaultPath : null;
    }

    protected function sanitizeNic(mixed $value): string
    {
        $raw = trim(preg_replace('/\s+/', '', (string) $value));

        return strtoupper($raw);
    }

    protected function resolveGrade(mixed $value): ?OfficerGrade
    {
        if (! $value) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        return match (true) {
            str_contains($normalized, 'grade_iii') || str_contains($normalized, 'iii') => OfficerGrade::GradeIII,
            str_contains($normalized, 'grade_ii')  || str_contains($normalized, 'ii')  => OfficerGrade::GradeII,
            str_contains($normalized, 'grade_i')   || str_contains($normalized, 'i')   => OfficerGrade::GradeI,
            str_contains($normalized, 'special') => OfficerGrade::Special,
            default => OfficerGrade::tryFrom($normalized),
        };
    }

    protected function parseDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw) && $raw > 30000 && $raw < 60000) {
            try {
                return Carbon::serialToDateTime((float) $raw)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        $normalized = str_replace(['.', '/'], '-', $raw);

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $normalized, $m)) {
            $normalized = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $normalized)) {
            try {
                return Carbon::parse($normalized)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
