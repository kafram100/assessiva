<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class DepartmentAnalyticsExport implements FromArray, WithTitle, ShouldAutoSize
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private array $payload,
        private string $departmentLabel,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $rows[] = ['Department Analytics Export', $this->departmentLabel];
        $rows[] = [];
        $intel = $this->payload['intelligence'] ?? [];
        $dept = $this->payload['department'] ?? null;
        if (is_array($dept)) {
            $rows[] = ['Department', $dept['name'] ?? ''];
            $rows[] = ['School', $dept['school']['name'] ?? ''];
            $rows[] = [];
        }

        $health = $intel['academic_health'] ?? [];
        $rows[] = ['Academic Health Score', $health['score'] ?? ''];
        $rows[] = ['Academic Health Label', $health['label'] ?? ''];
        $rows[] = [];

        $perf = $intel['performance'] ?? [];
        $rows[] = ['Pass Rate %', $perf['pass_rate_percent'] ?? ''];
        $rows[] = ['Attempts (scope)', $perf['attempts'] ?? ''];
        $rows[] = [];

        $rows[] = ['Weakest Courses', 'Score'];
        foreach ($intel['weakest_courses'] ?? [] as $wc) {
            if (! is_array($wc)) {
                continue;
            }
            $rows[] = [$wc['course_name'] ?? $wc['name'] ?? '', $wc['health_score'] ?? $wc['score'] ?? ''];
        }
        $rows[] = [];

        $rows[] = ['Weak Topics', 'Weight / Signal'];
        foreach ($intel['weak_topics'] ?? [] as $wt) {
            if (! is_array($wt)) {
                continue;
            }
            $rows[] = [$wt['topic'] ?? '', $wt['weight'] ?? $wt['count'] ?? ''];
        }
        $rows[] = [];

        $fair = $intel['fairness'] ?? [];
        $rows[] = ['Fairness Proxy Score', $fair['proxy_score'] ?? ''];
        $rows[] = ['Fairness Note', $fair['note'] ?? ''];
        $rows[] = [];

        $rows[] = ['At-risk (high tier)', 'Detail'];
        foreach ($intel['at_risk']['high'] ?? [] as $h) {
            $rows[] = [is_array($h) ? json_encode($h) : (string) $h, ''];
        }
        $rows[] = ['At-risk (medium tier)', ''];
        foreach ($intel['at_risk']['medium'] ?? [] as $m) {
            $rows[] = [is_array($m) ? json_encode($m) : (string) $m, ''];
        }
        $rows[] = [];

        $ph = $intel['practice_hub'] ?? [];
        $rows[] = ['Practice Hub Total Attempts', $ph['total_attempts'] ?? 0];
        $rows[] = [];

        $counts = $intel['counts'] ?? [];
        $rows[] = ['Learning Twins (count)', $counts['learning_twins'] ?? 0];
        $rows[] = ['Prediction Reports (count)', $counts['prediction_reports'] ?? 0];
        $rows[] = ['Analytics Reports Used', $counts['analytics_reports_used'] ?? 0];
        $rows[] = [];

        $rows[] = ['AI Recommendations', ''];
        foreach ($intel['ai_recommendations'] ?? [] as $rec) {
            $rows[] = [(string) $rec, ''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Department Analytics';
    }
}
