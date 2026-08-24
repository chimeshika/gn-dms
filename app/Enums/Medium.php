<?php

namespace App\Enums;

enum Medium: string
{
    case Sinhala = 'sinhala';
    case Tamil = 'tamil';
    case English = 'english';

    public function label(): string
    {
        return match ($this) {
            self::Sinhala => 'Sinhala',
            self::Tamil => 'Tamil',
            self::English => 'English',
        };
    }
}
