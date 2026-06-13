<?php

namespace App\Services;

use App\Models\Department;
use App\Services\Concerns\RollsUpDepartmentIntelligence;

class AdminInstitutionalIntelligenceService
{
    use RollsUpDepartmentIntelligence;

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $departments = Department::query()->with('school')->orderBy('name')->get();
        $rows = [];
        foreach ($departments as $dept) {
            $rows[] = array_merge([
                'department_id' => $dept->id,
                'school_name' => $dept->school?->name,
            ], $this->rollupDepartment($dept->id, $dept->name));
        }

        return [
            'departments' => $rows,
            'institution' => $this->rollupInstitution($rows),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $deptRows
     * @return array<string, mixed>
     */
    private function rollupInstitution(array $deptRows): array
    {
        $scores = [];
        $heatmap = [];
        foreach ($deptRows as $r) {
            $s = $r['academic_health']['score'] ?? null;
            if ($s !== null) {
                $scores[] = (int) $s;
            }
            $heatmap[] = [
                'department_id' => $r['department_id'] ?? null,
                'department' => $r['department_name'],
                'school_name' => $r['school_name'] ?? null,
                'score' => $s,
                'label' => $r['academic_health']['label'] ?? null,
            ];
        }

        $univScore = $scores !== [] ? (int) round(array_sum($scores) / count($scores)) : null;
        $univLabel = $univScore === null ? 'Insufficient stored intelligence' : ($univScore >= 75 ? 'Strong' : ($univScore >= 55 ? 'Stable' : 'Needs attention'));

        $weakestDepartments = collect($deptRows)
            ->filter(fn (array $r) => ($r['academic_health']['score'] ?? null) !== null)
            ->sortBy(fn (array $r) => (int) $r['academic_health']['score'])
            ->take(10)
            ->values()
            ->map(fn (array $r) => [
                'department_id' => $r['department_id'] ?? null,
                'department' => $r['department_name'],
                'score' => $r['academic_health']['score'],
            ])
            ->all();

        $topicAcc = [];
        foreach ($deptRows as $r) {
            foreach ($r['weak_topics'] ?? [] as $wt) {
                $t = (string) ($wt['topic'] ?? '');
                if ($t === '') {
                    continue;
                }
                $topicAcc[$t] = ($topicAcc[$t] ?? 0) + (int) ($wt['weight'] ?? 0);
            }
        }
        arsort($topicAcc);
        $universityWeakTopics = collect($topicAcc)
            ->take(20)
            ->map(fn ($w, $t) => ['topic' => $t, 'weight' => (int) $w])
            ->values()
            ->all();

        $highSignals = 0;
        $mediumSignals = 0;
        foreach ($deptRows as $r) {
            $highSignals += count($r['at_risk']['high'] ?? []);
            $mediumSignals += count($r['at_risk']['medium'] ?? []);
        }

        $fairnessScores = [];
        $lowFairnessDepartments = [];
        foreach ($deptRows as $r) {
            $p = $r['fairness']['proxy_score'] ?? null;
            if ($p !== null) {
                $fairnessScores[] = (int) $p;
                if ((int) $p < 52 && ! empty($r['department_name'])) {
                    $lowFairnessDepartments[] = (string) $r['department_name'];
                }
            }
        }
        $lowFairnessDepartments = array_values(array_unique($lowFairnessDepartments));

        $totalPractice = 0;
        foreach ($deptRows as $r) {
            $totalPractice += (int) ($r['practice_hub']['total_attempts'] ?? 0);
        }

        $weekAcc = [];
        foreach ($deptRows as $r) {
            foreach ($r['practice_trend'] ?? [] as $pt) {
                $wk = (string) ($pt['week'] ?? '');
                if ($wk === '') {
                    continue;
                }
                $weekAcc[$wk] = ($weekAcc[$wk] ?? 0) + (int) ($pt['count'] ?? 0);
            }
        }
        ksort($weekAcc);
        $practiceWeekly = collect($weekAcc)
            ->map(fn ($c, $w) => ['week' => $w, 'count' => (int) $c])
            ->values()
            ->slice(-14)
            ->values()
            ->all();

        $passRates = [];
        foreach ($deptRows as $r) {
            $pr = $r['performance']['pass_rate_percent'] ?? null;
            if ($pr !== null) {
                $passRates[] = (int) $pr;
            }
        }
        $avgPass = $passRates !== [] ? (int) round(array_sum($passRates) / count($passRates)) : null;

        $lines = [];
        foreach ($deptRows as $r) {
            foreach ($r['ai_recommendations'] ?? [] as $line) {
                if (is_string($line) && trim($line) !== '') {
                    $lines[] = trim($line);
                }
            }
        }
        $lines = array_values(array_unique($lines));

        $counts = [
            'departments' => count($deptRows),
            'high_tier_risk_signals' => $highSignals,
            'medium_tier_risk_signals' => $mediumSignals,
            'practice_attempts_total' => $totalPractice,
        ];

        return [
            'university_academic_health' => [
                'score' => $univScore,
                'label' => $univLabel,
            ],
            'department_heatmap' => $heatmap,
            'weakest_departments' => $weakestDepartments,
            'university_weak_topics' => $universityWeakTopics,
            'fairness' => [
                'avg_proxy_score' => $fairnessScores !== [] ? (int) round(array_sum($fairnessScores) / count($fairnessScores)) : null,
                'departments_with_low_proxy' => array_slice($lowFairnessDepartments, 0, 10),
                'note' => 'Institutional fairness-adjacent view is derived from stored cohort analytics per department — supportive review only.',
            ],
            'retention_risk' => [
                'high_tier_signals' => $highSignals,
                'medium_tier_signals' => $mediumSignals,
                'note' => 'Counts reflect anonymised risk tiers aggregated from stored Prediction Engine and Analytics Intelligence outputs.',
            ],
            'practice_institution' => [
                'total_attempts' => $totalPractice,
                'weekly_trend' => $practiceWeekly,
            ],
            'cross_department_performance' => [
                'avg_pass_rate_percent' => $avgPass,
            ],
            'ai_recommendations' => array_slice($lines, 0, 18),
            'counts' => $counts,
        ];
    }
}
