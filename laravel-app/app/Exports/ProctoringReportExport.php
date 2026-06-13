<?php

namespace App\Exports;

use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\CheatingIncident;
use App\Models\ExamActivityLog;
use App\Models\ExamAllowedIndex;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Flattened proctoring activity for Excel — activity logs, cheat events, and incidents.
 * Rows are sorted by timestamp (newest first). Does not call AI or alter scores.
 */
class ProctoringReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    private const COL_COUNT = 16;

    /**
     * @param  Collection<int, int>  $attemptIds
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private Collection $attemptIds,
        private array $filters,
        private int $maxRowsPerSource,
    ) {}

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function attemptScoreParts(?Attempt $a): array
    {
        if (! $a) {
            return [0.0, 0.0, 0.0];
        }
        $raw = (float) ($a->raw_score ?? $a->score ?? 0);
        $pen = (float) ($a->proctoring_penalty_total ?? 0);
        $adj = (float) ($a->score ?? 0);

        return [$raw, $pen, $adj];
    }

    public function collection(): Collection
    {
        if ($this->attemptIds->isEmpty()) {
            return collect([
                array_merge(
                    ['No data'],
                    array_fill(0, self::COL_COUNT - 1, '')
                ),
            ]);
        }

        $rows = collect();

        $logsQuery = ExamActivityLog::query()
            ->with(['attempt.exam'])
            ->whereIn('exam_attempt_id', $this->attemptIds);
        if (! empty($this->filters['severity'])) {
            $logsQuery->where('severity', $this->filters['severity']);
        }
        if (! empty($this->filters['start_date'])) {
            $logsQuery->where('timestamp', '>=', $this->filters['start_date']);
        }
        if (! empty($this->filters['end_date'])) {
            $logsQuery->where('timestamp', '<=', $this->filters['end_date'].' 23:59:59');
        }
        $logs = $logsQuery->orderByDesc('timestamp')->limit($this->maxRowsPerSource)->get();
        foreach ($logs as $log) {
            $a = $log->attempt;
            $ex = $a?->exam;
            $ts = $log->timestamp;
            [$raw, $pen, $adj] = $this->attemptScoreParts($a);
            $rows->push([
                '_sort' => $ts?->timestamp ?? 0,
                'Activity log',
                $ts?->format('Y-m-d H:i:s') ?? '',
                (string) ($log->severity ?? ''),
                (string) ($log->activity_type ?? ''),
                (string) ($a?->student_index ?? ''),
                (string) ($a?->student_fullname ?? ''),
                (string) ($ex?->title ?? ''),
                (string) ($ex?->exam_code ?? ''),
                (string) ($log->description ?? ''),
                (string) ($log->ip_address ?? ''),
                $raw,
                $pen,
                $adj,
                '',
                '',
                '',
            ]);
        }

        $cheatQuery = CheatEvent::query()
            ->with(['attempt.exam'])
            ->whereIn('attempt_id', $this->attemptIds);
        if (! empty($this->filters['severity'])) {
            $cheatQuery->where('severity', $this->filters['severity']);
        }
        if (! empty($this->filters['event_type'])) {
            $cheatQuery->where('event_type', $this->filters['event_type']);
        }
        if (! empty($this->filters['start_date'])) {
            $cheatQuery->where('created_at', '>=', $this->filters['start_date']);
        }
        if (! empty($this->filters['end_date'])) {
            $cheatQuery->where('created_at', '<=', $this->filters['end_date'].' 23:59:59');
        }
        $cheats = $cheatQuery->orderByDesc('created_at')->limit($this->maxRowsPerSource)->get();
        foreach ($cheats as $ev) {
            $a = $ev->attempt;
            $ex = $a?->exam;
            $ts = $ev->created_at;
            $meta = $ev->metadata;
            [$raw, $pen, $adj] = $this->attemptScoreParts($a);
            $rows->push([
                '_sort' => $ts?->timestamp ?? 0,
                'Cheat / integrity event',
                $ts?->format('Y-m-d H:i:s') ?? '',
                (string) ($ev->severity ?? ''),
                (string) ($ev->event_type ?? ''),
                (string) ($a?->student_index ?? ''),
                (string) ($a?->student_fullname ?? ''),
                (string) ($ex?->title ?? ''),
                (string) ($ex?->exam_code ?? ''),
                '',
                is_array($meta) ? json_encode($meta) : (string) ($meta ?? ''),
                $raw,
                $pen,
                $adj,
                (string) ($ev->review_status ?? 'pending'),
                $ev->penalty_marks !== null ? (float) $ev->penalty_marks : '',
                $ev->penalty_applied ? 'yes' : 'no',
            ]);
        }

        $incQuery = CheatingIncident::query()
            ->with(['attempt.exam'])
            ->whereIn('exam_attempt_id', $this->attemptIds);
        if (! empty($this->filters['start_date'])) {
            $incQuery->where('incident_timestamp', '>=', $this->filters['start_date']);
        }
        if (! empty($this->filters['end_date'])) {
            $incQuery->where('incident_timestamp', '<=', $this->filters['end_date'].' 23:59:59');
        }
        $incidents = $incQuery->orderByDesc('incident_timestamp')->limit($this->maxRowsPerSource)->get();
        foreach ($incidents as $inc) {
            $a = $inc->attempt;
            $ex = $a?->exam;
            $ts = $inc->incident_timestamp;
            $detail = trim(
                implode(' | ', array_filter([
                    (string) ($inc->incident_type ?? ''),
                    'confidence: '.(string) ($inc->confidence_level ?? ''),
                    'status: '.(string) ($inc->status ?? ''),
                    (string) ($inc->notes ?? ''),
                ]))
            );
            [$raw, $pen, $adj] = $this->attemptScoreParts($a);
            $rows->push([
                '_sort' => $ts?->timestamp ?? 0,
                'Cheating incident',
                $ts?->format('Y-m-d H:i:s') ?? '',
                (string) ($inc->confidence_level ?? ''),
                (string) ($inc->incident_type ?? ''),
                (string) ($a?->student_index ?? ''),
                (string) ($a?->student_fullname ?? ''),
                (string) ($ex?->title ?? ''),
                (string) ($ex?->exam_code ?? ''),
                $detail,
                '',
                $raw,
                $pen,
                $adj,
                '',
                '',
                '',
            ]);
        }

        return $rows
            ->sortByDesc('_sort')
            ->values()
            ->map(function (array $r) {
                unset($r['_sort']);

                return array_values($r);
            });
    }

    public function headings(): array
    {
        return [
            'Record type',
            'Timestamp',
            'Severity / confidence',
            'Type',
            'Student index',
            'Student name',
            'Exam title',
            'Exam code',
            'Detail',
            'Metadata / extra',
            'Raw score (attempt)',
            'Total proctoring penalty',
            'Adjusted score (attempt)',
            'Event review status',
            'Event penalty marks',
            'Penalty applied to event',
        ];
    }

    public function title(): string
    {
        return 'Proctoring';
    }

    /**
     * @return array{0: Collection<int, int>, 1: int, 2: array<string, mixed>}
     */
    public static function resolveAttemptContext(Request $request, User $user, bool $adminScope): array
    {
        $examId = $request->integer('exam_id');
        $severity = $request->input('severity');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range', '7d');
        $eventType = $request->input('event_type');
        $studentIndex = trim((string) $request->input('student_index', ''));
        if ($studentIndex !== '') {
            $studentIndex = ExamAllowedIndex::normalize($studentIndex);
        }

        if (! $startDate && ! $endDate) {
            $today = Carbon::today()->toDateString();
            if ($dateRange === 'today') {
                $startDate = $today;
                $endDate = $today;
            } elseif ($dateRange === '30d') {
                $startDate = Carbon::now()->subDays(30)->toDateString();
                $endDate = Carbon::today()->toDateString();
            } elseif ($dateRange === 'all') {
                $startDate = null;
                $endDate = null;
            } else {
                $dateRange = '7d';
                $startDate = Carbon::now()->subDays(7)->toDateString();
                $endDate = Carbon::today()->toDateString();
            }
        } else {
            $dateRange = 'custom';
        }

        $q = Attempt::query();
        if (! $adminScope) {
            $q->whereHas('exam', fn ($ex) => $ex->where('user_id', $user->id));
        }
        if ($examId > 0) {
            $q->where('exam_id', $examId);
        }
        if ($studentIndex !== '') {
            $q->where('student_index', $studentIndex);
        }

        $attemptIds = $q->pluck('id');

        $isNarrowSearch = $studentIndex !== '' || $examId > 0;
        $maxRows = $isNarrowSearch ? 1000 : 300;

        $filters = [
            'exam_id' => $examId ?: null,
            'severity' => $severity,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'date_range' => $dateRange,
            'event_type' => $eventType,
            'student_index' => $studentIndex,
        ];

        return [$attemptIds, $maxRows, $filters];
    }
}
