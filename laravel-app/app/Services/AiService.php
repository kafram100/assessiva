<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public static function fromConfig(): self
    {
        return new self;
    }

    /**
     * POST /generate-questions on the AI microservice.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function generateQuestions(array $payload): array
    {
        try {
            return $this->postJson('generate-questions', $payload);
        } catch (RequestException $e) {
            Log::warning('AI generate-questions request failed', [
                'message' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::warning('AI generate-questions failed', [
                'message' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    public function baseUrl(): string
    {
        $url = (string) config('services.ai.url', '');

        return rtrim($url, '/');
    }

    public function timeoutSeconds(): int
    {
        return max(5, (int) config('services.ai.timeout', 120));
    }

    /**
     * POST JSON to the AI microservice (FastAPI).
     *
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function postJson(string $path, array $body): array
    {
        $base = $this->baseUrl();
        if ($base === '') {
            throw new \RuntimeException('AI_SERVICE_URL is not configured.');
        }

        $url = $base.'/'.ltrim($path, '/');

        $response = Http::timeout($this->timeoutSeconds())
            ->connectTimeout(5)
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        if (! $response->successful()) {
            Log::warning('AI service HTTP error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        $response->throw();

        /** @var array<string, mixed> */
        return $response->json() ?? [];
    }

    /**
     * POST JSON to the AI microservice without throwing on HTTP errors (for UX messaging).
     *
     * @return array{ok: bool, data?: array<string, mixed>|null, error?: string|null}
     */
    public function postJsonOrError(string $path, array $body): array
    {
        $base = $this->baseUrl();
        if ($base === '') {
            return ['ok' => false, 'error' => 'AI_SERVICE_URL is not configured.', 'data' => null];
        }

        $url = $base.'/'.ltrim($path, '/');

        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->asJson()
                ->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('AI service connection failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage(), 'data' => null];
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if (! $response->successful()) {
            $detail = is_array($json) ? ($json['detail'] ?? $json['message'] ?? $json['error'] ?? null) : null;
            if (is_array($detail)) {
                $detail = json_encode($detail);
            }
            $msg = is_string($detail) && $detail !== ''
                ? $detail
                : (is_string($response->body()) ? $response->body() : 'Request failed');

            Log::warning('AI service HTTP error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['ok' => false, 'error' => (string) $msg, 'data' => is_array($json) ? $json : null];
        }

        return ['ok' => true, 'data' => is_array($json) ? $json : [], 'error' => null];
    }
}
