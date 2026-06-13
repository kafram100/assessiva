<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\CheatingIncident;
use App\Models\Exam;
use App\Models\ExamActivityLog;
use App\Models\ExamSecurityLog;
use App\Models\ExamSessionProctoring;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProctoringController extends Controller
{
    public function monitor(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        $attemptIds = Attempt::query()
            ->whereHas('exam', fn ($q) => $q->where('user_id', $userId))
            ->pluck('id');

        $sessions = ExamSessionProctoring::query()
            ->with(['attempt.exam'])
            ->whereHas('attempt.exam', fn ($q) => $q->where('user_id', $userId))
            ->whereIn('proctoring_status', ['active', 'flagged', 'violated'])
            ->orderByDesc('created_at')
            ->get();

        $recentLogs = ExamSecurityLog::query()
            ->whereIn('exam_attempt_id', $attemptIds)
            ->orderByDesc('timestamp')
            ->limit(20)
            ->get();

        $recentFaceEvents = CheatEvent::query()
            ->with('attempt')
            ->whereIn('attempt_id', $attemptIds)
            ->whereIn('event_type', ['no_face_detected', 'multiple_faces', 'looking_away'])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $cheatSummary = CheatEvent::query()
            ->whereIn('attempt_id', $attemptIds)
            ->select('event_type', DB::raw('count(*) as total'))
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        return Inertia::render('Lecturer/Proctoring/Monitor', [
            'sessions' => $sessions,
            'recentLogs' => $recentLogs,
            'recentFaceEvents' => $recentFaceEvents,
            'cheatSummary' => $cheatSummary,
        ]);
    }

    public function report(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        $exams = Exam::query()->where('user_id', $userId)->orderBy('title')->get();

        $examId = $request->integer('exam_id');
        $severity = $request->input('severity');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range', '7d');
        $eventType = $request->input('event_type');
        $studentIndex = trim((string) $request->input('student_index', ''));

        if (! $startDate && ! $endDate) {
            $today = Carbon::today()->toDateString();
            if ($dateRange === 'today') {
                $startDate = $today;
                $endDate = $today;
            } elseif ($dateRange === '30d') {
                $startDate = Carbon::now()->subDays(30)->toDateString();
                $endDate = Carbon::today()->toDateString();
            } elseif ($dateRange === 'all') {
                // leave empty
            } else {
                $dateRange = '7d';
                $startDate = Carbon::now()->subDays(7)->toDateString();
                $endDate = Carbon::today()->toDateString();
            }
        } else {
            $dateRange = 'custom';
        }

        $attemptIds = Attempt::query()
            ->whereHas('exam', fn ($q) => $q->where('user_id', $userId))
            ->when($examId, fn ($q) => $q->where('exam_id', $examId))
            ->when($studentIndex !== '', fn ($q) => $q->where('student_index', $studentIndex))
            ->pluck('id');

        $isNarrowSearch = $studentIndex !== '' || (bool) $examId;
        $maxRows = $isNarrowSearch ? 1000 : 300;
        $resultsCapped = ! $isNarrowSearch;

        $logsQuery = ExamActivityLog::query()
            ->with('attempt')
            ->whereIn('exam_attempt_id', $attemptIds);
        if ($severity) {
            $logsQuery->where('severity', $severity);
        }
        if ($startDate) {
            $logsQuery->where('timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $logsQuery->where('timestamp', '<=', $endDate.' 23:59:59');
        }

        $logs = $logsQuery->orderByDesc('timestamp')
            ->limit($maxRows)
            ->get()
            ->groupBy(fn ($l) => $l->attempt?->student_index ?? 'unknown');

        $cheatQuery = CheatEvent::query()
            ->with('attempt')
            ->whereIn('attempt_id', $attemptIds);
        if ($severity) {
            $cheatQuery->where('severity', $severity);
        }
        if ($eventType) {
            $cheatQuery->where('event_type', $eventType);
        }
        if ($startDate) {
            $cheatQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $cheatQuery->where('created_at', '<=', $endDate.' 23:59:59');
        }

        $cheatEvents = $cheatQuery->orderByDesc('created_at')
            ->limit($maxRows)
            ->get()
            ->groupBy(fn ($e) => $e->attempt?->student_index ?? 'unknown');

        $incidentsQuery = CheatingIncident::query()
            ->with('attempt')
            ->whereIn('exam_attempt_id', $attemptIds);
        if ($startDate) {
            $incidentsQuery->where('incident_timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $incidentsQuery->where('incident_timestamp', '<=', $endDate.' 23:59:59');
        }

        $incidents = $incidentsQuery->orderByDesc('incident_timestamp')
            ->limit($maxRows)
            ->get();

        $eventTypeOptions = array_keys(CheatEvent::$severityMap);

        return Inertia::render('Lecturer/Proctoring/Report', [
            'exams' => $exams,
            'logs' => $logs,
            'cheatEvents' => $cheatEvents,
            'incidents' => $incidents,
            'filters' => [
                'exam_id' => $examId ?: null,
                'severity' => $severity,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'date_range' => $dateRange,
                'event_type' => $eventType,
                'student_index' => $studentIndex,
            ],
            'eventTypeOptions' => $eventTypeOptions,
            'resultsCapped' => $resultsCapped,
            'maxRows' => $maxRows,
        ]);
    }
}
