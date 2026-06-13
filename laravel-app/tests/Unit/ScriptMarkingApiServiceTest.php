<?php

namespace Tests\Unit;

use App\Data\AnswerMarkingEvaluation;
use App\Models\Question;
use App\Services\ScriptMarkingApiService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScriptMarkingApiServiceTest extends TestCase
{
    #[Test]
    public function should_mark_by_api_with_external_driver_requires_only_service_url_not_openai_key(): void
    {
        config([
            'marking.api.enabled' => true,
            'marking.api.use_for_fill_in' => true,
            'marking.api.external_url' => 'http://127.0.0.1:8088',
            'marking.driver' => 'external',
            'marking.openai.key' => '',
            'marking.gemini.key' => '',
        ]);

        $svc = app(ScriptMarkingApiService::class);

        $this->assertTrue($svc->shouldMarkByApi('theory'));
        $this->assertTrue($svc->shouldMarkByApi('short_answer'));
        $this->assertSame('http://127.0.0.1:8088/mark-answer', $svc->resolveExternalMarkUrl());
    }

    #[Test]
    public function should_mark_by_api_false_when_external_url_missing(): void
    {
        config([
            'marking.api.enabled' => true,
            'marking.api.external_url' => '',
            'marking.api.url' => '',
            'marking.driver' => 'external',
            'services.ai.url' => '',
        ]);

        $svc = app(ScriptMarkingApiService::class);

        $this->assertFalse($svc->shouldMarkByApi('theory'));
        $this->assertNull($svc->resolveExternalMarkUrl());
    }

    #[Test]
    public function openai_driver_still_requires_openai_key(): void
    {
        config([
            'marking.api.enabled' => true,
            'marking.driver' => 'openai',
            'marking.openai.key' => '',
        ]);

        $svc = app(ScriptMarkingApiService::class);

        $this->assertFalse($svc->shouldMarkByApi('theory'));
    }

    #[Test]
    public function evaluate_answer_posts_to_fastapi_mark_answer_and_maps_response(): void
    {
        config([
            'marking.api.enabled' => true,
            'marking.api.external_url' => 'https://fastapi.example.com',
            'marking.api.log_pipeline' => false,
            'marking.driver' => 'external',
        ]);

        Http::fake([
            'https://fastapi.example.com/mark-answer' => Http::response([
                'marks_awarded' => 1.5,
                'confidence' => 0.82,
                'evaluation_status' => 'partial',
                'detected_concepts' => ['modularity'],
                'missing_concepts' => ['reuse'],
                'concept_coverage' => 0.75,
                'key_concepts_rubric' => ['modularity', 'reuse'],
                'reason' => 'Partial understanding.',
                'feedback' => 'Expand on reuse.',
            ], 200),
        ]);

        $question = new Question([
            'question_text' => 'What is modularity?',
            'correct_option' => 'Organizing code into modules.',
            'marks' => 2,
        ]);
        $question->id = 99;
        $question->q_type = 'theory';

        $ev = app(ScriptMarkingApiService::class)->evaluateAnswer(
            $question,
            'Modules help organize code.',
            'theory',
        );

        $this->assertInstanceOf(AnswerMarkingEvaluation::class, $ev);
        $this->assertSame(1.5, $ev->marksAwarded);
        $this->assertSame('partial', $ev->evaluationStatus);
        $this->assertSame(['modularity'], $ev->detectedConcepts);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://fastapi.example.com/mark-answer'
                && ($data['student_answer'] ?? '') === 'Modules help organize code.'
                && ($data['question_type'] ?? '') === 'theory'
                && ($data['max_marks'] ?? 0) === 2;
        });
    }
}
