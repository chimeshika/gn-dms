<?php

namespace App\Enums;

enum SignatoryCategory: string
{
    case Secretary = 'secretary';
    case ControllingOfficer = 'controlling_officer';
    case District = 'district';
    case Divisional = 'divisional';
    case Special = 'special';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Secretary => 'අමාත්‍යාංශ ලේකම් වෙනුවට (Secretary)',
            self::ControllingOfficer => 'පාලන නිලධාරී (Controlling Officer)',
            self::District => 'දිස්ත්‍රික් ලේකම් (District Secretary)',
            self::Divisional => 'ප්‍රාදේශීය ලේකම් (Divisional Secretary)',
            self::Special => 'විශේෂ ලාභියා (Pensions / Audit)',
            self::Other => 'වෙනත් (Other)',
        };
    }
}
