<?php

namespace Tests\Unit;

use App\Models\Answer;
use App\Models\Question;
use App\Support\AnswerScoring;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnswerScoringShortAnswerTest extends TestCase
{
    #[Test]
    public function short_answer_contribution_uses_marks_awarded_like_theory(): void
    {
        $q = new Question([
            'marks' => 2,
            'q_type' => \App\Enums\QuestionType::ShortAnswer,
        ]);

        $a = new Answer([
            'marks_awarded' => 1.5,
        ]);

        $this->assertSame(1.5, AnswerScoring::contribution($a, $q));
    }
}
