<?php

namespace App\Exports;

use App\Models\Exam;
use App\Models\PracticeQuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PracticeQuizAnalyticsExport implements FromArray, WithTitle, ShouldAutoSize
{
    /**
     * @param  array<string, mixed>  $stats
     * @param  list<array<string, mixed>>  $weakTopics
     * @param  list<array<string, mixed>>  $questionFailures
     * @param  list<array<string, mixed>>  $trend
     */
    public function __construct(
        private Exam $quiz,
        private array $stats,
        private array $weakTopics,
        private array $questionFailures,
        private array $trend,
        private Collection $attemptRows,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $rows[] = ['Quiz', $this->quiz->title];
        $rows[] = ['Course', $this->quiz->course_name ?? ''];
        $rows[] = [];
        $rows[] = ['Completed Attempts', $this->stats['attempts'] ?? 0];
        $rows[] = ['Average Score %', $this->stats['average_percent'] ?? 0];
        $rows[] = [];
        $rows[] = ['Weak Topics (aggregated)', 'Count'];
        foreach ($this->weakTopics as $wt) {
            $rows[] = [$wt['topic'] ?? '', $wt['count'] ?? 0];
        }
        $rows[] = [];
        $rows[] = ['Question Failures', 'Misses', 'Preview'];
        foreach ($this->questionFailures as $qf) {
            $rows[] = [$qf['question_id'] ?? '', $qf['misses'] ?? 0, $qf['question_preview'] ?? ''];
        }
        $rows[] = [];
        $rows[] = ['Participation Trend (by day)', 'Completions'];
        foreach ($this->trend as $t) {
            $rows[] = [$t['d'] ?? '', $t['c'] ?? 0];
        }
        $rows[] = [];
        $rows[] = ['Attempt ID', 'Score', 'Total Marks', 'Weak Topics', 'Duration (s)', 'Submitted At'];
        foreach ($this->attemptRows as $a) {
            $wt = $a->weak_topics ?? [];
            $wtStr = is_array($wt) ? implode('; ', array_map('strval', $wt)) : '';
            $rows[] = [
                $a->id,
                $a->score,
                $a->total_marks,
                $wtStr,
                $a->duration_seconds,
                optional($a->submitted_at)->format('Y-m-d H:i:s'),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return Str::limit(preg_replace('/[^\w\-]+/u', '_', $this->quiz->title ?? 'quiz'), 28, '') ?: 'Practice Analytics';
    }

    /**
     * @return self
     */
    public static function fromQuiz(Exam $quiz): self
    {
        $attempts = PracticeQuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(500)
            ->get();

        $count = $attempts->count();
        $avgPercent = 0.0;
        if ($count > 0) {
            $sum = 0.0;
            foreach ($attempts as $a) {
                $tm = max(1, (int) $a->total_marks);
                $sum += ((float) $a->score / $tm) * 100;
            }
            $avgPercent = round($sum / $count, 1);
        }

        $weakAgg = [];
        foreach ($attempts as $a) {
            $wt = $a->weak_topics;
            if (! is_array($wt)) {
                continue;
            }
            foreach ($wt as $t) {
                $t = trim((string) $t);
                if ($t === '') {
                    continue;
                }
                $weakAgg[$t] = ($weakAgg[$t] ?? 0) + 1;
            }
        }
        arsort($weakAgg);
        $weakTopics = collect($weakAgg)->take(15)->map(fn ($c, $t) => ['topic' => $t, 'count' => $c])->values()->all();

        $questions = $quiz->questions()->get(['id', 'question_text']);
        $fails = [];
        foreach ($questions as $q) {
            $fails[$q->id] = ['misses' => 0, 'preview' => Str::limit(strip_tags($q->question_text), 80)];
        }
        foreach ($attempts as $att) {
            $resp = $att->responses;
            if (! is_array($resp)) {
                continue;
            }
            foreach ($resp as $qid => $row) {
                $qid = (int) $qid;
                if (! isset($fails[$qid])) {
                    continue;
                }
                $correct = is_array($row) ? ($row['correct'] ?? false) : false;
                $awarded = is_array($row) ? (float) ($row['marks_awarded'] ?? 0) : 0.0;
                $max = is_array($row) ? (int) ($row['max'] ?? 0) : 0;
                if (! $correct || ($max > 0 && $awarded < $max)) {
                    $fails[$qid]['misses']++;
                }
            }
        }
        $questionFails = collect($fails)
            ->map(fn ($meta, $qid) => [
                'question_id' => (int) $qid,
                'misses' => $meta['misses'],
                'question_preview' => $meta['preview'],
            ])
            ->sortByDesc('misses')
            ->values()
            ->take(20)
            ->all();

        $byDay = [];
        foreach ($attempts as $a) {
            if ($a->submitted_at === null) {
                continue;
            }
            $d = $a->submitted_at->format('Y-m-d');
            $byDay[$d] = ($byDay[$d] ?? 0) + 1;
        }
        ksort($byDay);
        $trend = collect($byDay)
            ->map(fn (int $c, string $d) => ['d' => $d, 'c' => $c])
            ->values()
            ->slice(-30)
            ->values()
            ->all();

        return new self(
            $quiz,
            ['attempts' => $count, 'average_percent' => $avgPercent],
            $weakTopics,
            $questionFails,
            $trend,
            $attempts,
        );
    }
}
