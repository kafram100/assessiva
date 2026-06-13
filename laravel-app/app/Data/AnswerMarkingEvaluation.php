<?php

namespace App\Data;

/**
 * Structured result from AI (or script) marking for one answer.
 */
final readonly class AnswerMarkingEvaluation
{
    /**
     * @param  list<string>  $detectedConcepts
     * @param  list<string>  $missingConcepts
     * @param  list<string>  $rubricConcepts  Key concepts the model used for coverage (theory)
     */
    public function __construct(
        public float $marksAwarded,
        public ?float $confidence,
        public ?string $evaluationStatus,
        public array $detectedConcepts,
        public array $missingConcepts,
        public ?string $reason,
        public ?string $feedback,
        public ?string $rawModelResponse = null,
        public ?float $conceptCoverage = null,
        public array $rubricConcepts = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, int $maxMarks, ?string $raw = null): self
    {
        $marks = isset($data['marks_awarded']) ? (float) $data['marks_awarded'] : 0.0;
        $marks = max(0.0, min((float) $maxMarks, $marks));

        $conf = isset($data['confidence']) ? (float) $data['confidence'] : null;
        if ($conf !== null) {
            $conf = max(0.0, min(1.0, $conf));
        }

        $detected = $data['detected_concepts'] ?? [];
        $missing = $data['missing_concepts'] ?? [];
        if (! is_array($detected)) {
            $detected = [];
        }
        if (! is_array($missing)) {
            $missing = [];
        }
        $detected = array_values(array_filter(array_map('strval', $detected)));
        $missing = array_values(array_filter(array_map('strval', $missing)));

        $status = isset($data['evaluation_status']) ? (string) $data['evaluation_status'] : null;

        $coverage = null;
        if (isset($data['concept_coverage'])) {
            $coverage = max(0.0, min(1.0, (float) $data['concept_coverage']));
        }

        $rubric = $data['key_concepts_rubric'] ?? $data['rubric_concepts'] ?? $data['key_concepts_checked'] ?? [];
        if (! is_array($rubric)) {
            $rubric = [];
        }
        $rubric = array_values(array_filter(array_map('strval', $rubric)));

        return new self(
            marksAwarded: $marks,
            confidence: $conf,
            evaluationStatus: $status,
            detectedConcepts: $detected,
            missingConcepts: $missing,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            feedback: isset($data['feedback']) ? (string) $data['feedback'] : null,
            rawModelResponse: $raw,
            conceptCoverage: $coverage,
            rubricConcepts: $rubric,
        );
    }

    /**
     * Copy with new marks / status / reason (e.g. fairness guard).
     */
    public function withAdjustedMarks(
        float $marksAwarded,
        ?string $evaluationStatus,
        ?string $reason,
    ): self {
        return new self(
            marksAwarded: $marksAwarded,
            confidence: $this->confidence,
            evaluationStatus: $evaluationStatus ?? $this->evaluationStatus,
            detectedConcepts: $this->detectedConcepts,
            missingConcepts: $this->missingConcepts,
            reason: $reason ?? $this->reason,
            feedback: $this->feedback,
            rawModelResponse: $this->rawModelResponse,
            conceptCoverage: $this->conceptCoverage,
            rubricConcepts: $this->rubricConcepts,
        );
    }
}
