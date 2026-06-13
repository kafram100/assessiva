<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\CheatingIncident;
use App\Models\Exam;
use App\Models\ExamActivityLog;
use App\Models\ExamSecurityLog;
use App\Models\ExamSessionProctoring;
use App\Support\ExamDeviceBinding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * JSON endpoints used by the student exam Blade UI (proctoring.js).
 */
class StudentExamProctoringController extends Controller
{
    public function enable(Request $request)
    {
        $examAttemptId = (int) session('exam_attempt_id', 0);
        $examId = (int) session('exam_id', 0);

        if ($examAttemptId <= 0 || $examId <= 0) {
            return response()->json(['success' => false, 'message' => 'Missing exam session context'], 400);
        }

        $attempt = Attempt::find($examAttemptId);
        if (! $attempt || (int) $attempt->exam_id !== $examId || ! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return response()->json(['success' => false, 'message' => 'Invalid exam session for this device'], 403);
        }

        $exam = Exam::find($examId);
        if (! $exam || ! $exam->requiresProctoring()) {
            session()->forget(['proctoring_enabled', 'proctoring_exam_id']);

            return response()->json(['success' => false, 'message' => 'Proctoring not required for this exam type'], 422);
        }

        session(['proctoring_enabled' => true, 'proctoring_exam_id' => $examId]);

        try {
            $exists = ExamSessionProctoring::where('exam_attempt_id', $examAttemptId)
                ->where('proctoring_status', 'active')
                ->exists();

            if (! $exists) {
                ExamSessionProctoring::create([
                    'exam_attempt_id' => $examAttemptId,
                    'student_id' => 0,
                    'lecturer_id' => (int) ($exam->user_id ?? 0),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('enable_proctoring DB warning: '.$e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Proctoring session enabled']);
    }

    public function log(Request $request)
    {
        $attemptId = (int) session('exam_attempt_id', 0);
        $type = 'other';
        $desc = '';
        $ip = $request->ip();

        if ($request->isJson()) {
            $data = $request->json()->all();
            $type = $data['type'] ?? $data['activity_type'] ?? 'other';
            $desc = $data['description'] ?? '';
            if (! empty($data['exam_attempt_id'])) {
                $attemptId = (int) $data['exam_attempt_id'];
            }
        } else {
            $type = $request->input('activity_type', 'other');
            $desc = $request->input('description', '');
            if ($request->filled('exam_attempt_id')) {
                $attemptId = (int) $request->input('exam_attempt_id');
            }
        }

        $severity = CheatEvent::$severityMap[$type] ?? $request->input('severity', 'medium');

        if ($attemptId <= 0) {
            return response()->json(['success' => false, 'message' => 'No attempt found'], 400);
        }

        $attempt = Attempt::find($attemptId);
        if (! $attempt || ! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return response()->json(['success' => false, 'message' => 'Invalid attempt for this device'], 403);
        }

        ExamActivityLog::create([
            'exam_attempt_id' => $attemptId,
            'user_id' => 0,
            'activity_type' => substr($type, 0, 50),
            'description' => $desc,
            'severity' => in_array($severity, ['low', 'medium', 'high', 'critical']) ? $severity : 'medium',
            'timestamp' => now(),
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
        ]);

        ExamSecurityLog::create([
            'exam_attempt_id' => $attemptId,
            'user_id' => 0,
            'activity_type' => substr($type, 0, 100),
            'description' => $desc,
            'timestamp' => now(),
            'ip_address' => $ip,
            'severity' => in_array($severity, ['low', 'medium', 'high', 'critical']) ? $severity : 'medium',
        ]);

        $incidentCreated = false;
        if (in_array($severity, ['high', 'critical'])) {
            $cutoff = Carbon::now()->subSeconds(90);
            $already = CheatingIncident::where('exam_attempt_id', $attemptId)
                ->where('incident_type', $type)
                ->where('incident_timestamp', '>=', $cutoff)
                ->exists();

            if (! $already) {
                CheatingIncident::create([
                    'exam_attempt_id' => $attemptId,
                    'user_id' => 0,
                    'incident_type' => substr($type, 0, 50),
                    'confidence_level' => $severity === 'critical' ? 'high' : 'medium',
                    'incident_timestamp' => now(),
                ]);
                $incidentCreated = true;
            }
        }

        return response()->json(['success' => true, 'incident_created' => $incidentCreated]);
    }

    public function capture(Request $request)
    {
        $imageData = $request->input('image_data', '');
        $attemptId = (int) ($request->input('exam_attempt_id', session('exam_attempt_id', 0)));
        $type = $request->input('activity_type', 'screenshot');
        $desc = $request->input('description', '');
        $isPreStart = $type === 'pre_start';

        if (empty($imageData) || $attemptId <= 0) {
            return response()->json(['success' => false, 'message' => 'Missing data'], 400);
        }

        $attempt = Attempt::find($attemptId);
        if (! $attempt || ! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return response()->json(['success' => false, 'message' => 'Invalid attempt for this device'], 403);
        }

        if ($isPreStart && ExamSecurityLog::where('exam_attempt_id', $attemptId)->where('activity_type', 'pre_start')->exists()) {
            $this->markAttemptExamClockStarted($attemptId);

            return response()->json([
                'success' => true,
                'stored' => false,
                'duplicate' => true,
                'message' => 'Pre-start verification already recorded.',
            ]);
        }

        $jpegCount = $this->proctoringJpegCaptureCount($attemptId);
        if ($jpegCount >= 4) {
            if ($isPreStart) {
                ExamSecurityLog::create([
                    'exam_attempt_id' => $attemptId,
                    'user_id' => 0,
                    'activity_type' => 'pre_start',
                    'description' => $desc !== '' ? $desc : 'Pre-start identity verification (log only; JPEG storage quota reached for this attempt)',
                    'timestamp' => now(),
                    'ip_address' => $request->ip(),
                    'severity' => 'low',
                ]);

                $this->markAttemptExamClockStarted($attemptId);

                return response()->json([
                    'success' => true,
                    'stored' => false,
                    'captures_full' => true,
                    'message' => 'Verification recorded; maximum stored JPEG captures reached for this attempt.',
                ]);
            }

            return response()->json([
                'success' => true,
                'stored' => false,
                'captures_full' => true,
                'message' => 'Maximum of 4 camera captures are stored per attempt.',
            ]);
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $m)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }

        $decoded = base64_decode($imageData);
        if (! $decoded) {
            return response()->json(['success' => false, 'message' => 'Invalid image data'], 400);
        }

        $filename = 'proctoring/'.$attemptId.'_'.$type.'_'.time().'.jpg';
        Storage::disk('public')->put($filename, $decoded);

        $severity = $isPreStart ? 'low' : 'medium';
        ExamSecurityLog::create([
            'exam_attempt_id' => $attemptId,
            'user_id' => 0,
            'activity_type' => substr($type, 0, 100),
            'description' => $desc !== '' ? $desc : ($isPreStart ? 'Pre-start identity verification' : 'Screenshot captured: '.$type),
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'severity' => $severity,
        ]);

        if ($isPreStart) {
            $this->markAttemptExamClockStarted($attemptId);
        }

        return response()->json([
            'success' => true,
            'stored' => true,
            'filename' => $filename,
            'captures_remaining' => max(0, 4 - $this->proctoringJpegCaptureCount($attemptId)),
        ]);
    }

    public function saveRecording(Request $request)
    {
        if (! $request->hasFile('recording')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $attemptId = (int) ($request->input('exam_attempt_id', session('exam_attempt_id', 0)));
        $reason = $request->input('reason', 'evidence');

        $attempt = Attempt::find($attemptId);
        if (! $attempt || ! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return response()->json(['success' => false, 'message' => 'Invalid attempt for this device'], 403);
        }

        $file = $request->file('recording');
        $filename = 'proctoring/'.$attemptId.'_'.$reason.'_'.time().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('', $filename, 'public');

        ExamSessionProctoring::where('exam_attempt_id', $attemptId)
            ->update(['video_recording_path' => $path]);

        return response()->json(['success' => true, 'filename' => $filename, 'path' => $path]);
    }

    /**
     * JPEG frames saved by {@see capture()} (proctoring/{attemptId}_*.jpg).
     */
    private function proctoringJpegCaptureCount(int $attemptId): int
    {
        $disk = Storage::disk('public');
        if (! $disk->exists('proctoring')) {
            return 0;
        }

        $n = 0;
        foreach ($disk->files('proctoring') as $path) {
            $base = basename($path);
            if (! preg_match('/^(\d+)_/', $base, $m) || (int) $m[1] !== $attemptId) {
                continue;
            }
            if (strtolower((string) pathinfo($base, PATHINFO_EXTENSION)) === 'jpg') {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Official exam timer starts at first successful pre_start verification.
     */
    private function markAttemptExamClockStarted(int $attemptId): void
    {
        Attempt::query()
            ->whereKey($attemptId)
            ->whereNull('start_time')
            ->update(['start_time' => now()]);
    }
}
