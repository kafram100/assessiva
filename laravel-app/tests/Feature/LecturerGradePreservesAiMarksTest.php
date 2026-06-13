<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LecturerGradePreservesAiMarksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lecturer_override_updates_marks_awarded_without_touching_ai_marks_awarded(): void
    {
        $lecturer = User::factory()->create();

        $exam = Exam::query()->create([
            'user_id' => $lecturer->id,
            'title' => 'T',
            'course_name' => 'C',
            'duration' => 60,
            'exam_code' => 'E'.Str::upper(Str::random(8)),
            'is_published' => false,
            'results_released' => false,
        ]);

        $question = Question::query()->create([
            'exam_id' => $exam->id,
            'question_text' => 'Q?',
            'q_type' => QuestionType::Theory,
            'marks' => 2,
            'correct_option' => 'Model.',
        ]);

        $attempt = Attempt::query()->create([
            'exam_id' => $exam->id,
            'student_index' => 'STU001',
            'student_fullname' => 'Test Student',
            'status' => 'completed',
            'score' => 1.0,
            'raw_score' => 1.0,
            'total_marks' => 2,
            'question_order' => json_encode([(string) $question->id]),
        ]);

        $answer = Answer::query()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'theory_answer' => 'Answer text.',
            'marks_awarded' => 1.0,
            'ai_marks_awarded' => 1.0,
            'ai_confidence' => 0.8,
            'is_correct' => 1,
        ]);

        $this->actingAs($lecturer)
            ->post(route('lecturer.grades.store', $attempt), [
                'marks' => [(string) $answer->id => '2'],
            ])
            ->assertRedirect(route('lecturer.grades.attempt', $attempt));

        $answer->refresh();

        $this->assertSame('2.00', $answer->marks_awarded);
        $this->assertSame('1.00', $answer->ai_marks_awarded);
    }
}
