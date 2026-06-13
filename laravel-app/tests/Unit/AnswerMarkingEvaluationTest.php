<?php

namespace Tests\Unit;

use App\Data\AnswerMarkingEvaluation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnswerMarkingEvaluationTest extends TestCase
{
    #[Test]
    public function from_array_clamps_marks_to_max_and_parses_concepts(): void
    {
        $data = [
            'marks_awarded' => 99,
            'confidence' => 1.25,
            'evaluation_status' => 'partial',
            'detected_concepts' => ['a', 'b'],
            'missing_concepts' => ['c'],
            'reason' => 'R',
            'feedback' => 'F',
            'concept_coverage' => 0.6,
            'key_concepts_rubric' => ['a', 'b', 'c', 'd'],
        ];

        $ev = AnswerMarkingEvaluation::fromArray($data, 2, '{"x":1}');

        $this->assertSame(2.0, $ev->marksAwarded);
        $this->assertSame(1.0, $ev->confidence);
        $this->assertSame('partial', $ev->evaluationStatus);
        $this->assertSame(['a', 'b'], $ev->detectedConcepts);
        $this->assertSame(['c'], $ev->missingConcepts);
        $this->assertSame('R', $ev->reason);
        $this->assertSame('F', $ev->feedback);
        $this->assertSame(0.6, $ev->conceptCoverage);
        $this->assertSame(['a', 'b', 'c', 'd'], $ev->rubricConcepts);
        $this->assertSame('{"x":1}', $ev->rawModelResponse);
    }

    #[Test]
    public function from_array_clamps_concept_coverage_between_zero_and_one(): void
    {
        $low = AnswerMarkingEvaluation::fromArray(['concept_coverage' => -0.5, 'marks_awarded' => 0], 2);
        $high = AnswerMarkingEvaluation::fromArray(['concept_coverage' => 2, 'marks_awarded' => 0], 2);

        $this->assertSame(0.0, $low->conceptCoverage);
        $this->assertSame(1.0, $high->conceptCoverage);
    }

    #[Test]
    public function from_array_accepts_rubric_concepts_alias(): void
    {
        $ev = AnswerMarkingEvaluation::fromArray([
            'marks_awarded' => 1,
            'rubric_concepts' => ['x'],
        ], 2);

        $this->assertSame(['x'], $ev->rubricConcepts);
    }

    #[Test]
    public function with_adjusted_marks_preserves_concept_metadata(): void
    {
        $base = new AnswerMarkingEvaluation(
            marksAwarded: 0.0,
            confidence: 0.5,
            evaluationStatus: 'incorrect',
            detectedConcepts: ['a'],
            missingConcepts: ['b'],
            reason: 'Old',
            feedback: 'Tip',
            rawModelResponse: '{}',
            conceptCoverage: 0.25,
            rubricConcepts: ['a', 'b'],
        );

        $adj = $base->withAdjustedMarks(1.0, 'partial', 'New reason');

        $this->assertSame(1.0, $adj->marksAwarded);
        $this->assertSame('partial', $adj->evaluationStatus);
        $this->assertSame('New reason', $adj->reason);
        $this->assertSame(0.5, $adj->confidence);
        $this->assertSame(['a'], $adj->detectedConcepts);
        $this->assertSame(['b'], $adj->missingConcepts);
        $this->assertSame('Tip', $adj->feedback);
        $this->assertSame(0.25, $adj->conceptCoverage);
        $this->assertSame(['a', 'b'], $adj->rubricConcepts);
    }
}
