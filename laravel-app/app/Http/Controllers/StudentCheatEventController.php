<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\ExamAllowedIndex;
use App\Models\ExamSessionProctoring;
use App\Support\ExamDeviceBinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentCheatEventController extends Controller
{
    /**
     * Unified client-side proctoring signals (tab switch, face detection, etc.).
     * Session is established by the student exam entry flow.
     */
    public function store(Request $request): JsonResponse
    {
        $studentIndex = ExamAllowedIndex::normalize((string) session('student_index', ''));
        $examId = (int) session('exam_id', 0);

        if ($studentIndex === '' || $examId <= 0) {
            return response()->json(['ok' => false, 'error' => 'No active exam session.'], 403);
        }

        $attempt = Attempt::where('exam_id', $examId)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$studentIndex])
            ->where('status', 'ongoing')
            ->first();

        if (! $attempt) {
            return response()->json(['ok' => false, 'error' => 'No ongoing attempt found.'], 404);
        }

        if (! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return response()->json(['ok' => false, 'error' => 'Device mismatch for this attempt.'], 403);
        }

        $data = $request->validate([
            'event_type' => 'required|string|max:80',
            'metadata' => 'nullable|array',
            'metadata.*' => 'nullable',
        ]);

        $severity = CheatEvent::$severityMap[$data['event_type']] ?? 'medium';

        CheatEvent::create([
            'attempt_id' => $attempt->id,
            'event_type' => $data['event_type'],
            'metadata' => $data['metadata'] ?? null,
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if (in_array($severity, ['high', 'critical'], true)) {
            ExamSessionProctoring::where('exam_attempt_id', $attempt->id)
                ->whereIn('proctoring_status', ['active', 'flagged'])
                ->update([
                    'proctoring_status' => 'flagged',
                    'suspicious_activity_count' => DB::raw('suspicious_activity_count + 1'),
                ]);
        }

        return response()->json(['ok' => true, 'severity' => $severity]);
    }
}
