<?php

namespace App\Exports;

use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExamResultsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private Exam $exam)
    {
        $this->exam->loadMissing(['course.department']);
    }

    public function collection(): Collection
    {
        $attempts = Attempt::query()
            ->withCount(['cheatEvents', 'cheatingIncidents'])
            ->where('exam_id', $this->exam->id)
            ->where('status', 'completed')
            ->orderByDesc('submit_time')
            ->get();

        $dept = (string) ($this->exam->course?->department?->name ?? '');

        if ($attempts->isEmpty()) {
            return collect([
                array_merge(
                    ['No completed attempts recorded for this exam yet.'],
                    array_fill(0, 14, '')
                ),
            ]);
        }

        return $attempts->map(function (Attempt $a) use ($dept) {
            $total = (float) ($a->total_marks ?? 0);
            $raw = (float) ($a->raw_score ?? $a->score ?? 0);
            $pen = (float) ($a->proctoring_penalty_total ?? 0);
            $adj = (float) ($a->score ?? 0);
            $pct = $total > 0 ? round(($adj / $total) * 100, 2) : 0.0;
            $remark = $pct >= 50 ? 'Pass' : 'Fail';

            return [
                $a->student_fullname ?? '',
                $a->student_index ?? '',
                $this->exam->course_name ?? '',
                $dept,
                $this->exam->title ?? '',
                $raw,
                $pen,
                $adj,
                $pct,
                $remark,
                optional($a->submit_time)->format('Y-m-d H:i:s') ?? '',
                $a->student_level ?? '',
                (int) ($this->exam->duration ?? 0),
                (int) ($a->cheat_events_count ?? 0) + (int) ($a->cheating_incidents_count ?? 0),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Index Number',
            'Course',
            'Department',
            'Exam',
            'Raw score',
            'Proctoring penalty total',
            'Adjusted score',
            'Percentage',
            'Remark',
            'Attempt Date',
            'Student Level',
            'Exam Duration (min)',
            'Suspicious Activity Count',
        ];
    }

    public function title(): string
    {
        return 'Exam Results';
    }
}
