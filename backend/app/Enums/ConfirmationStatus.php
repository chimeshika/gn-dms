<?php

namespace App\Enums;

enum ConfirmationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Confirmation',
            self::Confirmed => 'Confirmed',
        };
    }
}
