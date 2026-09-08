<?php

namespace App\Enums;

enum EventType: string
{
    case Appointment = 'appointment';
    case Confirmation = 'confirmation';
    case Promotion = 'promotion';
    case Transfer = 'transfer';
    case Disciplinary = 'disciplinary';
    case Retirement = 'retirement';
    case Leave = 'leave';
    case Training = 'training';
    case Commendation = 'commendation';

    public function label(): string
    {
        return match ($this) {
            self::Appointment => 'Appointment',
            self::Confirmation => 'Confirmation',
            self::Promotion => 'Promotion',
            self::Transfer => 'Transfer',
            self::Disciplinary => 'Disciplinary',
            self::Retirement => 'Retirement',
            self::Leave => 'Leave',
            self::Training => 'Training',
            self::Commendation => 'Commendation',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
