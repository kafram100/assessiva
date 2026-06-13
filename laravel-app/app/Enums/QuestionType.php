<?php

namespace App\Enums;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case Theory = 'theory';
    case ShortAnswer = 'short_answer';
    case File = 'file';
    case FillIn = 'fill_in';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
