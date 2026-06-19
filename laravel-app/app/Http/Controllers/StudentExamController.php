<?php

namespace App\Http\Controllers;

use App\Data\AnswerMarkingEvaluation;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\ExamSecurityLog;
use App\Models\ExamSessionProctoring;
use App\Models\Question;
use App\Services\ProctoringPenaltyService;
use App\Services\QuestionBankService;
use App\Services\ScriptMarkingApiService;
use App\Support\AnswerScoring;
use App\Support\AnswerTextNormalizer;
use App\Support\ExamDeviceBinding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StudentExamController extends Controller
{
    // ── Entry page ──────────────────────────────────────────────────────────

    public function entryShow(Request $request)
    {
        return redirect()->route('login')->withErrors(['email' => 'Please log in to your student account to access exams.']);
    }

    public function entryStore(Request $request)
    {
        $action = $request->input('action', 'exam');

        if ($action === 'results') {
            $request->validate([
                'exam_code' => 'required|string',
                'student_index' => 'required|string|max:50',
            ]);
        } else {
            $request->validate([
                'exam_code' => 'required|string',
                'student_index' => 'required|string|max:50',
                'student_name' => 'required|string|max:150',
                'student_email' => 'required|email|max:150',
            ]);
        }

        $normalizedIndex = ExamAllowedIndex::normalize($request->student_index);
        if ($normalizedIndex === '') {
            return back()->withErrors(['student_index' => 'Enter a valid index number.'])->withInput();
        }

        $code = strtoupper(trim($request->exam_code));
        $exam = Exam::where('exam_code', $code)->first();

        if (! $exam || ! $exam->is_published) {
            return back()->withErrors(['exam_code' => 'Invalid or unpublished exam code.'])->withInput();
        }

        $now = Carbon::now();
        if ($exam->start_datetime && $now->lt($exam->start_datetime)) {
            return back()->withErrors(['exam_code' => 'This exam has not started yet.'])->withInput();
        }
        if ($exam->end_datetime && $now->gt($exam->end_datetime)) {
            return back()->withErrors(['exam_code' => 'This exam has ended.'])->withInput();
        }

        if ($action === 'results') {
            $attempt = $this->attemptsForExamStudent($exam->id, $normalizedIndex)
                ->where('status', 'completed')
                ->latest('submit_time')
                ->first();

            if (! $attempt) {
                return back()->withErrors(['exam_code' => 'No completed attempt found for that index number.'])->withInput();
            }

            session([
                'student_index' => $normalizedIndex,
                'student_fullname' => $attempt->student_fullname ?? '',
                'student_email' => $attempt->student_email ?? '',
                'exam_id' => $exam->id,
                'exam_attempt_id' => $attempt->id,
            ]);

            return redirect()->route('student.result');
        }

        if (! $exam->hasAllowedIndexWhitelist()) {
            return back()->withErrors(['student_index' => 'This exam has no registered index numbers yet. Your lecturer must upload the class list before anyone can start.'])->withInput();
        }
        if (! $exam->isStudentIndexAllowed($normalizedIndex)) {
            return back()->withErrors(['student_index' => 'Your index number is not registered for this exam. Contact your lecturer if you think this is a mistake.'])->withInput();
        }

        $deviceKey = ExamDeviceBinding::currentKey($request);
        if ($deviceKey === '') {
            ExamDeviceBinding::ensureDeviceCookieQueued($request);

            return back()->withErrors([
                'exam_code' => 'Cookies are required. Open the student portal page once so a device cookie can be set, then submit again.',
            ])->withInput();
        }

        $ongoing = $this->attemptsForExamStudent($exam->id, $normalizedIndex)
            ->where('status', 'ongoing')
            ->first();

        if ($ongoing) {
            if ($ongoing->exam_device_key && ! hash_equals($ongoing->exam_device_key, $deviceKey)) {
                return back()->withErrors([
                    'exam_code' => 'This exam is already active in another browser or device. Continue only there, or wait until that session ends.',
                ])->withInput();
            }
            if (
                ($ongoing->exam_device_key === null || $ongoing->exam_device_key === '')
                && ! empty($ongoing->locked_by_session)
                && $ongoing->locked_by_session !== session()->getId()
            ) {
                return back()->withErrors([
                    'exam_code' => 'This exam is currently active in another browser. Only one session is allowed.',
                ])->withInput();
            }
        }

        session([
            'student_index' => $normalizedIndex,
            'student_fullname' => $request->student_name,
            'student_email' => $request->student_email,
            'exam_id' => $exam->id,
        ]);
        $this->queueExamResumeCookie($request, (int) $exam->id, $normalizedIndex, (string) $request->student_name, (string) $request->student_email);

        return redirect()->route('student.exam');
    }

    public function joinAuthenticated(Request $request)
    {
        $request->validate([
            'exam_code' => 'required|string',
            'student_index' => 'required|string|max:50',
        ]);

        $normalizedIndex = ExamAllowedIndex::normalize($request->student_index);
        if ($normalizedIndex === '') {
            return back()->withErrors(['exam_code' => 'Enter a valid index number.'])->withInput();
        }

        $code = strtoupper(trim($request->exam_code));
        $exam = Exam::where('exam_code', $code)->first();

        if (! $exam || ! $exam->is_published) {
            return back()->withErrors(['exam_code' => 'Invalid or unpublished exam code.'])->withInput();
        }

        $now = Carbon::now();
        if ($exam->start_datetime && $now->lt($exam->start_datetime)) {
            return back()->withErrors(['exam_code' => 'This exam has not started yet.'])->withInput();
        }
        if ($exam->end_datetime && $now->gt($exam->end_datetime)) {
            return back()->withErrors(['exam_code' => 'This exam has ended.'])->withInput();
        }

        if (! $exam->hasAllowedIndexWhitelist()) {
            return back()->withErrors(['exam_code' => 'This exam has no registered index numbers yet. Your lecturer must upload the class list before anyone can start.'])->withInput();
        }
        if (! $exam->isStudentIndexAllowed($normalizedIndex)) {
            return back()->withErrors(['exam_code' => 'Your index number is not registered for this exam. Contact your lecturer if you think this is a mistake.'])->withInput();
        }

        $deviceKey = ExamDeviceBinding::currentKey($request);
        if ($deviceKey === '') {
            ExamDeviceBinding::ensureDeviceCookieQueued($request);
            return back()->withErrors([
                'exam_code' => 'Cookies are required. Try submitting again now that a device cookie has been set.',
            ])->withInput();
        }

        $ongoing = $this->attemptsForExamStudent($exam->id, $normalizedIndex)
            ->where('status', 'ongoing')
            ->first();

        if ($ongoing) {
            if ($ongoing->exam_device_key && ! hash_equals($ongoing->exam_device_key, $deviceKey)) {
                return back()->withErrors([
                    'exam_code' => 'This exam is already active in another browser or device. Continue only there, or wait until that session ends.',
                ])->withInput();
            }
            if (
                ($ongoing->exam_device_key === null || $ongoing->exam_device_key === '')
                && ! empty($ongoing->locked_by_session)
                && $ongoing->locked_by_session !== session()->getId()
            ) {
                return back()->withErrors([
                    'exam_code' => 'This exam is currently active in another browser. Only one session is allowed.',
                ])->withInput();
            }
        }

        $user = auth()->user();

        session([
            'student_index' => $normalizedIndex,
            'student_fullname' => $user->name,
            'student_email' => $user->email,
            'exam_id' => $exam->id,
        ]);
        $this->queueExamResumeCookie($request, (int) $exam->id, $normalizedIndex, (string) $user->name, (string) $user->email);

        return redirect()->route('student.exam');
    }

    // ── Take exam ───────────────────────────────────────────────────────────

    public function takeExam(Request $request)
    {
        $examId = session('exam_id');
        $studentIndex = ExamAllowedIndex::normalize((string) session('student_index'));
        if ($studentIndex === '') {
            return redirect()->route('student.entry')->withErrors(['student_index' => 'Session expired. Enter your details again.']);
        }
        session(['student_index' => $studentIndex]);

        $studentName = session('student_fullname');
        $studentEmail = session('student_email');

        $exam = Exam::findOrFail($examId);
        if (! $exam->is_published) {
            abort(403, 'Exam not available.');
        }

        if (! $exam->hasAllowedIndexWhitelist() || ! $exam->isStudentIndexAllowed($studentIndex)) {
            return redirect()->route('student.entry')->withErrors(['student_index' => 'Your index number is not registered for this exam, or the class list is not set up yet.']);
        }

        $requiresProctoring = $exam->requiresProctoring();

        if ($requiresProctoring) {
            session(['proctoring_enabled' => true, 'proctoring_exam_id' => $examId]);
        } else {
            session()->forget(['proctoring_enabled', 'proctoring_exam_id']);
        }

        $deviceKey = ExamDeviceBinding::currentKey($request);
        if ($deviceKey === '') {
            return redirect()->route('student.entry')->withErrors([
                'exam_code' => 'This browser has no device cookie. Open the student portal first, then start the exam again.',
            ]);
        }

        $anotherDevice = false;
        $attempt = null;

        DB::transaction(function () use ($exam, $examId, $studentIndex, $studentName, $studentEmail, $deviceKey, $requiresProctoring, &$anotherDevice, &$attempt): void {
            $row = Attempt::query()
                ->where('exam_id', $examId)
                ->whereRaw('UPPER(TRIM(student_index)) = ?', [$studentIndex])
                ->where('status', 'ongoing')
                ->lockForUpdate()
                ->first();

            if ($row) {
                if ($row->exam_device_key && ! hash_equals($row->exam_device_key, $deviceKey)) {
                    $anotherDevice = true;

                    return;
                }

                $row->update([
                    'locked_by_session' => session()->getId(),
                    'student_email' => $studentEmail,
                    'student_fullname' => $studentName,
                    'exam_device_key' => $row->exam_device_key ?: $deviceKey,
                ]);
                $attempt = $row->fresh();
            } else {
                $attemptCount = $this->attemptsForExamStudent((int) $examId, $studentIndex)->count();

                if ($attemptCount >= $exam->attempts_allowed) {
                    return;
                }

                $attempt = Attempt::create([
                    'exam_id' => $examId,
                    'student_index' => $studentIndex,
                    'student_fullname' => $studentName,
                    'student_email' => $studentEmail,
                    'locked_by_session' => session()->getId(),
                    'exam_device_key' => $deviceKey,
                    // Proctored exams: official timer starts only after pre_start verification (see capture()).
                    'start_time' => $requiresProctoring ? null : now(),
                ]);
            }
        });

        if ($anotherDevice) {
            return redirect()->route('student.entry')->withErrors([
                'exam_code' => 'This exam is already open on another browser or device. You can only use one at a time.',
            ]);
        }

        if (! $attempt) {
            return redirect()->route('student.result');
        }

        session(['exam_attempt_id' => $attempt->id]);
        $this->queueExamResumeCookie($request, (int) $examId, (string) $studentIndex, (string) $studentName, (string) $studentEmail);

        if ($requiresProctoring && ! $this->hasPreStartVerification($attempt)) {
            return redirect()->route('student.verification-required');
        }

        // Heal edge cases where pre_start exists but exam clock was never started.
        if ($requiresProctoring && $this->hasPreStartVerification($attempt) && $attempt->start_time === null) {
            $attempt->update(['start_time' => now()]);
            $attempt = $attempt->fresh();
        }

        $isUnlimited = ($exam->duration <= 0);
        $remainingSec = 0;

        if (! $isUnlimited) {
            $remainingSec = $this->attemptRemainingSeconds($exam, $attempt) ?? 0;

            if ($remainingSec <= 0) {
                if (! $exam->submission_password_enabled) {
                    return $this->doAutoSubmit($attempt, $exam);
                }
                $remainingSec = 0;
            }
        }

        $questions = $this->resolveQuestionsForAttempt($exam, $attempt);
        $savedAnswers = Answer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        return view('student.take-exam', compact(
            'exam', 'attempt', 'questions', 'savedAnswers',
            'remainingSec', 'isUnlimited', 'requiresProctoring',
            'studentName', 'studentIndex'
        ));
    }

    /**
     * Dedicated step for pre-start verification when proctoring is required but no
     * {@see ExamSecurityLog} with {@code activity_type = pre_start} exists yet.
     */
    public function verificationRequired(Request $request)
    {
        $examId = session('exam_id');
        $studentIndex = ExamAllowedIndex::normalize((string) session('student_index'));
        if ($studentIndex === '' || ! $examId) {
            return redirect()->route('student.entry')->withErrors([
                'exam_code' => 'Session expired. Enter your details again.',
            ]);
        }

        $exam = Exam::findOrFail((int) $examId);
        if (! $exam->is_published) {
            abort(403, 'Exam not available.');
        }

        if (! $exam->requiresProctoring()) {
            return redirect()->route('student.exam');
        }

        if (! $exam->hasAllowedIndexWhitelist() || ! $exam->isStudentIndexAllowed($studentIndex)) {
            return redirect()->route('student.entry')->withErrors([
                'student_index' => 'Your index number is not registered for this exam, or the class list is not set up yet.',
            ]);
        }

        $attempt = $this->attemptsForExamStudent((int) $examId, $studentIndex)
            ->where('status', 'ongoing')
            ->first();

        if (! $attempt) {
            return redirect()->route('student.entry')->withErrors([
                'exam_code' => 'No active exam session found. Start the exam again from the entry page.',
            ]);
        }

        if (! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return redirect()->route('student.entry')->withErrors([
                'exam_code' => 'This exam session is bound to another browser. Continue only on the device where you started.',
            ]);
        }

        if ($this->hasPreStartVerification($attempt)) {
            return redirect()->route('student.exam');
        }

        session([
            'proctoring_enabled' => true,
            'proctoring_exam_id' => (int) $examId,
        ]);

        $studentName = session('student_fullname');
        $studentEmail = session('student_email');

        return view('student.verification-required', compact(
            'exam',
            'attempt',
            'studentName',
            'studentIndex',
            'studentEmail',
        ));
    }

    // ── Autosave ─────────────────────────────────────────────────────────────

    public function autosave(Request $request)
    {
        $examId = session('exam_id');
        $studentIndex = ExamAllowedIndex::normalize((string) session('student_index'));

        $attempt = $this->attemptsForExamStudent((int) $examId, $studentIndex)
            ->where('status', 'ongoing')
            ->first();

        if (! $attempt) {
            return response()->json(['success' => false, 'error' => 'No active attempt.'], 403);
        }

        if (! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return response()->json(['success' => false, 'error' => 'This exam is active in another browser.'], 403);
        }

        $remainingForAttempt = $this->attemptRemainingSeconds($attempt->exam, $attempt);
        if ($remainingForAttempt !== null && $remainingForAttempt <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Time has expired for this attempt.',
                'time_expired' => true,
            ], 403);
        }

        $questionId = (int) $request->input('question_id');
        $answerType = $request->input('answer_type');

        $question = $attempt->exam->questions()->find($questionId);
        if (! $question) {
            return response()->json(['success' => false, 'error' => 'Question not found.'], 404);
        }

        $existing = Answer::where('attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->first();

        $data = ['attempt_id' => $attempt->id, 'question_id' => $questionId];

        if ($answerType === 'mcq') {
            $selected = strtoupper(substr($request->input('selected_option', ''), 0, 1));
            $isCorrect = ($selected === $question->correct_option) ? 1 : 0;
            $data += ['selected_option' => $selected ?: null, 'is_correct' => $isCorrect];
        } elseif ($answerType === 'theory' || $answerType === 'short_answer' || $answerType === 'fill_in') {
            $text = $request->input('theory_answer', '');
            $isCorrect = null;
            if ($answerType === 'fill_in' && $question->correct_option) {
                $isCorrect = (strtolower(trim($text)) === strtolower(trim($question->correct_option))) ? 1 : 0;
            }
            $data += ['theory_answer' => $text ?: null, 'is_correct' => $isCorrect];
        } elseif ($answerType === 'file' && $request->hasFile('file_answer')) {
            $path = $request->file('file_answer')->store('submissions', 'public');
            $data += ['file_upload' => $path];
        }

        if ($existing) {
            $existing->update($data);
        } else {
            Answer::create($data);
        }

        return response()->json(['success' => true, 'saved_at' => now()->toISOString()]);
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    public function submit(Request $request)
    {
        $examId = session('exam_id');
        $studentIndex = ExamAllowedIndex::normalize((string) session('student_index'));

        $exam = Exam::findOrFail($examId);

        $attempt = $this->attemptsForExamStudent((int) $examId, $studentIndex)
            ->where('status', 'ongoing')
            ->first();

        if (! $attempt) {
            return redirect()->route('student.result');
        }

        if (! ExamDeviceBinding::matchesAttempt($request, $attempt)) {
            return redirect()->route('student.entry')->withErrors([
                'exam_code' => 'This exam session is bound to another browser. Continue only on the device where you started.',
            ]);
        }

        $remainingForAttempt = $this->attemptRemainingSeconds($exam, $attempt);
        if ($remainingForAttempt !== null && $remainingForAttempt <= 0) {
            $request->merge(['auto_submit' => true]);
        }

        // Check proctoring bypass
        $requiresProctoring = $exam->requiresProctoring();
        $hasValidProctoring = session('proctoring_enabled') && (int) session('proctoring_exam_id') === (int) $examId;

        if ($requiresProctoring && ! $hasValidProctoring) {
            ExamSecurityLog::create([
                'exam_attempt_id' => $attempt->id,
                'user_id' => 0,
                'activity_type' => 'proctoring_bypass',
                'description' => 'Attempted to submit exam without proper proctoring session',
                'timestamp' => now(),
                'ip_address' => $request->ip(),
                'severity' => 'critical',
            ]);

            return redirect()->route('student.terminated', [
                'reason' => 'Proctoring bypass attempt detected',
                'mark_violated' => 1,
            ]);
        }

        if ($requiresProctoring && ! $this->hasPreStartVerification($attempt)) {
            return redirect()->route('student.verification-required')
                ->with('verification_notice', 'A proctoring verification photo is required before you can continue this exam.');
        }

        // Manual submissions must answer every question before submit is accepted.
        $isAutoSubmit = $request->boolean('auto_submit');
        $questions = $this->resolveQuestionsForAttempt($exam, $attempt);

        if ($exam->submission_password_enabled) {
            $submittedPassword = (string) $request->input('submission_password', '');
            if ($submittedPassword === '' || empty($exam->submission_password) || ! Hash::check($submittedPassword, (string) $exam->submission_password)) {
                return back()->withErrors([
                    'submission_password' => 'Invalid submission password. Ask your lecturer/admin for the correct password.',
                ])->withInput();
            }
        }

        if (! $isAutoSubmit) {
            $unanswered = 0;
            foreach ($questions as $q) {
                $existing = Answer::where('attempt_id', $attempt->id)
                    ->where('question_id', $q->id)
                    ->first();

                if ($this->qType($q) === 'mcq') {
                    $selected = $request->input("answers.{$q->id}");
                    if ($selected === null && $existing) {
                        $selected = $existing->selected_option;
                    }
                    if (trim((string) $selected) === '') {
                        $unanswered++;
                    }
                } elseif ($this->qType($q) === 'fill_in') {
                    $value = $request->input("fill_in_answers.{$q->id}");
                    if ($value === null && $existing) {
                        $value = $existing->theory_answer;
                    }
                    if (trim((string) $value) === '') {
                        $unanswered++;
                    }
                } elseif (in_array($this->qType($q), ['theory', 'short_answer'], true)) {
                    $value = $request->input("theory_answers.{$q->id}");
                    if ($value === null && $existing) {
                        $value = $existing->theory_answer;
                    }
                    if (trim((string) $value) === '') {
                        $unanswered++;
                    }
                } elseif ($this->qType($q) === 'file') {
                    $key = "file_answers_{$q->id}";
                    $hasFileNow = $request->hasFile($key);
                    $hasExisting = $existing && ! empty($existing->file_upload);
                    if (! $hasFileNow && ! $hasExisting) {
                        $unanswered++;
                    }
                }
            }

            if ($unanswered > 0) {
                return back()->withErrors([
                    'exam' => 'Please answer all questions before submitting your exam.',
                ])->withInput();
            }
        }

        // Mark proctoring completed
        if ($requiresProctoring) {
            ExamSessionProctoring::where('exam_attempt_id', $attempt->id)
                ->where('proctoring_status', 'active')
                ->update(['proctoring_status' => 'completed', 'end_time' => now()]);
        }

        session()->forget(['proctoring_enabled', 'proctoring_exam_id']);

        // Score all questions
        $totalScore = 0;
        $totalMarks = 0;

        foreach ($questions as $q) {
            $existing = Answer::where('attempt_id', $attempt->id)
                ->where('question_id', $q->id)
                ->first();

            $selected = $existing->selected_option ?? null;
            $theoryAns = $existing->theory_answer ?? null;
            $filePath = $existing->file_upload ?? null;
            $isCorrect = 0;
            $marksAwarded = null;

            if ($this->qType($q) === 'mcq') {
                if (! $isAutoSubmit) {
                    $selected = $request->input("answers.{$q->id}");
                }
                $isCorrect = ($selected && $selected === $q->correct_option) ? 1 : 0;
                if ($isCorrect) {
                    $totalScore += $q->marks;
                    $marksAwarded = (float) ($q->marks ?? 0);
                }
            } elseif ($this->qType($q) === 'fill_in') {
                if (! $isAutoSubmit) {
                    $theoryAns = $request->input("fill_in_answers.{$q->id}");
                }
                if (AnswerTextNormalizer::fillInMatches($theoryAns, $q->correct_option)) {
                    $isCorrect = 1;
                    $totalScore += $q->marks;
                    $marksAwarded = (float) ($q->marks ?? 0);
                }
            } elseif (in_array($this->qType($q), ['theory', 'short_answer'], true)) {
                if (! $isAutoSubmit) {
                    $theoryAns = $request->input("theory_answers.{$q->id}");
                }
            } elseif ($this->qType($q) === 'file') {
                if (! $isAutoSubmit) {
                    $key = "file_answers_{$q->id}";
                    if ($request->hasFile($key)) {
                        $filePath = $request->file($key)->store('submissions', 'public');
                    }
                }
            }

            $totalMarks += $q->marks;

            $answerData = [
                'attempt_id' => $attempt->id,
                'question_id' => $q->id,
                'selected_option' => $selected,
                'theory_answer' => $theoryAns ?: null,
                'file_upload' => $filePath,
                'is_correct' => $isCorrect,
            ];
            if ($marksAwarded !== null) {
                $answerData['marks_awarded'] = $marksAwarded;
            }

            if ($existing) {
                $existing->update($answerData);
            } else {
                Answer::create($answerData);
            }
        }

        $notes = $attempt->notes ?? '';
        if ($isAutoSubmit) {
            $notes .= ' Auto-submitted due to time expiration.';
        }

        $attempt->update([
            'score' => $totalScore,
            'total_marks' => $totalMarks,
            'submit_time' => now(),
            'status' => 'completed',
            'notes' => trim($notes),
        ]);

        // Auto-mark theory/fill_in immediately so marks are available right after submit
        $this->applyScriptAutoMarking($attempt);
        $attempt = $attempt->fresh();
        app(ProctoringPenaltyService::class)->setRawScoreFromGrading(
            $attempt,
            (float) ($attempt->score ?? 0)
        );
        $this->refreshQuestionBankStatsForQuestions($questions->pluck('question_bank_id')->filter()->unique()->values()->all());

        return redirect()->route('student.result');
    }

    // ── Result ────────────────────────────────────────────────────────────────

    public function result()
    {
        $examId = session('exam_id');
        $studentIndex = ExamAllowedIndex::normalize((string) session('student_index'));
        $studentName = session('student_fullname');

        $exam = Exam::findOrFail($examId);

        $attempt = $this->attemptsForExamStudent((int) $examId, $studentIndex)
            ->whereIn('status', ['completed'])
            ->latest('submit_time')
            ->first();

        return view('student.result', compact('exam', 'attempt', 'studentName', 'studentIndex'));
    }

    // ── Terminated ────────────────────────────────────────────────────────────

    public function terminated(Request $request)
    {
        $reason = $request->query('reason', 'Unknown reason');
        $attemptId = $request->query('attempt_id', session('exam_attempt_id'));
        $markViolated = $request->boolean('mark_violated');

        if ($attemptId && $markViolated) {
            ExamSessionProctoring::where('exam_attempt_id', $attemptId)
                ->update(['proctoring_status' => 'violated', 'end_time' => now()]);
        }

        return view('student.terminated', compact('reason', 'markViolated'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function doAutoSubmit(Attempt $attempt, Exam $exam)
    {
        $notes = ($attempt->notes ?? '').' Auto-submitted due to time expiration.';
        $questions = $this->resolveQuestionsForAttempt($exam, $attempt);
        $totalScore = 0;
        $totalMarks = 0;

        foreach ($questions as $q) {
            $existing = Answer::where('attempt_id', $attempt->id)->where('question_id', $q->id)->first();
            if ($this->qType($q) === 'mcq' && $existing && $existing->selected_option === $q->correct_option) {
                $totalScore += $q->marks;
                $existing->update([
                    'is_correct' => 1,
                    'marks_awarded' => (float) ($q->marks ?? 0),
                ]);
            }
            if ($this->qType($q) === 'fill_in' && $existing && AnswerTextNormalizer::fillInMatches($existing->theory_answer, $q->correct_option)) {
                $totalScore += $q->marks;
                $existing->update([
                    'is_correct' => 1,
                    'marks_awarded' => (float) ($q->marks ?? 0),
                    'evaluation_status' => 'correct',
                    'evaluation_reason' => 'Deterministic match after auto-submit (normalized text).',
                    'expected_answer_snapshot' => $q->correct_option,
                ]);
            }
            $totalMarks += $q->marks;
        }

        $attempt->update([
            'score' => $totalScore, 'total_marks' => $totalMarks,
            'submit_time' => now(), 'status' => 'completed', 'notes' => trim($notes),
        ]);

        $this->applyScriptAutoMarking($attempt);
        $attempt = $attempt->fresh();
        app(ProctoringPenaltyService::class)->setRawScoreFromGrading(
            $attempt,
            (float) ($attempt->score ?? 0)
        );
        $this->refreshQuestionBankStatsForQuestions($questions->pluck('question_bank_id')->filter()->unique()->values()->all());

        return redirect()->route('student.result');
    }

    private function applyScriptAutoMarking(Attempt $attempt): void
    {
        $service = app(ScriptMarkingApiService::class);
        $answers = Answer::with('question')->where('attempt_id', $attempt->id)->get();
        $updated = false;

        foreach ($answers as $answer) {
            $question = $answer->question;
            if (! $question) {
                continue;
            }

            $qType = $this->qType($question);
            $max = (float) ($question->marks ?? 0);
            $scriptTextTypes = ['theory', 'fill_in', 'short_answer'];
            $apiQuestionType = $qType === 'short_answer' ? 'theory' : $qType;

            if ($qType === 'fill_in' && (int) $answer->is_correct === 1) {
                $current = (float) ($answer->marks_awarded ?? 0);
                if ($current + 0.00001 < $max || $answer->marks_awarded === null) {
                    $answer->update([
                        'marks_awarded' => $max,
                        'evaluation_status' => 'correct',
                        'evaluation_reason' => 'Deterministic match: normalized student text equals the expected answer.',
                        'expected_answer_snapshot' => $question->correct_option,
                    ]);
                    $updated = true;
                }

                continue;
            }

            if (! in_array($qType, $scriptTextTypes, true)) {
                continue;
            }

            $text = trim((string) ($answer->theory_answer ?? ''));
            $logPipeline = (bool) config('marking.api.log_pipeline', false);

            if (! $service->shouldMarkByApi($qType)) {
                if ($text !== '') {
                    $answer->update([
                        'marks_awarded' => 0.0,
                        'ai_marks_awarded' => null,
                        'ai_confidence' => null,
                        'ai_concept_coverage' => null,
                        'detected_concepts' => null,
                        'missing_concepts' => null,
                        'rubric_concepts' => null,
                        'evaluation_status' => 'ai_marking_disabled',
                        'evaluation_reason' => 'Script marking is disabled or not configured (check SCRIPT_MARKING_API_ENABLED, SCRIPT_MARKING_DRIVER=external, SCRIPT_MARKING_API_URL or AI_SERVICE_URL, and SCRIPT_MARKING_USE_FOR_FILL_IN for fill-in). Laravel OpenAI/Gemini keys are not required when using the external driver.',
                        'expected_answer_snapshot' => $question->correct_option,
                        'ai_evaluation' => null,
                        'is_correct' => 0,
                    ]);
                    $updated = true;
                    if ($logPipeline) {
                        Log::info('[script_marking] applyScriptAutoMarking: skipped (marking not available)', [
                            'attempt_id' => $attempt->id,
                            'answer_id' => $answer->id,
                            'question_id' => $question->id,
                            'q_type' => $qType,
                        ]);
                    }
                }

                continue;
            }

            if ($text === '') {
                $answer->update([
                    'marks_awarded' => 0.0,
                    'ai_marks_awarded' => null,
                    'ai_confidence' => null,
                    'ai_concept_coverage' => null,
                    'detected_concepts' => null,
                    'missing_concepts' => null,
                    'rubric_concepts' => null,
                    'evaluation_status' => 'unanswered',
                    'evaluation_reason' => 'No text was submitted for this question.',
                    'expected_answer_snapshot' => $question->correct_option,
                    'ai_evaluation' => null,
                    'is_correct' => 0,
                ]);
                $updated = true;
                if ($logPipeline) {
                    Log::info('[script_marking] applyScriptAutoMarking: empty response stored as unanswered', [
                        'attempt_id' => $attempt->id,
                        'answer_id' => $answer->id,
                        'question_id' => $question->id,
                        'q_type' => $qType,
                    ]);
                }

                continue;
            }

            if ($logPipeline) {
                Log::info('[script_marking] applyScriptAutoMarking: calling AI', [
                    'attempt_id' => $attempt->id,
                    'answer_id' => $answer->id,
                    'question_id' => $question->id,
                    'q_type' => $qType,
                    'api_question_type' => $apiQuestionType,
                    'driver' => config('marking.driver', 'external'),
                    'external_url' => $service->resolveExternalMarkUrl(),
                    'marking_enabled' => (bool) config('marking.api.enabled'),
                ]);
            }

            $ev = $service->evaluateAnswer($question, $text, $apiQuestionType);
            if (! $ev instanceof AnswerMarkingEvaluation) {
                $answer->update([
                    'marks_awarded' => 0.0,
                    'ai_marks_awarded' => null,
                    'ai_confidence' => null,
                    'ai_concept_coverage' => null,
                    'detected_concepts' => null,
                    'missing_concepts' => null,
                    'rubric_concepts' => null,
                    'evaluation_status' => 'ai_unavailable',
                    'evaluation_reason' => 'External AI service unavailable or returned an error while marking this answer (FastAPI /mark-answer).',
                    'expected_answer_snapshot' => $question->correct_option,
                    'ai_evaluation' => null,
                    'is_correct' => 0,
                ]);
                $updated = true;
                if ($logPipeline) {
                    Log::info('[script_marking] applyScriptAutoMarking: AI returned no evaluation; stored ai_unavailable', [
                        'attempt_id' => $attempt->id,
                        'answer_id' => $answer->id,
                        'question_id' => $question->id,
                        'q_type' => $qType,
                    ]);
                }

                continue;
            }

            $marks = min($max, max(0.0, $ev->marksAwarded));

            $answer->update([
                'marks_awarded' => $marks,
                'ai_marks_awarded' => $marks,
                'ai_confidence' => $ev->confidence,
                'ai_concept_coverage' => $ev->conceptCoverage,
                'detected_concepts' => $ev->detectedConcepts,
                'missing_concepts' => $ev->missingConcepts,
                'rubric_concepts' => $ev->rubricConcepts,
                'evaluation_reason' => $ev->reason,
                'evaluation_status' => $ev->evaluationStatus,
                'expected_answer_snapshot' => $question->correct_option,
                'ai_evaluation' => $this->buildAiEvaluationSummary($ev),
                'is_correct' => $marks > 0 ? 1 : 0,
            ]);
            $updated = true;

            if ($logPipeline) {
                $answer->refresh();
                Log::info('[script_marking] applyScriptAutoMarking: saved AI evaluation', [
                    'attempt_id' => $attempt->id,
                    'answer_id' => $answer->id,
                    'question_id' => $question->id,
                    'q_type' => $qType,
                    'marks_awarded' => $answer->marks_awarded,
                    'ai_marks_awarded' => $answer->ai_marks_awarded,
                    'evaluation_status' => $answer->evaluation_status,
                    'detected_concepts' => $answer->detected_concepts,
                    'missing_concepts' => $answer->missing_concepts,
                    'contribution' => AnswerScoring::contribution($answer, $question),
                ]);
            }
        }

        if ($updated) {
            $totalScore = Answer::query()
                ->where('attempt_id', $attempt->id)
                ->with('question')
                ->get()
                ->sum(fn (Answer $ans) => AnswerScoring::contribution($ans, $ans->question));

            $attempt->update(['score' => $totalScore]);
        }

        if (config('app.debug')) {
            $breakdown = Answer::query()
                ->where('attempt_id', $attempt->id)
                ->with('question')
                ->get()
                ->map(function (Answer $ans) {
                    $q = $ans->question;

                    return [
                        'question_id' => $ans->question_id,
                        'type' => $q ? $this->qType($q) : null,
                        'marks_awarded' => $ans->marks_awarded,
                        'evaluation_status' => $ans->evaluation_status,
                        'is_correct' => $ans->is_correct,
                        'contribution' => AnswerScoring::contribution($ans, $q),
                    ];
                })
                ->all();

            Log::debug('applyScriptAutoMarking breakdown', [
                'attempt_id' => $attempt->id,
                'rows' => $breakdown,
                'score' => $attempt->fresh()?->score,
            ]);
        }
    }

    private function buildAiEvaluationSummary(AnswerMarkingEvaluation $ev): string
    {
        $parts = array_filter([$ev->reason, $ev->feedback]);

        return implode("\n\n", $parts);
    }

    private function attemptsForExamStudent(int $examId, string $normalizedIndex)
    {
        return Attempt::where('exam_id', $examId)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalizedIndex]);
    }

    /**
     * Seconds remaining for this timed attempt, or null when the exam has no countdown.
     */
    private function attemptRemainingSeconds(Exam $exam, Attempt $attempt): ?int
    {
        if ($exam->duration <= 0) {
            return null;
        }

        $start = $attempt->start_time;
        if ($start === null) {
            // Clock not started yet (e.g. waiting on proctoring verification) - full duration remains.
            return max(0, (int) $exam->duration * 60);
        }

        $deadline = Carbon::parse($start)->addMinutes((int) $exam->duration);
        $remaining = (int) floor($deadline->getTimestamp() - now()->getTimestamp());

        return max(0, $remaining);
    }

    private function qType(Question $q): string
    {
        return AnswerScoring::qType($q);
    }

    private function hasPreStartVerification(Attempt $attempt): bool
    {
        return ExamSecurityLog::where('exam_attempt_id', $attempt->id)
            ->where('activity_type', 'pre_start')
            ->exists();
    }

    private function resolveQuestionsForAttempt(Exam $exam, Attempt $attempt)
    {
        if (! $exam->shuffle_questions) {
            return $exam->questions()->get();
        }

        $allIds = $exam->questions()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        if ($allIds === []) {
            return collect();
        }

        $savedOrder = collect((array) ($attempt->question_order ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if ($savedOrder === []) {
            $order = collect($allIds)->shuffle()->values()->all();
        } else {
            $validSet = array_fill_keys($allIds, true);
            $order = [];
            foreach ($savedOrder as $id) {
                if (isset($validSet[$id]) && ! in_array($id, $order, true)) {
                    $order[] = $id;
                }
            }

            // Keep legacy attempts stable but append newly added questions if any.
            foreach ($allIds as $id) {
                if (! in_array($id, $order, true)) {
                    $order[] = $id;
                }
            }
        }

        if ($order !== $savedOrder) {
            $attempt->update(['question_order' => $order]);
        }

        [$orderSql, $orderBindings] = $this->orderByQuestionIdsCaseRaw($order);

        return $exam->questions()
            ->reorder()
            ->whereIn('id', $order)
            ->orderByRaw($orderSql, $orderBindings)
            ->get();
    }

    /**
     * Preserve a specific id sequence without MySQL-only FIELD(); works on PostgreSQL and SQLite.
     *
     * @param  list<int>  $orderedIds
     * @return array{0: string, 1: list<int>}
     */
    private function orderByQuestionIdsCaseRaw(array $orderedIds): array
    {
        if ($orderedIds === []) {
            return ['1', []];
        }

        $parts = [];
        $bindings = [];
        foreach ($orderedIds as $position => $id) {
            $parts[] = 'WHEN id = ? THEN ?';
            $bindings[] = $id;
            $bindings[] = $position;
        }
        $sql = 'CASE '.implode(' ', $parts).' ELSE ? END';
        $bindings[] = count($orderedIds);

        return [$sql, $bindings];
    }

    private function queueExamResumeCookie(Request $request, int $examId, string $studentIndex, string $studentName, string $studentEmail): void
    {
        $payload = json_encode([
            'exam_id' => $examId,
            'student_index' => $studentIndex,
            'student_fullname' => $studentName,
            'student_email' => $studentEmail,
            'exam_device_key' => ExamDeviceBinding::currentKey($request),
        ]);
        Cookie::queue(cookie('exam_resume', $payload, 720, '/'));
    }

    /**
     * @param  array<int, int|string>  $questionBankIds
     */
    private function refreshQuestionBankStatsForQuestions(array $questionBankIds): void
    {
        if ($questionBankIds === []) {
            return;
        }

        $service = app(QuestionBankService::class);
        foreach ($questionBankIds as $id) {
            $service->refreshQuestionUsageStats((int) $id);
        }
    }
}
