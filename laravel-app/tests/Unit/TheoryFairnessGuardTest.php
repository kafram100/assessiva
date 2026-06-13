<?php

namespace Tests\Unit;

use App\Data\AnswerMarkingEvaluation;
use App\Support\TheoryMarkingFairnessGuard;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TheoryFairnessGuardTest extends TestCase
{
    private function evFromJson(array $payload, int $maxMarks): AnswerMarkingEvaluation
    {
        return AnswerMarkingEvaluation::fromArray($payload, $maxMarks, json_encode($payload));
    }

    #[Test]
    public function when_ai_returns_zero_but_lists_detected_concepts_partial_marks_are_applied(): void
    {
        $payload = [
            'marks_awarded' => 0,
            'detected_concepts' => ['algorithm explanation'],
            'missing_concepts' => [],
            'evaluation_status' => 'incorrect',
            'reason' => 'Too vague.',
        ];

        $ev = $this->evFromJson($payload, 2);
        $out = TheoryMarkingFairnessGuard::apply(
            $ev,
            2,
            'For explaining how an algorithm works',
            'theory',
        );

        $this->assertGreaterThan(0.0, $out->marksAwarded);
        $this->assertLessThanOrEqual(2.0, $out->marksAwarded);
        $this->assertContains($out->evaluationStatus, ['partial', 'correct']);
        $this->assertStringContainsString('Partial-credit alignment', (string) $out->reason);
    }

    #[Test]
    public function blank_student_response_leaves_zero_even_if_model_lists_concepts(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 0,
            'detected_concepts' => ['x'],
            'missing_concepts' => ['y'],
        ], 2);

        $out = TheoryMarkingFairnessGuard::apply($ev, 2, '   ', 'theory');

        $this->assertSame(0.0, $out->marksAwarded);
        $this->assertSame($ev->reason, $out->reason);
    }

    #[Test]
    public function off_topic_answer_with_no_detected_concepts_stays_at_zero(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 0,
            'detected_concepts' => [],
            'missing_concepts' => ['pseudocode purpose', 'planning'],
            'evaluation_status' => 'incorrect',
        ], 2);

        $out = TheoryMarkingFairnessGuard::apply(
            $ev,
            2,
            'The capital of France is Paris and I like pizza.',
            'theory',
        );

        $this->assertSame(0.0, $out->marksAwarded);
        $this->assertSame($ev->evaluationStatus, $out->evaluationStatus);
    }

    #[Test]
    public function when_detected_and_missing_lists_exist_marks_scale_proportionally(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 0,
            'detected_concepts' => ['a', 'b'],
            'missing_concepts' => ['c', 'd'],
            'concept_coverage' => null,
        ], 4);

        $out = TheoryMarkingFairnessGuard::apply(
            $ev,
            4,
            'A student answer with enough length for the fairness guard.',
            'theory',
        );

        // ratio 2/4 = 0.5 → raw 2.0 → rounded to 2.0; minPartial for max 4 is 1.0
        $this->assertSame(2.0, $out->marksAwarded);
    }

    #[Test]
    public function concept_coverage_can_boost_above_ratio_when_model_reports_higher_coverage(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 0,
            'detected_concepts' => ['a'],
            'missing_concepts' => ['b', 'c', 'd'],
            'concept_coverage' => 0.75,
        ], 4);

        $out = TheoryMarkingFairnessGuard::apply(
            $ev,
            4,
            'Another answer that is long enough for the fairness guard to run.',
            'theory',
        );

        $ratio = 1 / 4;
        $this->assertSame(3.0, $out->marksAwarded);
        $this->assertGreaterThan(4 * $ratio, $out->marksAwarded);
    }

    #[Test]
    public function non_theory_question_type_is_not_adjusted(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 0,
            'detected_concepts' => ['synonym ok'],
            'missing_concepts' => [],
        ], 2);

        $out = TheoryMarkingFairnessGuard::apply(
            $ev,
            2,
            'Some fill-in text long enough.',
            'fill_in',
        );

        $this->assertSame(0.0, $out->marksAwarded);
    }

    #[Test]
    public function positive_ai_marks_are_left_unchanged(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 1.5,
            'detected_concepts' => ['a'],
            'missing_concepts' => ['b'],
        ], 2);

        $out = TheoryMarkingFairnessGuard::apply(
            $ev,
            2,
            'Student already received credit from the model.',
            'theory',
        );

        $this->assertSame(1.5, $out->marksAwarded);
    }

    #[Test]
    public function very_short_theory_answer_skips_guard_even_with_detected_concepts(): void
    {
        $ev = $this->evFromJson([
            'marks_awarded' => 0,
            'detected_concepts' => ['hint'],
            'missing_concepts' => ['x'],
        ], 2);

        $out = TheoryMarkingFairnessGuard::apply($ev, 2, 'short', 'theory');

        $this->assertSame(0.0, $out->marksAwarded);
    }
}
