<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPracticeSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPracticeGradingAndWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(int $freeQuestions = 20, int $tokens = 100): User
    {
        return User::create([
            'name' => 'Grading Student',
            'username' => 'grade_student_' . uniqid(),
            'email' => 'student_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'status' => UserStatus::Approved,
            'is_active' => true,
            'free_questions_remaining' => $freeQuestions,
            'tokens' => $tokens,
        ]);
    }

    public function test_mcq_options_are_shuffled_deterministically_and_graded_accurately(): void
    {
        $student = $this->createStudent(freeQuestions: 10, tokens: 10);

        // Create a question where correct answer is 'C'
        $q = QuestionBank::create([
            'lecturer_id' => $student->id,
            'created_by' => $student->id,
            'topic' => 'Physics 101',
            'question_text' => 'What is the speed of light?',
            'question_type' => 'mcq',
            'difficulty' => 'easy',
            'correct_answer' => 'C',
            'is_active' => true,
        ]);

        QuestionOption::create(['question_bank_id' => $q->id, 'option_key' => 'A', 'option_text' => '300 m/s']);
        QuestionOption::create(['question_bank_id' => $q->id, 'option_key' => 'B', 'option_text' => '3,000 m/s']);
        QuestionOption::create(['question_bank_id' => $q->id, 'option_key' => 'C', 'option_text' => '300,000 km/s']);
        QuestionOption::create(['question_bank_id' => $q->id, 'option_key' => 'D', 'option_text' => 'Speed of sound']);

        // Generate practice session
        $genResponse = $this->actingAs($student)
            ->postJson(route('student.practice.generate'), [
                'topic' => 'Physics 101',
                'question_type' => 'mcqs',
                'count' => 1,
            ]);

        $genResponse->assertOk();
        $sessionId = $genResponse->json('practiceSession.id');
        $this->assertNotNull($sessionId);

        $session = UserPracticeSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertNotEmpty($session->options_order);
        $this->assertArrayHasKey((string)$q->id, $session->options_order);

        // Find which displayed key corresponds to original key 'C'
        $map = $session->options_order[(string)$q->id]; // e.g. ['A' => 'B', 'B' => 'C', ...]
        $displayedKeyForC = null;
        foreach ($map as $displayedKey => $origKey) {
            if ($origKey === 'C') {
                $displayedKeyForC = $displayedKey;
                break;
            }
        }
        $this->assertNotNull($displayedKeyForC);

        // Submit the answer using the displayed key that maps to 'C'
        $submitResponse = $this->actingAs($student)
            ->post(route('student.practice.submit', ['session' => $session->id]), [
                'responses' => [
                    $q->id => $displayedKeyForC,
                ],
                'time_spent' => 45,
            ]);

        $submitResponse->assertRedirect(route('student.practice.result', ['session' => $session->id]));

        $session->refresh();
        $this->assertEquals(100.0, (float) $session->score);

        // Check the graded responses in the session
        $this->assertIsArray($session->responses);
        $this->assertArrayHasKey((string) $q->id, $session->responses);
        $result = $session->responses[(string) $q->id];
        $this->assertTrue((bool) $result['is_correct']);
        $this->assertEquals($displayedKeyForC, $result['user_answer']);
        $this->assertEquals($displayedKeyForC, $result['correct_answer']);
        $this->assertStringContainsString('300,000 km/s', $result['user_answer_text']);
        $this->assertStringContainsString('300,000 km/s', $result['correct_answer_text']);
    }

    public function test_student_history_calculates_score_percentage_accurately(): void
    {
        $student = $this->createStudent();

        $q = QuestionBank::create([
            'lecturer_id' => $student->id,
            'created_by' => $student->id,
            'topic' => 'Chemistry',
            'question_text' => 'What is H2O?',
            'question_type' => 'mcq',
            'difficulty' => 'easy',
            'correct_answer' => 'A',
            'is_active' => true,
        ]);

        // Create completed practice session with score 85%
        UserPracticeSession::create([
            'user_id' => $student->id,
            'topic' => 'Chemistry',
            'question_type' => 'mcq',
            'questions' => [$q->id],
            'questions_count' => 1,
            'score' => 85.0,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'options_order' => [],
        ]);

        $response = $this->actingAs($student)->get(route('student.history'));
        $response->assertOk();

        // Check customSessions and progressData analytics
        $sessions = $response->viewData('page')['props']['customSessions'];
        $this->assertNotEmpty($sessions);
        $this->assertEquals(85.0, (float) $sessions[0]['score']);

        $progressData = $response->viewData('page')['props']['analytics']['progressData'];
        $this->assertNotEmpty($progressData);
        $this->assertEquals(85.0, (float) $progressData[0]['score']);
    }

    public function test_paystack_webhook_signature_verification_and_idempotent_crediting(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret_sample_key']);

        $student = $this->createStudent(freeQuestions: 0, tokens: 50);

        // Create a pending transaction for 50 tokens (50 NGN base + 2% charge = 51 NGN => 5100 kobo)
        $ref = 'ngx_' . uniqid() . '_' . time();
        $tx = Transaction::create([
            'user_id' => $student->id,
            'reference' => $ref,
            'amount' => 50,
            'gateway' => 'paystack',
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => $ref,
                'status' => 'success',
                'amount' => 5100, // 51 NGN in kobo (50 * 1.02)
            ],
        ]);

        // 1. Invalid signature should return 400
        $invalidResp = $this->call(
            'POST',
            route('webhook.paystack'),
            [],
            [],
            [],
            ['HTTP_X-PAYSTACK-SIGNATURE' => 'invalid_signature_hash'],
            $payload
        );
        $invalidResp->assertStatus(400);

        // 2. Valid signature should process and credit tokens
        $validSignature = hash_hmac('sha512', $payload, 'sk_test_secret_sample_key');
        $validResp = $this->call(
            'POST',
            route('webhook.paystack'),
            [],
            [],
            [],
            ['HTTP_X-PAYSTACK-SIGNATURE' => $validSignature],
            $payload
        );
        $validResp->assertOk();

        $student->refresh();
        $this->assertEquals(100, $student->tokens); // 50 initial + 50 purchased = 100

        $tx->refresh();
        $this->assertEquals('success', $tx->status);

        // 3. Duplicate webhook call should be idempotent and not double-credit
        $duplicateResp = $this->call(
            'POST',
            route('webhook.paystack'),
            [],
            [],
            [],
            ['HTTP_X-PAYSTACK-SIGNATURE' => $validSignature],
            $payload
        );
        $duplicateResp->assertOk();

        $student->refresh();
        $this->assertEquals(100, $student->tokens); // Still 100, not 150!
    }

    public function test_theory_questions_display_correct_answer_from_model_answer_and_grade_fairly(): void
    {
        $student = $this->createStudent(freeQuestions: 10, tokens: 10);

        // Create a theory question where correct_answer is null and model_answer is populated
        $q = QuestionBank::create([
            'lecturer_id' => $student->id,
            'created_by' => $student->id,
            'topic' => 'Typing Skills',
            'question_text' => 'What does WPM stand for and why is it important?',
            'question_type' => 'theory',
            'difficulty' => 'medium',
            'correct_answer' => null,
            'model_answer' => 'Words Per Minute (WPM) measures typing speed and typing efficiency.',
            'marking_scheme' => 'Mentions Words Per Minute and typing speed/efficiency.',
            'suggested_marks' => 1,
            'is_active' => true,
        ]);

        $session = UserPracticeSession::create([
            'user_id' => $student->id,
            'topic' => 'Typing Skills',
            'questions' => [$q->id],
        ]);

        // Submit answer with keywords 'words per minute'
        $submitResponse = $this->actingAs($student)
            ->postJson(route('student.practice.submit', ['session' => $session->id]), [
                'responses' => [
                    $q->id => 'words per minute',
                ],
            ]);

        $submitResponse->assertOk();
        $sessionData = $submitResponse->json('practiceSession');
        $this->assertNotNull($sessionData);

        $graded = $sessionData['responses'][(string) $q->id] ?? null;
        $this->assertNotNull($graded);

        // Verify correct_answer and correct_answer_text are NOT null or empty
        $this->assertEquals('Words Per Minute (WPM) measures typing speed and typing efficiency.', $graded['correct_answer']);
        $this->assertEquals('Words Per Minute (WPM) measures typing speed and typing efficiency.', $graded['correct_answer_text']);
        $this->assertEquals('words per minute', $graded['user_answer']);

        // Verify it was fairly evaluated with marks awarded (local keyword matching)
        $this->assertGreaterThanOrEqual(0.5, (float) $graded['marks']);
        $this->assertNotEmpty($graded['ai_feedback']);

        // Verify the questions array in the response has correct_option and model_answer populated
        $questionsInResponse = $submitResponse->json('questions');
        $this->assertNotEmpty($questionsInResponse);
        $this->assertEquals(
            'Words Per Minute (WPM) measures typing speed and typing efficiency.',
            $questionsInResponse[0]['correct_option']
        );
        $this->assertEquals(
            'Words Per Minute (WPM) measures typing speed and typing efficiency.',
            $questionsInResponse[0]['model_answer']
        );

        // Verify Inertia result view also loads it cleanly
        $session->refresh();
        $resultResponse = $this->actingAs($student)
            ->get(route('student.practice.result', ['session' => $session->id]));
        $resultResponse->assertOk();
    }
}
