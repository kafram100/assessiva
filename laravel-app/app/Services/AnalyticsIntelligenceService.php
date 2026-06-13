<?php

namespace App\Services;

use App\Models\AnalyticsReport;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsIntelligenceService
{
    public function __construct(private readonly AiService $ai) {}

    /**
     * @return array{course: string, department: string|null, students: list<array<string, mixed>>}
     */
    public function buildPayload(User $owner, Course $course): array
    {
        $allLecturers = $owner->isAdmin() || $owner->isHod();

        $examIds = Exam::query()
            ->where('course_id', $course->id)
            ->when(! $allLecturers, fn ($q) => $q->where('user_id', $owner->id))
            ->pluck('id');

        if ($examIds->isEmpty()) {
            throw new \RuntimeException('No exams found for this course in the selected scope.');
        }

        $attempts = Attempt::query()
            ->whereIn('exam_id', $examIds)
            ->where('status', 'completed')
            ->withCount('cheatEvents')
            ->get();

        $byIndex = [];
        foreach ($attempts as $a) {
            $idx = ExamAllowedIndex::normalize((string) $a->student_index);
            if ($idx === '') {
                continue;
            }
            if (! isset($byIndex[$idx])) {
                $byIndex[$idx] = [];
            }
            $byIndex[$idx][] = $a;
        }

        $students = [];
        $sid = 1;
        foreach ($byIndex as $idx => $group) {
            $percents = [];
            $engagement = [];
            foreach ($group as $a) {
                $tm = (int) $a->total_marks;
                if ($tm > 0) {
                    $percents[] = ((float) $a->score / $tm) * 100;
                }
                $flags = (int) ($a->cheat_events_count ?? 0);
                $engagement[] = max(0, 100 - min(100, $flags * 20));
            }
            $avg = $percents !== [] ? array_sum($percents) / count($percents) : 0.0;
            $eng = $engagement !== [] ? array_sum($engagement) / count($engagement) : 50.0;
            $students[] = [
                'student_id' => $sid++,
                'student_index' => $idx,
                'average_score' => (int) round($avg),
                'attendance' => min(100, max(20, (int) round($avg * 0.65 + $eng * 0.35))),
                'quiz_average' => (int) round($avg),
                'assignment_average' => (int) round($avg * 0.95),
                'engagement_score' => (int) round($eng),
            ];
        }

        if ($students === []) {
            throw new \RuntimeException('No completed student attempts available for analytics.');
        }

        $course->loadMissing('department');
        $dept = $course->department?->name;

        return [
            'course' => $course->name,
            'department' => $dept,
            'students' => $students,
        ];
    }

    public function generateAndStore(User $owner, Course $course, string $scope = 'course'): AnalyticsReport
    {
        $payload = $this->buildPayload($owner, $course);

        try {
            $data = $this->ai->postJson('analyze-academic-intelligence', $payload);
        } catch (\Throwable $e) {
            Log::error('Analytics AI error', ['message' => $e->getMessage()]);
            throw $e;
        }

        if (! ($data['success'] ?? false)) {
            throw new \RuntimeException((string) ($data['message'] ?? 'AI service returned failure.'));
        }

        $contextKey = 'course:'.$course->id.':'.($owner->isAdmin() ? 'all' : 'own');

        $attrs = [
            'owner_user_id' => $owner->id,
            'scope' => $scope,
            'course_id' => $course->id,
            'course_name' => $course->name,
            'department_name' => $payload['department'],
            'context_key' => $contextKey,
            'cohort_students_snapshot' => $payload['students'],
            'course_health_score' => isset($data['course_health_score']) ? (int) $data['course_health_score'] : null,
            'at_risk_students' => is_array($data['at_risk_students'] ?? null) ? $data['at_risk_students'] : [],
            'weak_topics' => is_array($data['weak_topics'] ?? null) ? $data['weak_topics'] : [],
            'performance_clusters' => is_array($data['performance_clusters'] ?? null) ? $data['performance_clusters'] : [],
            'interventions' => is_array($data['interventions'] ?? null) ? $data['interventions'] : [],
            'summary' => is_array($data['summary'] ?? null) ? $data['summary'] : [],
            'department_insights' => is_array($data['department_insights'] ?? null) ? $data['department_insights'] : [],
            'cohort_trends' => is_array($data['cohort_trends'] ?? null) ? $data['cohort_trends'] : [],
            'raw_ai_response' => $data,
            'generated_at' => now(),
        ];

        return DB::transaction(fn (): AnalyticsReport => AnalyticsReport::query()->create($attrs));
    }

    public function latestForCourse(User $owner, int $courseId): ?AnalyticsReport
    {
        return AnalyticsReport::query()
            ->where('owner_user_id', $owner->id)
            ->where('course_id', $courseId)
            ->orderByDesc('id')
            ->first();
    }

    public function latestInstitutional(User $owner): ?AnalyticsReport
    {
        return AnalyticsReport::query()
            ->where('owner_user_id', $owner->id)
            ->where('scope', 'institutional')
            ->orderByDesc('id')
            ->first();
    }
}
