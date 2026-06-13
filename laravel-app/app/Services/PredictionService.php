<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\PredictionReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    public function __construct(
        private readonly AiService $ai,
        private readonly LearningTwinService $learningTwinService,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function buildStudentData(User $lecturer, string $studentIndex, ?int $courseId): array
    {
        $payload = $this->learningTwinService->buildPayload($lecturer, $studentIndex, $courseId);
        $academic = $payload['academic_data'];
        $attempts = $this->attemptsCollection($lecturer, ExamAllowedIndex::normalize($studentIndex), $courseId);

        $quizPercents = [];
        $assignPercents = [];
        foreach ($attempts as $a) {
            $ex = $a->exam;
            $type = strtolower((string) ($ex?->exam_type ?? ''));
            $tm = (int) $a->total_marks;
            $pct = $tm > 0 ? ((float) $a->score / $tm) * 100 : 0.0;
            if (str_contains($type, 'quiz')) {
                $quizPercents[] = $pct;
            } else {
                $assignPercents[] = $pct;
            }
        }

        $avg = (int) ($academic['average_percent'] ?? 0);
        $quizAvg = $quizPercents !== []
            ? (int) round(array_sum($quizPercents) / count($quizPercents))
            : $avg;
        $assignAvg = $assignPercents !== []
            ? (int) round(array_sum($assignPercents) / count($assignPercents))
            : $avg;

        $attendanceProxy = min(100, max(15, (int) round(
            ($avg * 0.55) + ((int) ($academic['engagement_index'] ?? 50) * 0.45)
        )));

        return [
            'attendance' => $attendanceProxy,
            'quiz_average' => $quizAvg,
            'assignment_average' => $assignAvg,
            'engagement' => (int) ($academic['engagement_index'] ?? 50),
            'previous_failures' => (int) ($academic['below_fifty_percent_count'] ?? 0),
            'missed_assessments' => 0,
        ];
    }

    public function generateAndStore(User $lecturer, string $studentIndex, ?int $courseId): PredictionReport
    {
        $idx = ExamAllowedIndex::normalize($studentIndex);
        $studentData = $this->buildStudentData($lecturer, $idx, $courseId);

        try {
            $data = $this->ai->postJson('predict-academic-risk', [
                'student_data' => $studentData,
            ]);
        } catch (\Throwable $e) {
            Log::error('Prediction AI error', ['message' => $e->getMessage()]);
            throw $e;
        }

        if (! ($data['success'] ?? false)) {
            throw new \RuntimeException((string) ($data['message'] ?? 'AI service returned failure.'));
        }

        $attrs = [
            'lecturer_user_id' => $lecturer->id,
            'student_index' => $idx,
            'course_id' => $courseId,
            'student_data_snapshot' => $studentData,
            'failure_probability' => isset($data['failure_probability']) ? (int) $data['failure_probability'] : null,
            'dropout_risk' => isset($data['dropout_risk']) ? (string) $data['dropout_risk'] : null,
            'burnout_risk' => isset($data['burnout_risk']) ? (string) $data['burnout_risk'] : null,
            'predicted_grade' => isset($data['predicted_grade']) ? (string) $data['predicted_grade'] : null,
            'intervention_urgency' => isset($data['intervention_urgency']) ? (string) $data['intervention_urgency'] : null,
            'recommended_interventions' => is_array($data['recommended_interventions'] ?? null) ? $data['recommended_interventions'] : [],
            'advisory_rationale' => isset($data['rationale']) ? (string) $data['rationale'] : null,
            'raw_ai_response' => $data,
            'generated_at' => now(),
        ];

        return DB::transaction(fn (): PredictionReport => PredictionReport::query()->create($attrs));
    }

    public function latestForStudent(User $lecturer, string $studentIndex, ?int $courseId): ?PredictionReport
    {
        $idx = ExamAllowedIndex::normalize($studentIndex);

        return PredictionReport::query()
            ->where('lecturer_user_id', $lecturer->id)
            ->where('student_index', $idx)
            ->when($courseId !== null, fn ($q) => $q->where('course_id', $courseId))
            ->when($courseId === null, fn ($q) => $q->whereNull('course_id'))
            ->orderByDesc('id')
            ->first();
    }

    public function latestForAttempt(Attempt $attempt): ?PredictionReport
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            return null;
        }
        $lecturer = User::query()->find($exam->user_id);
        if ($lecturer === null) {
            return null;
        }

        return $this->latestForStudent($lecturer, (string) $attempt->student_index, $exam->course_id !== null ? (int) $exam->course_id : null);
    }

    private function attemptsCollection(User $lecturer, string $normalizedIndex, ?int $courseId)
    {
        $q = Attempt::query()
            ->whereHas('exam', function ($q) use ($lecturer, $courseId): void {
                $q->where('user_id', $lecturer->id);
                if ($courseId !== null) {
                    $q->where('course_id', $courseId);
                }
            })
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalizedIndex])
            ->where('status', 'completed')
            ->with('exam');

        return $q->get();
    }
}
