<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamFairnessService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExamFairnessController extends Controller
{
    public function analyze(Exam $exam, ExamFairnessService $fairness): RedirectResponse
    {
        $this->authorize('update', $exam);

        $result = $fairness->runAnalysis($exam, false);

        if (! $result['ok']) {
            return back()->withErrors(['fairness' => $result['message']]);
        }

        $msg = ! empty($result['skipped'])
            ? 'Fairness report is already up to date for these questions.'
            : 'Fairness analysis completed.';

        return redirect()
            ->route('lecturer.exams.fairness.show', $exam)
            ->with('success', $msg);
    }

    public function reanalyze(Exam $exam, ExamFairnessService $fairness): RedirectResponse
    {
        $this->authorize('update', $exam);

        $result = $fairness->runAnalysis($exam, true);

        if (! $result['ok']) {
            return back()->withErrors(['fairness' => $result['message']]);
        }

        return redirect()
            ->route('lecturer.exams.fairness.show', $exam)
            ->with('success', 'Fairness analysis updated.');
    }

    public function show(Exam $exam): Response|RedirectResponse
    {
        $this->authorize('update', $exam);

        $exam->load(['fairnessReport', 'questions' => fn ($q) => $q->orderBy('id')]);
        $report = $exam->fairnessReport;
        if (! $report) {
            return redirect()
                ->route('lecturer.exams.show', $exam)
                ->withErrors(['fairness' => 'Run fairness analysis first.']);
        }

        return Inertia::render('Lecturer/Exams/FairnessReport', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'duration' => $exam->duration,
                'course_name' => $exam->course_name,
                'course_code' => $exam->course_code,
                'is_published' => (bool) $exam->is_published,
            ],
            'report' => $report,
        ]);
    }
}
