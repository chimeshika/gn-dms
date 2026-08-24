<?php

namespace App\Enums;

enum OfficerGrade: string
{
    case GradeIII = 'grade_iii';
    case GradeII = 'grade_ii';
    case GradeI = 'grade_i';
    case Special = 'special';

    public function label(): string
    {
        return match ($this) {
            self::GradeIII => 'Grade III',
            self::GradeII => 'Grade II',
            self::GradeI => 'Grade I',
            self::Special => 'Special Grade',
        };
    }

    /**
     * Promotional order used by the promotion workflow.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::GradeIII => self::GradeII,
            self::GradeII => self::GradeI,
            self::GradeI => self::Special,
            self::Special => null,
        };
    }
}
