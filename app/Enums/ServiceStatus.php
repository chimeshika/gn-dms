<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Appointed = 'appointed';
    case Confirmed = 'confirmed';
    case Promoted = 'promoted';
    case Transferred = 'transferred';
    case Interdicted = 'interdicted';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Appointed => 'Appointed',
            self::Confirmed => 'Confirmed',
            self::Promoted => 'Promoted',
            self::Transferred => 'Transferred',
            self::Interdicted => 'Interdicted',
            self::Retired => 'Retired',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
