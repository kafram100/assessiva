<?php

namespace App\Exports;

use App\Models\Exam;
use App\Models\ExamFairnessReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class FairnessReportExport implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(
        private Exam $exam,
        private ExamFairnessReport $report,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $rows[] = ['Exam', $this->exam->title];
        $rows[] = ['Course', $this->exam->course_name ?? ''];
        $rows[] = ['Analyzed At', optional($this->report->analyzed_at)->format('Y-m-d H:i:s') ?? ''];
        $rows[] = [];
        $rows[] = ['Fairness Score', $this->report->fairness_score];
        $rows[] = ['Overall Status', $this->report->overall_status];
        $rows[] = ['Estimated Total Duration (min)', $this->report->estimated_total_duration_minutes];
        $rows[] = ['Duration Status', $this->report->duration_status];
        $rows[] = [];

        $dd = $this->report->difficulty_distribution ?? [];
        $rows[] = ['Difficulty Distribution', ''];
        $rows[] = ['Easy', $dd['easy'] ?? ''];
        $rows[] = ['Medium', $dd['medium'] ?? ''];
        $rows[] = ['Hard', $dd['hard'] ?? ''];
        $rows[] = ['Very Hard', $dd['very_hard'] ?? ''];
        $rows[] = [];

        $bd = $this->report->blooms_distribution ?? [];
        $rows[] = ['Bloom Taxonomy', ''];
        foreach (['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'] as $k) {
            $rows[] = [ucfirst($k), $bd[$k] ?? ''];
        }
        $rows[] = [];

        $rows[] = ['Cognitive Difficulty Map', '', '', '', ''];
        $rows[] = ['Question #', 'Difficulty', 'Bloom Level', 'Est. Minutes', ''];
        foreach ($this->report->cognitive_difficulty_map ?? [] as $m) {
            if (! is_array($m)) {
                continue;
            }
            $rows[] = [
                $m['question_number'] ?? '',
                $m['difficulty'] ?? '',
                $m['blooms_level'] ?? '',
                $m['estimated_time_minutes'] ?? '',
                '',
            ];
        }
        $rows[] = [];

        $rows[] = ['Question Analysis', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = [
            'Q#',
            'Question ID',
            'Question',
            'Difficulty',
            'Bloom',
            'Est. Min',
            'Syllabus',
            'Ambiguity',
            'Excess Difficulty',
            'Repeat',
            'Bias',
            'Trick',
        ];
        foreach ($this->report->question_analysis ?? [] as $q) {
            if (! is_array($q)) {
                continue;
            }
            $rows[] = [
                $q['question_number'] ?? '',
                $q['question_id'] ?? '',
                $q['question'] ?? '',
                $q['difficulty'] ?? '',
                $q['blooms_level'] ?? '',
                $q['estimated_time_minutes'] ?? '',
                $q['syllabus_alignment'] ?? '',
                ! empty($q['ambiguity_detected']) ? 'Yes' : 'No',
                ! empty($q['excessive_difficulty']) ? 'Yes' : 'No',
                ! empty($q['repeated_pattern']) ? 'Yes' : 'No',
                ! empty($q['bias_detected']) ? 'Yes' : 'No',
                ! empty($q['trick_question_detected']) ? 'Yes' : 'No',
            ];
            foreach ($q['issues'] ?? [] as $iss) {
                if (! is_array($iss)) {
                    continue;
                }
                $rows[] = [
                    '',
                    'Issue',
                    $iss['type'] ?? '',
                    $iss['severity'] ?? '',
                    $iss['message'] ?? '',
                    $iss['suggestion'] ?? '',
                    '', '', '', '', '',
                ];
            }
            if (! empty($q['improved_question'])) {
                $rows[] = ['', 'Improved', $q['improved_question'], '', '', '', '', '', '', '', ''];
            }
            $rows[] = [];
        }

        $rows[] = ['Recommendations', ''];
        foreach ($this->report->recommendations ?? [] as $r) {
            $rows[] = [(string) $r, ''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Fairness Report';
    }
}
