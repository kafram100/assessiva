<?php

namespace App\Support;

use App\Data\AnswerMarkingEvaluation;

/**
 * Post-processes theory AI evaluations so harsh zero marks do not override
 * the model's own detected-concept signals (no external API calls).
 */
final class TheoryMarkingFairnessGuard
{
    public static function apply(
        AnswerMarkingEvaluation $ev,
        int $maxMarks,
        string $studentResponse,
        string $questionType,
    ): AnswerMarkingEvaluation {
        if ($questionType !== 'theory' || $maxMarks <= 0) {
            return $ev;
        }

        $trim = trim($studentResponse);
        if (mb_strlen($trim) < 8) {
            return $ev;
        }

        $nD = count($ev->detectedConcepts);
        $nM = count($ev->missingConcepts);
        $marks = $ev->marksAwarded;

        if ($marks > 0.0001) {
            return $ev;
        }

        if ($nD < 1) {
            return $ev;
        }

        $max = (float) $maxMarks;
        $minPartial = min($max, max(0.5, round($max * 0.25 * 2) / 2.0));

        if ($nM === 0) {
            $suggested = $minPartial;
        } else {
            $ratio = $nD / max(1, $nD + $nM);
            $fromModelCoverage = $ev->conceptCoverage !== null
                ? $max * max($ratio, $ev->conceptCoverage)
                : $max * $ratio;
            $suggested = max($minPartial, self::roundMarkToHalf($fromModelCoverage));
            $suggested = min($max, $suggested);
        }

        $reason = trim((string) ($ev->reason ?? ''));
        $suffix = ' Partial-credit alignment: at least one relevant concept was identified; marks raised to reflect concept-based fairness.';

        return $ev->withAdjustedMarks(
            $suggested,
            $suggested + 0.001 >= $max ? 'correct' : 'partial',
            $reason !== '' ? $reason.$suffix : ltrim($suffix),
        );
    }

    public static function roundMarkToHalf(float $x): float
    {
        return round($x * 2) / 2.0;
    }
}
