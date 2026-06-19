<?php

namespace App\Http\Middleware;

use App\Models\Attempt;
use App\Models\ExamAllowedIndex;
use App\Support\ExamDeviceBinding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('student_index') || ! session()->has('exam_id')) {
            // Mobile browsers may evict PHP session state when app/tab is backgrounded.
            // Recover session from the encrypted resume cookie when possible.
            $resumeRaw = $request->cookie('exam_resume');
            if ($resumeRaw) {
                $resume = json_decode($resumeRaw, true);
                $examId = (int) ($resume['exam_id'] ?? 0);
                $studentIndex = ExamAllowedIndex::normalize((string) ($resume['student_index'] ?? ''));
                $studentName = (string) ($resume['student_fullname'] ?? '');

                if ($examId > 0 && $studentIndex !== '') {
                    $attempt = Attempt::where('exam_id', $examId)
                        ->whereRaw('UPPER(TRIM(student_index)) = ?', [$studentIndex])
                        ->where('status', 'ongoing')
                        ->latest('id')
                        ->first();

                    if ($attempt) {
                        $cookieDevice = ExamDeviceBinding::currentKey($request);
                        if ($attempt->exam_device_key) {
                            if ($cookieDevice === '' || ! hash_equals($attempt->exam_device_key, $cookieDevice)) {
                                Cookie::queue(Cookie::forget('exam_resume'));
                            } else {
                                $resumeDevice = (string) ($resume['exam_device_key'] ?? '');
                                if ($resumeDevice !== '' && ! hash_equals($attempt->exam_device_key, $resumeDevice)) {
                                    Cookie::queue(Cookie::forget('exam_resume'));
                                } else {
                                    session([
                                        'exam_id' => $examId,
                                        'student_index' => $studentIndex,
                                        'student_fullname' => $studentName ?: ($attempt->student_fullname ?? ''),
                                        'exam_attempt_id' => $attempt->id,
                                        'proctoring_enabled' => true,
                                        'proctoring_exam_id' => $examId,
                                    ]);
                                }
                            }
                        } else {
                            session([
                                'exam_id' => $examId,
                                'student_index' => $studentIndex,
                                'student_fullname' => $studentName ?: ($attempt->student_fullname ?? ''),
                                'exam_attempt_id' => $attempt->id,
                                'proctoring_enabled' => true,
                                'proctoring_exam_id' => $examId,
                            ]);
                        }
                    }
                }
            }
        }

        if (! session()->has('student_index') || ! session()->has('exam_id')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if (auth()->check() && auth()->user()->isStudent()) {
                return redirect()->route('student.dashboard')->withErrors(['exam_code' => 'Session expired or not found. Please join the exam again.']);
            }

            return redirect()->route('login')->withErrors(['email' => 'Please log in to take an exam.']);
        }

        return $next($request);
    }
}
