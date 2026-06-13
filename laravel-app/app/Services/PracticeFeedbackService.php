<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class PracticeFeedbackService
{
    public function __construct(private readonly AiService $ai) {}

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function generate(array $body): array
    {
        try {
            /** @var array<string, mixed> */
            return $this->ai->postJson('generate-practice-feedback', $body);
        } catch (RequestException $e) {
            Log::warning('Practice feedback AI request failed', [
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Practice feedback AI error', [
                'message' => $e->getMessage(),
            ]);
        }

        return $this->fallbackPayload($body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function fallbackPayload(array $body): array
    {
        $weak = $body['weak_topics'] ?? [];
        $weak = is_array($weak) ? $weak : [];

        return [
            'summary' => $weak !== []
                ? 'Review the topics you missed and try a few more targeted questions when you are ready.'
                : 'Solid work. Keep practicing to stay sharp before your official assessments.',
            'recommended_topics' => array_values($weak),
            'revision_tips' => [
                'Break study sessions into short focused blocks.',
                'Explain each concept out loud or teach a friend — it reveals gaps quickly.',
            ],
            'recommended_quizzes' => [],
            'motivation_message' => 'Every practice session builds confidence. Keep going.',
        ];
    }
}
