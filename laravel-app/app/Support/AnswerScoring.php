<?php

namespace App\Support;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Question;

final class AnswerScoring
{
    public static function qType(Question $q): string
    {
        $t = $q->q_type;

        return $t instanceof QuestionType ? $t->value : (string) $t;
    }

    /**
     * Marks counted toward attempt total for one answer row.
     * Fill-in: deterministic match (is_correct) always earns full question marks even if AI stored 0.
     */
    public static function contribution(Answer $ans, ?Question $q): float
    {
        if (! $q) {
            return 0.0;
        }
        $type = self::qType($q);
        $max = (float) ($q->marks ?? 0);

        if ($type === 'mcq') {
            return $ans->is_correct ? $max : 0.0;
        }

        if ($type === 'fill_in') {
            if ($ans->is_correct) {
                return $max;
            }

            return min($max, max(0.0, (float) ($ans->marks_awarded ?? 0)));
        }

        if ($type === 'theory' || $type === 'short_answer' || $type === 'file') {
            return min($max, max(0.0, (float) ($ans->marks_awarded ?? 0)));
        }

        return 0.0;
    }
}
