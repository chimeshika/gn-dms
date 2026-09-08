<?php

namespace App\Enums;

use App\Enums\EventType;

enum DocumentType: string
{
    case Appointment = 'appointment';
    case Confirmation = 'confirmation';
    case Promotion = 'promotion';
    case PromotionGrade2 = 'promotion_grade_2';
    case Transfer = 'transfer';
    case Disciplinary = 'disciplinary';
    case Retirement = 'retirement';
    case Certificate = 'certificate';
    case Leave = 'leave';
    case TrainingCertificate = 'training_certificate';
    case Commendation = 'commendation';

    public function label(): string
    {
        return match ($this) {
            self::Appointment => 'ග්‍රාම නිලධාරී III ශ්‍රේණියේ තනතුරට පත්කිරීම',
            self::Confirmation => 'තහවුරු කිරීම් ලිපිය (Confirmation Letter)',
            self::Promotion => 'ග්‍රාම නිලධාරී I ශ්‍රේණියට උසස් කිරීම',
            self::PromotionGrade2 => 'ග්‍රාම නිලධාරී II ශ්‍රේණියට උසස් කිරීම',
            self::Transfer => 'ස්ථාන මාරුවීම් නියෝගය',
            self::Disciplinary => 'වැරදි ක්‍රියා පිළිබඳ දැනුම්දීම (Disciplinary Notice)',
            self::Retirement => 'සේවයෙන් විශ්‍රාම ගැන්වීම',
            self::Certificate => 'සේවා සහතිකය (Service Certificate)',
            self::Leave => 'නිවාඩු අනුමැතිය (Leave Approval)',
            self::TrainingCertificate => 'පුහුණු පාඨමාලා සහතිකය (Training Certificate)',
            self::Commendation => 'පැසසුම් ලිපිය (Commendation)',
        };
    }

    public function eventType(): EventType
    {
        return match ($this) {
            self::Appointment => EventType::Appointment,
            self::Confirmation => EventType::Confirmation,
            self::Promotion, self::PromotionGrade2 => EventType::Promotion,
            self::Transfer => EventType::Transfer,
            self::Disciplinary => EventType::Disciplinary,
            self::Retirement => EventType::Retirement,
            self::Certificate => EventType::Appointment,
            self::Leave => EventType::Leave,
            self::TrainingCertificate => EventType::Training,
            self::Commendation => EventType::Commendation,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
