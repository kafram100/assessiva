<?php

namespace App\Services\Concerns;

use App\Models\AnalyticsReport;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\LearningTwin;
use App\Models\PredictionReport;
use App\Models\PracticeQuizAttempt;
use App\Models\RevisionPlan;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates existing AI intelligence rows (twins, predictions, analytics, revision, practice)
 * for a single department - no new AI calls.
 */
trait RollsUpDepartmentIntelligence
{
    /**
     * @return array<string, mixed>
     */
    protected function rollupDepartment(int $departmentId, ?string $departmentName = null): array
    {
        $courseIds = Course::query()->where('department_id', $departmentId)->pluck('id');
        $lecturerIds = User::query()
            ->where('department_id', $departmentId)
            ->where('role', UserRole::SuperAdmin)
            ->pluck('id');

        if ($courseIds->isEmpty() && $lecturerIds->isEmpty()) {
            return $this->emptyDepartmentPayload($departmentName ?? 'Department');
        }

        $latestAnalytics = $this->latestAnalyticsReportsForCourses($courseIds);
        $twins = $this->scopedLearningTwins($courseIds, $lecturerIds);
        $predictions = $this->scopedPredictions($courseIds, $lecturerIds);
        $revisionPlans = $this->scopedRevisionPlans($courseIds);
        $practice = $this->scopedPracticeStats($lecturerIds);
        $attemptStats = $this->scopedAttemptStats($courseIds, $lecturerIds);

        $health = $this->computeDepartmentHealth($latestAnalytics, $twins, $attemptStats);
        $weakTopics = $this->aggregateWeakTopics($latestAnalytics, $twins, $revisionPlans);
        $weakTopics = $this->mergePracticeWeakTopics($weakTopics, $practice);
        $atRisk = $this->buildAtRiskPanel($latestAnalytics, $predictions);
        $weakestCourses = $this->weakestCourses($latestAnalytics);
        $fairness = $this->fairnessFromAnalytics($latestAnalytics);
        $lecturerSnapshots = $this->lecturerSupportSnapshots($lecturerIds, $courseIds, $latestAnalytics);
        $practiceTrend = $this->practiceWeeklyTrendPortable($lecturerIds);
        $recommendations = $this->mergeRecommendations($latestAnalytics, $twins, $predictions);

        return [
            'department_name' => $departmentName,
            'academic_health' => $health,
            'at_risk' => $atRisk,
            'weakest_courses' => $weakestCourses,
            'weak_topics' => $weakTopics,
            'fairness' => $fairness,
            'lecturer_snapshots' => $lecturerSnapshots,
            'practice_hub' => $practice,
            'practice_trend' => $practiceTrend,
            'performance' => $attemptStats,
            'counts' => [
                'learning_twins' => $twins->count(),
                'prediction_reports' => $predictions->count(),
                'analytics_reports_used' => $latestAnalytics->count(),
                'revision_plans' => $revisionPlans->count(),
                'practice_attempts' => $practice['total_attempts'] ?? 0,
            ],
            'ai_recommendations' => $recommendations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyDepartmentPayload(string $label): array
    {
        return [
            'department_name' => $label,
            'academic_health' => ['score' => null, 'label' => 'No data yet'],
            'at_risk' => ['high' => [], 'medium' => [], 'note' => 'No cohort intelligence stored yet.'],
            'weakest_courses' => [],
            'weak_topics' => [],
            'fairness' => ['proxy_score' => null, 'trend' => [], 'note' => 'Generate course analytics to populate fairness-adjacent signals.'],
            'lecturer_snapshots' => [],
            'practice_hub' => ['top_quizzes' => [], 'total_attempts' => 0, 'weak_topic_hits' => []],
            'practice_trend' => [],
            'performance' => ['pass_rate_percent' => null, 'attempts' => 0],
            'counts' => [
                'learning_twins' => 0,
                'prediction_reports' => 0,
                'analytics_reports_used' => 0,
                'revision_plans' => 0,
                'practice_attempts' => 0,
            ],
            'ai_recommendations' => [
                'Encourage lecturers to run cohort analytics and Learning Twin updates so this dashboard can aggregate real signals.',
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, AnalyticsReport>
     */
    private function latestAnalyticsReportsForCourses(Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        $ids = AnalyticsReport::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return AnalyticsReport::query()->whereIn('id', $ids)->get();
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @param  Collection<int, int>  $lecturerIds
     * @return Collection<int, LearningTwin>
     */
    private function scopedLearningTwins(Collection $courseIds, Collection $lecturerIds): Collection
    {
        return LearningTwin::query()
            ->where(function ($q) use ($courseIds, $lecturerIds): void {
                if ($courseIds->isNotEmpty()) {
                    $q->whereIn('course_id', $courseIds);
                }
                if ($lecturerIds->isNotEmpty()) {
                    if ($courseIds->isNotEmpty()) {
                        $q->orWhereIn('lecturer_user_id', $lecturerIds);
                    } else {
                        $q->whereIn('lecturer_user_id', $lecturerIds);
                    }
                }
            })
            ->orderByDesc('generated_at')
            ->limit(500)
            ->get();
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @param  Collection<int, int>  $lecturerIds
     * @return Collection<int, PredictionReport>
     */
    private function scopedPredictions(Collection $courseIds, Collection $lecturerIds): Collection
    {
        return PredictionReport::query()
            ->where(function ($q) use ($courseIds, $lecturerIds): void {
                if ($courseIds->isNotEmpty()) {
                    $q->whereIn('course_id', $courseIds);
                }
                if ($lecturerIds->isNotEmpty()) {
                    if ($courseIds->isNotEmpty()) {
                        $q->orWhereIn('lecturer_user_id', $lecturerIds);
                    } else {
                        $q->whereIn('lecturer_user_id', $lecturerIds);
                    }
                }
            })
            ->orderByDesc('generated_at')
            ->limit(400)
            ->get();
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, RevisionPlan>
     */
    private function scopedRevisionPlans(Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return RevisionPlan::query()
            ->whereHas('exam', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->orderByDesc('generated_at')
            ->limit(300)
            ->get();
    }

    /**
     * @param  Collection<int, int>  $lecturerIds
     * @return array<string, mixed>
     */
    private function scopedPracticeStats(Collection $lecturerIds): array
    {
        if ($lecturerIds->isEmpty()) {
            return ['top_quizzes' => [], 'total_attempts' => 0, 'weak_topic_hits' => []];
        }

        $total = PracticeQuizAttempt::query()
            ->whereHas('quiz', fn ($q) => $q->whereIn('user_id', $lecturerIds)->where('practice_mode', true))
            ->whereNotNull('submitted_at')
            ->count();

        $top = PracticeQuizAttempt::query()
            ->select('exams.title', 'exams.course_name', DB::raw('COUNT(*) as c'))
            ->join('exams', 'exams.id', '=', 'practice_quiz_attempts.quiz_id')
            ->whereIn('exams.user_id', $lecturerIds)
            ->where('exams.practice_mode', true)
            ->whereNotNull('practice_quiz_attempts.submitted_at')
            ->groupBy('exams.title', 'exams.course_name')
            ->orderByDesc('c')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'title' => $row->title,
                'course' => $row->course_name,
                'attempts' => (int) $row->c,
            ])
            ->all();

        $weakHits = [];
        $rows = PracticeQuizAttempt::query()
            ->whereHas('quiz', fn ($q) => $q->whereIn('user_id', $lecturerIds)->where('practice_mode', true))
            ->whereNotNull('submitted_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['weak_topics']);

        foreach ($rows as $r) {
            $wt = $r->weak_topics;
            if (! is_array($wt)) {
                continue;
            }
            foreach ($wt as $t) {
                $t = trim((string) $t);
                if ($t === '') {
                    continue;
                }
                $weakHits[$t] = ($weakHits[$t] ?? 0) + 1;
            }
        }
        arsort($weakHits);

        return [
            'total_attempts' => $total,
            'top_quizzes' => $top,
            'weak_topic_hits' => collect($weakHits)->take(12)->map(fn ($c, $t) => ['topic' => $t, 'count' => $c])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @param  Collection<int, int>  $lecturerIds
     * @return array<string, mixed>
     */
    private function scopedAttemptStats(Collection $courseIds, Collection $lecturerIds): array
    {
        $q = Attempt::query()->where('status', 'completed');

        $q->where(function ($w) use ($courseIds, $lecturerIds): void {
            if ($courseIds->isNotEmpty()) {
                $w->whereHas('exam', fn ($ex) => $ex->whereIn('course_id', $courseIds));
            }
            if ($lecturerIds->isNotEmpty()) {
                if ($courseIds->isNotEmpty()) {
                    $w->orWhereHas('exam', fn ($ex) => $ex->whereIn('user_id', $lecturerIds));
                } else {
                    $w->whereHas('exam', fn ($ex) => $ex->whereIn('user_id', $lecturerIds));
                }
            }
        });

        $attempts = $q->with('exam:id,course_id,exam_type')->limit(5000)->get(['id', 'exam_id', 'score', 'total_marks']);

        if ($attempts->isEmpty()) {
            return ['pass_rate_percent' => null, 'attempts' => 0];
        }

        $pass = 0;
        foreach ($attempts as $a) {
            $tm = max(1, (int) $a->total_marks);
            if (((float) $a->score / $tm) >= 0.5) {
                $pass++;
            }
        }

        return [
            'attempts' => $attempts->count(),
            'pass_rate_percent' => (int) round(($pass / max(1, $attempts->count())) * 100),
        ];
    }

    /**
     * @param  Collection<int, AnalyticsReport>  $reports
     * @param  Collection<int, LearningTwin>  $twins
     * @param  array<string, mixed>  $attemptStats
     * @return array<string, mixed>
     */
    private function computeDepartmentHealth(Collection $reports, Collection $twins, array $attemptStats): array
    {
        $parts = [];

        foreach ($reports as $r) {
            if ($r->course_health_score !== null) {
                $parts[] = (int) $r->course_health_score;
            }
        }

        foreach ($twins as $t) {
            if ($t->academic_health_score !== null) {
                $parts[] = (int) $t->academic_health_score;
            }
        }

        if (($attemptStats['pass_rate_percent'] ?? null) !== null) {
            $parts[] = (int) $attemptStats['pass_rate_percent'];
        }

        if ($parts === []) {
            return ['score' => null, 'label' => 'Insufficient stored intelligence'];
        }

        $score = (int) round(array_sum($parts) / count($parts));
        $label = $score >= 75 ? 'Strong' : ($score >= 55 ? 'Stable' : 'Needs attention');

        return ['score' => $score, 'label' => $label];
    }

    /**
     * @param  Collection<int, AnalyticsReport>  $reports
     * @return list<array{course: string|null, score: int|null}>
     */
    private function weakestCourses(Collection $reports): array
    {
        return $reports
            ->sortBy('course_health_score')
            ->take(6)
            ->values()
            ->map(fn (AnalyticsReport $r) => [
                'course' => $r->course_name,
                'health' => $r->course_health_score,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, AnalyticsReport>  $reports
     * @param  Collection<int, LearningTwin>  $twins
     * @param  Collection<int, RevisionPlan>  $revisionPlans
     * @return list<array{topic: string, weight: int}>
     */
    private function aggregateWeakTopics(Collection $reports, Collection $twins, Collection $revisionPlans): array
    {
        $acc = [];

        foreach ($reports as $r) {
            foreach ($r->weak_topics ?? [] as $t) {
                $t = trim((string) $t);
                if ($t !== '') {
                    $acc[$t] = ($acc[$t] ?? 0) + 3;
                }
            }
        }

        foreach ($twins as $t) {
            foreach ($t->weaknesses ?? [] as $w) {
                if (is_string($w)) {
                    $s = trim($w);
                    if ($s !== '') {
                        $acc[$s] = ($acc[$s] ?? 0) + 2;
                    }
                } elseif (is_array($w) && isset($w['topic'])) {
                    $s = trim((string) $w['topic']);
                    if ($s !== '') {
                        $acc[$s] = ($acc[$s] ?? 0) + 2;
                    }
                }
            }
        }

        foreach ($revisionPlans as $p) {
            foreach ($p->weak_topics ?? [] as $t) {
                $t = trim((string) $t);
                if ($t !== '') {
                    $acc[$t] = ($acc[$t] ?? 0) + 1;
                }
            }
        }

        arsort($acc);

        return collect($acc)
            ->take(15)
            ->map(fn ($w, $t) => ['topic' => $t, 'weight' => (int) $w])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{topic: string, weight: int}>  $topics
     * @param  array<string, mixed>  $practice
     * @return list<array{topic: string, weight: int}>
     */
    private function mergePracticeWeakTopics(array $topics, array $practice): array
    {
        $acc = [];
        foreach ($topics as $row) {
            $acc[$row['topic']] = $row['weight'];
        }
        foreach ($practice['weak_topic_hits'] ?? [] as $row) {
            $t = trim((string) ($row['topic'] ?? ''));
            if ($t === '') {
                continue;
            }
            $acc[$t] = ($acc[$t] ?? 0) + (int) ($row['count'] ?? 0);
        }
        arsort($acc);

        return collect($acc)
            ->take(15)
            ->map(fn ($w, $t) => ['topic' => $t, 'weight' => (int) $w])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AnalyticsReport>  $reports
     * @param  Collection<int, PredictionReport>  $predictions
     * @return array<string, mixed>
     */
    private function buildAtRiskPanel(Collection $reports, Collection $predictions): array
    {
        $high = [];
        $medium = [];

        foreach ($reports as $r) {
            foreach ($r->at_risk_students ?? [] as $row) {
                $row = (array) $row;
                $level = strtolower((string) ($row['risk_level'] ?? $row['level'] ?? 'medium'));
                $entry = [
                    'student_index_masked' => $this->maskIndex((string) ($row['student_index'] ?? $row['index'] ?? '')),
                    'course' => $r->course_name,
                    'note' => (string) ($row['reason'] ?? $row['notes'] ?? 'Flagged in analytics intelligence'),
                ];
                if (str_contains($level, 'high') || str_contains($level, 'critical')) {
                    $high[] = $entry;
                } else {
                    $medium[] = $entry;
                }
            }
        }

        foreach ($predictions as $p) {
            $urg = strtolower((string) ($p->intervention_urgency ?? ''));
            $drop = $this->riskTierScore((string) $p->dropout_risk);
            $burn = $this->riskTierScore((string) $p->burnout_risk);
            $fail = (int) ($p->failure_probability ?? 0);
            $score = max($drop, $burn, $fail);
            $entry = [
                'student_index_masked' => $this->maskIndex((string) $p->student_index),
                'course_id' => $p->course_id,
                'dropout_risk' => $p->dropout_risk,
                'burnout_risk' => $p->burnout_risk,
                'failure_probability' => $fail,
                'urgency' => $p->intervention_urgency,
            ];
            if ($score >= 70 || str_contains($urg, 'high') || str_contains($urg, 'critical')) {
                $high[] = $entry;
            } elseif ($score >= 40 || str_contains($urg, 'medium')) {
                $medium[] = $entry;
            }
        }

        return [
            'high' => array_slice($high, 0, 25),
            'medium' => array_slice($medium, 0, 25),
            'note' => 'Signals are aggregated from stored Prediction Engine and Analytics Intelligence outputs - supportive use only.',
        ];
    }

    private function maskIndex(string $index): string
    {
        $t = trim($index);
        if (strlen($t) <= 4) {
            return '••••';
        }

        return substr($t, 0, 2).'•••'.substr($t, -2);
    }

    private function riskTierScore(string $tier): int
    {
        $t = strtolower($tier);

        return match (true) {
            str_contains($t, 'crit') => 95,
            str_contains($t, 'high') => 75,
            str_contains($t, 'med') => 50,
            str_contains($t, 'low') => 25,
            default => 40,
        };
    }

    /**
     * @param  Collection<int, AnalyticsReport>  $reports
     * @return array<string, mixed>
     */
    private function fairnessFromAnalytics(Collection $reports): array
    {
        $scores = [];
        $trend = [];

        foreach ($reports as $r) {
            if ($r->course_health_score !== null) {
                $scores[] = (int) $r->course_health_score;
            }
            $raw = $r->raw_ai_response;
            if (is_array($raw) && isset($raw['fairness_score'])) {
                $scores[] = max(0, min(100, (int) $raw['fairness_score']));
            }
            if ($r->generated_at) {
                $trend[] = [
                    'date' => $r->generated_at->format('Y-m-d'),
                    'course_health' => $r->course_health_score,
                ];
            }
        }

        $proxy = $scores !== [] ? (int) round(array_sum($scores) / count($scores)) : null;

        $flagged = $reports
            ->filter(fn (AnalyticsReport $r) => $r->course_health_score !== null && (int) $r->course_health_score < 52)
            ->map(fn (AnalyticsReport $r) => $r->course_name)
            ->filter()
            ->unique()
            ->values()
            ->take(6)
            ->all();

        return [
            'proxy_score' => $proxy,
            'courses_needing_review' => $flagged,
            'trend' => array_slice($trend, -12),
            'note' => 'Fairness-adjacent signal blended from stored cohort analytics (course health & any embedded fairness_score). Dedicated fairness persistence can extend this panel later.',
        ];
    }

    /**
     * @param  Collection<int, int>  $lecturerIds
     * @param  Collection<int, int>  $courseIds
     * @param  Collection<int, AnalyticsReport>  $latestAnalytics
     * @return list<array<string, mixed>>
     */
    private function lecturerSupportSnapshots(Collection $lecturerIds, Collection $courseIds, Collection $latestAnalytics): array
    {
        if ($lecturerIds->isEmpty()) {
            return [];
        }

        $users = User::query()->whereIn('id', $lecturerIds)->get(['id', 'name']);

        $healthByLecturer = [];
        foreach ($latestAnalytics as $rep) {
            $course = Course::query()->find($rep->course_id);
            if (! $course) {
                continue;
            }
            $lid = (int) $course->user_id;
            if (! isset($healthByLecturer[$lid])) {
                $healthByLecturer[$lid] = [];
            }
            if ($rep->course_health_score !== null) {
                $healthByLecturer[$lid][] = (int) $rep->course_health_score;
            }
        }

        $twinCounts = LearningTwin::query()
            ->selectRaw('lecturer_user_id, COUNT(*) as c')
            ->whereIn('lecturer_user_id', $lecturerIds)
            ->groupBy('lecturer_user_id')
            ->pluck('c', 'lecturer_user_id');

        $out = [];
        foreach ($users as $u) {
            $arr = $healthByLecturer[$u->id] ?? [];
            $avgHealth = $arr !== [] ? (int) round(array_sum($arr) / count($arr)) : null;
            $out[] = [
                'lecturer_id' => $u->id,
                'name' => $u->name,
                'avg_course_health' => $avgHealth,
                'twin_generations' => (int) ($twinCounts[$u->id] ?? 0),
                'tone' => $avgHealth === null ? 'neutral' : ($avgHealth >= 60 ? 'positive' : 'support'),
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, int>  $lecturerIds
     * @return list<array{week: string, count: int}>
     */
    protected function practiceWeeklyTrendPortable(Collection $lecturerIds): array
    {
        if ($lecturerIds->isEmpty()) {
            return [];
        }

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            return PracticeQuizAttempt::query()
                ->selectRaw("DATE_TRUNC('week', practice_quiz_attempts.submitted_at) as wk, COUNT(*) as c")
                ->join('exams', 'exams.id', '=', 'practice_quiz_attempts.quiz_id')
                ->whereIn('exams.user_id', $lecturerIds)
                ->where('exams.practice_mode', true)
                ->whereNotNull('practice_quiz_attempts.submitted_at')
                ->groupBy('wk')
                ->orderBy('wk')
                ->limit(12)
                ->get()
                ->map(fn ($row) => [
                    'week' => (string) $row->wk,
                    'count' => (int) $row->c,
                ])
                ->all();
        }

        return PracticeQuizAttempt::query()
            ->selectRaw('YEARWEEK(practice_quiz_attempts.submitted_at, 1) as wk, COUNT(*) as c')
            ->join('exams', 'exams.id', '=', 'practice_quiz_attempts.quiz_id')
            ->whereIn('exams.user_id', $lecturerIds)
            ->where('exams.practice_mode', true)
            ->whereNotNull('practice_quiz_attempts.submitted_at')
            ->groupBy('wk')
            ->orderBy('wk')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'week' => (string) $row->wk,
                'count' => (int) $row->c,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, AnalyticsReport>  $reports
     * @param  Collection<int, LearningTwin>  $twins
     * @param  Collection<int, PredictionReport>  $predictions
     * @return list<string>
     */
    private function mergeRecommendations(Collection $reports, Collection $twins, Collection $predictions): array
    {
        $lines = [];

        foreach ($reports as $r) {
            foreach ($r->department_insights ?? [] as $s) {
                if (is_string($s) && trim($s) !== '') {
                    $lines[] = trim($s);
                }
            }
            foreach ($r->interventions ?? [] as $s) {
                if (is_string($s) && trim($s) !== '') {
                    $lines[] = trim($s);
                }
            }
            foreach ($r->summary ?? [] as $s) {
                if (is_string($s) && trim($s) !== '') {
                    $lines[] = trim($s);
                }
            }
        }

        foreach ($twins as $t) {
            foreach ($t->recommended_actions ?? [] as $s) {
                if (is_string($s) && trim($s) !== '') {
                    $lines[] = trim($s);
                }
            }
        }

        foreach ($predictions as $p) {
            foreach ($p->recommended_interventions ?? [] as $s) {
                if (is_string($s) && trim($s) !== '') {
                    $lines[] = trim($s);
                }
            }
        }

        $lines = array_values(array_unique($lines));

        return array_slice($lines, 0, 12);
    }
}
