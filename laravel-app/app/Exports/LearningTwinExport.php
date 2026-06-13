<?php

namespace App\Exports;

use App\Models\LearningTwin;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LearningTwinExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private ?LearningTwin $twin,
        private string $studentIndex,
        private ?string $courseName,
    ) {}

    public function collection(): Collection
    {
        if (! $this->twin) {
            return collect([
                array_merge(
                    ['No learning twin found for this student / course context. Generate one in the dashboard first.'],
                    array_fill(0, 11, '')
                ),
            ]);
        }

        $t = $this->twin;

        return collect([
            [
                $this->studentIndex,
                $this->courseName ?? '',
                $t->academic_health_score,
                $t->risk_level,
                $t->predicted_next_score,
                $this->joinList($t->strengths),
                $this->joinList($t->weaknesses),
                $t->learning_pattern,
                $this->joinList($t->recommended_actions),
                $this->joinList($t->revision_focus),
                $t->lecturer_intervention,
                optional($t->generated_at)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * @param  array<int, mixed>|null  $list
     */
    private function joinList(?array $list): string
    {
        if (! is_array($list)) {
            return '';
        }

        return implode('; ', array_map(fn ($x) => is_scalar($x) ? (string) $x : json_encode($x), $list));
    }

    public function headings(): array
    {
        return [
            'Student Index',
            'Course',
            'Academic Health Score',
            'Risk Level',
            'Predicted Next Score',
            'Strengths',
            'Weak Topics / Weaknesses',
            'Learning Pattern',
            'Recommended Actions',
            'Revision Focus',
            'Lecturer Intervention',
            'Generated At',
        ];
    }

    public function title(): string
    {
        return 'Learning Twin';
    }
}
