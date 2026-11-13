<?php

namespace Tests\Feature;

use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTokenUsageTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(int $freeQuestions = 20, int $tokens = 0): User
    {
        return User::create([
            'name' => 'Test Student',
            'username' => 'teststudent' . uniqid(),
            'email' => 'student' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'status' => UserStatus::Approved,
            'is_active' => true,
            'free_questions_remaining' => $freeQuestions,
            'tokens' => $tokens,
        ]);
    }

    private function createQuestions(string $topic, int $count = 5): void
    {
        $lecturer = User::create([
            'name' => 'Lecturer',
            'username' => 'lecturer' . uniqid(),
            'email' => 'lecturer' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Approved,
            'is_active' => true,
        ]);

        for ($i = 0; $i < $count; $i++) {
            $q = QuestionBank::create([
                'lecturer_id' => $lecturer->id,
                'created_by' => $lecturer->id,
                'topic' => $topic,
                'level' => '100',
                'question_text' => "Question {$i} for {$topic}",
                'question_type' => 'mcq',
                'difficulty' => 'easy',
                'correct_answer' => 'A',
                'is_active' => true,
            ]);

            QuestionOption::create([
                'question_bank_id' => $q->id,
                'option_key' => 'A',
                'option_text' => 'Option A',
            ]);
            QuestionOption::create([
                'question_bank_id' => $q->id,
                'option_key' => 'B',
                'option_text' => 'Option B',
            ]);
        }
    }

    public function test_user_with_remaining_free_tokens_can_generate_without_insufficient_tokens_error(): void
    {
        $topic = 'Computer Science ' . uniqid();
        $this->createQuestions($topic, 5);

        // Student has only 1 free question left and 0 tokens
        $student = $this->createStudent(freeQuestions: 1, tokens: 0);

        // Student requests 20 questions (default)
        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => $topic,
                'question_type' => 'all',
                'count' => 20,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['practiceSession', 'questions']);

        // Exactly 1 question returned and free questions decremented to 0
        $student->refresh();
        $this->assertEquals(0, $student->free_questions_remaining);
        $this->assertEquals(0, $student->tokens);
        $this->assertCount(1, $response->json('questions'));
    }

    public function test_user_with_zero_tokens_is_blocked_with_clear_message(): void
    {
        $topic = 'Physics ' . uniqid();
        $this->createQuestions($topic, 5);

        // Student has exhausted all free questions and has 0 tokens
        $student = $this->createStudent(freeQuestions: 0, tokens: 0);

        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => $topic,
                'question_type' => 'all',
                'count' => 5,
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'error' => "You've exhausted your free questions and tokens. Top up your tokens to keep unlocking new questions!",
        ]);
    }

    public function test_user_uses_free_tokens_first_then_paid_tokens(): void
    {
        $topic = 'Biology ' . uniqid();
        $this->createQuestions($topic, 5);

        // Student has 2 free questions and 5 tokens
        $student = $this->createStudent(freeQuestions: 2, tokens: 5);

        // Request 3 questions
        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => $topic,
                'question_type' => 'all',
                'count' => 3,
            ]);

        $response->assertStatus(200);

        $student->refresh();
        // 2 free questions used, 1 token used -> free: 0, tokens: 4
        $this->assertEquals(0, $student->free_questions_remaining);
        $this->assertEquals(4, $student->tokens);
        $this->assertCount(3, $response->json('questions'));
    }

    public function test_user_selecting_15_questions_receives_15_questions_and_not_clamped_to_4(): void
    {
        $topic = 'Chemistry ' . uniqid();
        $this->createQuestions($topic, 20);

        // A new user has 20 free tokens
        $student = $this->createStudent(freeQuestions: 20, tokens: 0);

        // User requests 15 questions
        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => $topic,
                'question_type' => 'mcqs',
                'count' => 15,
            ]);

        $response->assertStatus(200);
        $questions = $response->json('questions');

        // MUST be 15 questions, NOT clamped to 4!
        $this->assertCount(15, $questions);

        $student->refresh();
        $this->assertEquals(5, $student->free_questions_remaining);
        $this->assertEquals(0, $student->tokens);
    }

    public function test_user_selecting_specific_question_type_gets_only_that_type(): void
    {
        $topic = 'Computer Science ' . uniqid();

        $lecturer = User::create([
            'name' => 'Lecturer',
            'username' => 'lecturer' . uniqid(),
            'email' => 'lecturer' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Approved,
            'is_active' => true,
        ]);

        // Seed 10 MCQs
        for ($i = 0; $i < 10; $i++) {
            $q = QuestionBank::create([
                'lecturer_id' => $lecturer->id,
                'created_by' => $lecturer->id,
                'topic' => $topic,
                'level' => '100',
                'question_text' => "MCQ {$i}",
                'question_type' => 'mcq',
                'difficulty' => 'easy',
                'correct_answer' => 'A',
                'is_active' => true,
            ]);
            QuestionOption::create([
                'question_bank_id' => $q->id,
                'option_key' => 'A',
                'option_text' => 'Option A',
            ]);
        }

        // Seed 10 Theory
        for ($i = 0; $i < 10; $i++) {
            QuestionBank::create([
                'lecturer_id' => $lecturer->id,
                'created_by' => $lecturer->id,
                'topic' => $topic,
                'level' => '100',
                'question_text' => "Theory {$i}",
                'question_type' => 'theory',
                'difficulty' => 'easy',
                'model_answer' => 'Model answer',
                'is_active' => true,
            ]);
        }

        $student = $this->createStudent(freeQuestions: 20, tokens: 0);

        // Test 1: MCQs only
        $responseMcq = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => $topic,
                'question_type' => 'mcqs',
                'count' => 5,
            ]);
        $responseMcq->assertStatus(200);
        foreach ($responseMcq->json('questions') as $q) {
            $this->assertEquals('mcq', $q['q_type']);
        }

        // Test 2: Theory only
        $responseTheory = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => $topic,
                'question_type' => 'theory',
                'count' => 5,
            ]);
        $responseTheory->assertStatus(200);
        foreach ($responseTheory->json('questions') as $q) {
            $this->assertContains($q['q_type'], ['theory', 'short_answer']);
        }
    }

    public function test_user_typing_non_existent_course_returns_404_and_does_not_deduct_tokens(): void
    {
        // Database has questions for Computer Fundamentals
        $this->createQuestions('Computer Fundamentals', 10);

        $student = $this->createStudent(freeQuestions: 20, tokens: 0);

        // Student types a non-existent course 'Science'
        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => 'Science',
                'question_type' => 'all',
                'count' => 10,
            ]);

        $response->assertStatus(404);
        $this->assertStringContainsString('No questions found for the course "Science"', $response->json('error'));

        // Verify tokens and free questions were NOT deducted
        $student->refresh();
        $this->assertEquals(20, $student->free_questions_remaining);
        $this->assertEquals(0, $student->tokens);
    }

    public function test_user_typing_existing_course_returns_only_questions_for_that_course(): void
    {
        $this->createQuestions('Database Systems', 8);
        $this->createQuestions('Operating Systems', 8);

        $student = $this->createStudent(freeQuestions: 20, tokens: 0);

        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => 'Database Systems',
                'question_type' => 'all',
                'count' => 5,
            ]);

        $response->assertStatus(200);
        $questions = $response->json('questions');
        $this->assertCount(5, $questions);

        // Check that each question belongs to Database Systems and none to Operating Systems
        foreach ($questions as $q) {
            $this->assertStringContainsString('Database Systems', $q['question_text']);
            $this->assertStringNotContainsString('Operating Systems', $q['question_text']);
        }
    }

    public function test_questions_associated_by_course_id_are_found_when_topic_is_null(): void
    {
        $admin = $this->createStudent();
        $course = \App\Models\Course::create([
            'user_id' => $admin->id,
            'name' => 'Digital Literacy',
            'code' => 'DL101',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $q = QuestionBank::create([
                'course_id' => $course->id,
                'lecturer_id' => $admin->id,
                'created_by' => $admin->id,
                'level' => '100',
                'topic' => null,
                'question_text' => "Digital Literacy Question {$i}",
                'question_type' => 'mcq',
                'difficulty' => 'medium',
                'correct_answer' => 'A',
                'suggested_marks' => 1,
                'is_active' => true,
            ]);
            QuestionOption::create([
                'question_bank_id' => $q->id,
                'option_key' => 'A',
                'option_text' => 'Correct Answer',
                'is_correct' => true,
            ]);
            QuestionOption::create([
                'question_bank_id' => $q->id,
                'option_key' => 'B',
                'option_text' => 'Wrong Answer',
                'is_correct' => false,
            ]);
        }

        $student = $this->createStudent(freeQuestions: 15, tokens: 0);

        $response = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => 'Digital Literacy',
                'question_type' => 'mcq',
                'count' => 5,
            ]);

        $response->assertStatus(200);
        $questions = $response->json('questions');
        $this->assertCount(5, $questions);
        $this->assertNotNull($response->json('practiceSession'));
    }
}

