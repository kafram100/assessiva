<?php

namespace App\Exports;

use App\Models\PredictionReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PredictionReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private ?PredictionReport $report,
        private string $studentIndex,
        private ?string $courseName,
    ) {}

    public function collection(): Collection
    {
        if (! $this->report) {
            return collect([
                array_merge(
                    ['No prediction report found. Generate one in the dashboard first.'],
                    array_fill(0, 10, '')
                ),
            ]);
        }

        $r = $this->report;
        $snap = $r->student_data_snapshot ?? [];

        return collect([
            [
                $this->studentIndex,
                $this->courseName ?? '',
                $r->failure_probability,
                $r->dropout_risk,
                $r->burnout_risk,
                $r->intervention_urgency,
                $r->predicted_grade,
                $this->joinInterventions($r->recommended_interventions ?? []),
                $r->advisory_rationale,
                json_encode($snap),
                optional($r->generated_at)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * @param  list<mixed>  $items
     */
    private function joinInterventions(array $items): string
    {
        return implode("\n", array_map(fn ($x) => is_scalar($x) ? (string) $x : json_encode($x), $items));
    }

    public function headings(): array
    {
        return [
            'Student Index',
            'Course',
            'Failure Probability',
            'Dropout Risk',
            'Burnout Risk',
            'Intervention Urgency',
            'Predicted Grade Outlook',
            'Recommended Interventions',
            'Advisory Rationale',
            'Student Data Snapshot (JSON)',
            'Generated At',
        ];
    }

    public function title(): string
    {
        return 'Prediction Report';
    }
}
