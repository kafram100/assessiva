<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class InstitutionalAnalyticsExport implements FromArray, WithTitle, ShouldAutoSize
{
    /**
     * @param  array<string, mixed>  $dashboard
     */
    public function __construct(private array $dashboard) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $rows[] = ['Institutional intelligence export', ''];
        $rows[] = [];

        $inst = $this->dashboard['institution'] ?? [];
        $uh = $inst['university_academic_health'] ?? [];
        $rows[] = ['University academic health score', $uh['score'] ?? ''];
        $rows[] = ['University academic health label', $uh['label'] ?? ''];
        $rows[] = [];

        $rows[] = ['Department heatmap', 'School', 'Health score', 'Health label'];
        foreach ($inst['department_heatmap'] ?? [] as $h) {
            if (! is_array($h)) {
                continue;
            }
            $rows[] = [
                $h['department'] ?? '',
                $h['school_name'] ?? '',
                $h['score'] ?? '',
                $h['label'] ?? '',
            ];
        }
        $rows[] = [];

        $rows[] = ['Weakest departments', 'Score'];
        foreach ($inst['weakest_departments'] ?? [] as $w) {
            if (! is_array($w)) {
                continue;
            }
            $rows[] = [$w['department'] ?? '', $w['score'] ?? ''];
        }
        $rows[] = [];

        $rows[] = ['University weak topics', 'Weight'];
        foreach ($inst['university_weak_topics'] ?? [] as $wt) {
            if (! is_array($wt)) {
                continue;
            }
            $rows[] = [$wt['topic'] ?? '', $wt['weight'] ?? ''];
        }
        $rows[] = [];

        $fair = $inst['fairness'] ?? [];
        $rows[] = ['Fairness avg proxy score', $fair['avg_proxy_score'] ?? ''];
        $rows[] = ['Departments with low proxy (sample)', implode('; ', $fair['departments_with_low_proxy'] ?? [])];
        $rows[] = ['Fairness note', $fair['note'] ?? ''];
        $rows[] = [];

        $ret = $inst['retention_risk'] ?? [];
        $rows[] = ['Retention risk — high tier signal count', $ret['high_tier_signals'] ?? ''];
        $rows[] = ['Retention risk — medium tier signal count', $ret['medium_tier_signals'] ?? ''];
        $rows[] = ['Retention risk note', $ret['note'] ?? ''];
        $rows[] = [];

        $pi = $inst['practice_institution'] ?? [];
        $rows[] = ['Practice hub — total attempts', $pi['total_attempts'] ?? ''];
        $rows[] = ['Practice weekly trend', 'Completions'];
        foreach ($pi['weekly_trend'] ?? [] as $pt) {
            if (! is_array($pt)) {
                continue;
            }
            $rows[] = [$pt['week'] ?? '', $pt['count'] ?? ''];
        }
        $rows[] = [];

        $cross = $inst['cross_department_performance'] ?? [];
        $rows[] = ['Cross-department avg pass rate %', $cross['avg_pass_rate_percent'] ?? ''];
        $rows[] = [];

        $rows[] = ['Department detail', 'School', 'Health', 'Pass %', 'Twins', 'Predictions'];
        foreach ($this->dashboard['departments'] ?? [] as $d) {
            if (! is_array($d)) {
                continue;
            }
            $ah = $d['academic_health'] ?? [];
            $perf = $d['performance'] ?? [];
            $cnt = $d['counts'] ?? [];
            $rows[] = [
                $d['department_name'] ?? '',
                $d['school_name'] ?? '',
                $ah['score'] ?? '',
                $perf['pass_rate_percent'] ?? '',
                $cnt['learning_twins'] ?? '',
                $cnt['prediction_reports'] ?? '',
            ];
        }
        $rows[] = [];

        $rows[] = ['Institution counts', 'Value'];
        foreach ($inst['counts'] ?? [] as $k => $v) {
            $rows[] = [(string) $k, is_scalar($v) ? $v : json_encode($v)];
        }
        $rows[] = [];

        $rows[] = ['AI recommendations (institution)', ''];
        foreach ($inst['ai_recommendations'] ?? [] as $rec) {
            $rows[] = [(string) $rec, ''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Institutional Analytics';
    }
}
