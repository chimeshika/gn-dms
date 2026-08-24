<?php

namespace App\Enums;

enum UserRole: string
{
    case Officer = 'officer';
    case DivisionalAdmin = 'divisional_admin';
    case DistrictAdmin = 'district_admin';
    case MainAdmin = 'main_admin';

    public function spatieRole(): string
    {
        return match ($this) {
            self::MainAdmin => 'main_admin',
            self::DistrictAdmin => 'district_admin',
            self::DivisionalAdmin => 'divisional_admin',
            self::Officer => 'officer',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MainAdmin => 'Main Admin',
            self::DistrictAdmin => 'District Admin',
            self::DivisionalAdmin => 'Divisional Admin',
            self::Officer => 'Officer',
        };
    }
}