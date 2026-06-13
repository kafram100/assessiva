<?php

namespace Tests\Unit;

use App\Support\StudentFeedbackPresenter;
use PHPUnit\Framework\TestCase;

class StudentFeedbackPresenterTest extends TestCase
{
    public function test_merge_feedback_deduplicates_similar_text(): void
    {
        $reason = 'The student did not provide any relevant information about the topic.';
        $feedback = 'The student did not provide relevant information on the topic.';

        $merged = StudentFeedbackPresenter::mergeFeedbackTexts($reason, $feedback);

        $this->assertNotNull($merged);
        $this->assertSame($merged, StudentFeedbackPresenter::mergeFeedbackTexts($feedback, $reason));
        $this->assertLessThanOrEqual(320, mb_strlen($merged));
        $this->assertLessThan(mb_strlen($reason) + mb_strlen($feedback), mb_strlen($merged) + 20);
    }

    public function test_merge_feedback_keeps_distinct_additions(): void
    {
        $reason = 'Coverage was limited to basic definitions only.';
        $feedback = 'Try linking each definition to a concrete example from the scenario.';

        $merged = StudentFeedbackPresenter::mergeFeedbackTexts($reason, $feedback);

        $this->assertStringContainsString('definitions', $merged);
        $this->assertStringContainsString('concrete example', $merged);
    }

    public function test_understanding_level_blank_answer(): void
    {
        $level = StudentFeedbackPresenter::understandingLevel(
            'unanswered',
            '',
            [],
            ['logic'],
            0.0,
            0.0,
            5.0,
        );

        $this->assertSame('Unanswered', $level);
    }

    public function test_understanding_level_insufficient_response(): void
    {
        $level = StudentFeedbackPresenter::understandingLevel(
            'incorrect',
            "I don't know",
            [],
            ['pseudocode'],
            0.0,
            0.0,
            5.0,
        );

        $this->assertSame('Insufficient response', $level);
    }

    public function test_understanding_level_off_topic(): void
    {
        $level = StudentFeedbackPresenter::understandingLevel(
            'incorrect',
            'Football is a popular sport.',
            [],
            ['algorithm'],
            0.0,
            0.0,
            5.0,
        );

        $this->assertSame('Off-topic response', $level);
    }

    public function test_understanding_level_partial(): void
    {
        $level = StudentFeedbackPresenter::understandingLevel(
            'partial',
            'Pseudocode helps plan logic before coding.',
            ['pseudocode'],
            ['modularity'],
            0.5,
            2.5,
            5.0,
        );

        $this->assertSame('Partial understanding', $level);
    }

    public function test_understanding_level_strong(): void
    {
        $level = StudentFeedbackPresenter::understandingLevel(
            'correct',
            'Detailed correct answer.',
            ['pseudocode', 'planning'],
            [],
            0.95,
            5.0,
            5.0,
        );

        $this->assertSame('Strong understanding', $level);
    }

    public function test_group_concepts_into_themes(): void
    {
        $themes = StudentFeedbackPresenter::groupConceptsIntoThemes([
            'logic representation',
            'pre-coding outline',
            'pseudocode simplification',
            'python imports',
        ]);

        $this->assertNotEmpty($themes);
        $themeNames = array_column($themes, 'theme');
        $this->assertContains('Algorithm design', $themeNames);
        $this->assertContains('Python & modularity', $themeNames);
    }

    public function test_revision_focus_is_concise(): void
    {
        $themes = StudentFeedbackPresenter::groupConceptsIntoThemes([
            'logic representation',
            'pre-coding outline',
        ]);

        $tip = StudentFeedbackPresenter::revisionFocus(
            ['logic representation', 'pre-coding outline'],
            $themes,
            'Limited planning detail.',
        );

        $this->assertNotNull($tip);
        $this->assertStringNotContainsString('Review: logic representation, pre-coding outline', $tip);
        $this->assertLessThan(180, mb_strlen($tip));
    }

    public function test_aggregate_learning_themes(): void
    {
        $aggregated = StudentFeedbackPresenter::aggregateLearningThemes(
            ['pseudocode', 'planning'],
            ['python syntax'],
        );

        $this->assertArrayHasKey('to_strengthen', $aggregated);
        $this->assertArrayHasKey('demonstrated', $aggregated);
        $this->assertNotEmpty($aggregated['to_strengthen']);
        $this->assertNotEmpty($aggregated['demonstrated']);
    }
}
