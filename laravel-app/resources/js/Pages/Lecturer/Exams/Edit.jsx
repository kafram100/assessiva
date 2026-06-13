import PrimaryButton from '@/Components/PrimaryButton';
import LecturerLayout from '@/Layouts/LecturerLayout';
import ExamForm from '@/Pages/Lecturer/Exams/ExamForm';
import { Head, Link, router, useForm } from '@inertiajs/react';

function absoluteEntryUrl(entryRoute) {
    if (typeof window === 'undefined') {
        return entryRoute;
    }
    try {
        return new URL(entryRoute, window.location.origin).href;
    } catch {
        return entryRoute;
    }
}

function toDatetimeLocal(value) {
    if (!value) {
        return '';
    }
    const s = String(value);
    if (s.length >= 16) {
        return s.slice(0, 16).replace(' ', 'T');
    }
    return s;
}

export default function Edit({ exam, studentEntryUrl, linkableCourses }) {
    const qCount = exam.questions_count ?? 0;
    const form = useForm({
        title: exam.title ?? '',
        course_name: exam.course_name ?? '',
        course_code: exam.course_code ?? '',
        course_id: exam.course_id ?? '',
        exam_type: exam.exam_type ?? 'Exam',
        proctoring_mode: exam.proctoring_mode ?? 'auto',
        duration: exam.duration ?? 0,
        attempts_allowed: exam.attempts_allowed ?? 1,
        instructions: exam.instructions ?? '',
        start_datetime: toDatetimeLocal(exam.start_datetime),
        end_datetime: toDatetimeLocal(exam.end_datetime),
        exam_code: exam.exam_code ?? '',
        shuffle_questions: !!exam.shuffle_questions,
        submission_password_enabled: !!exam.submission_password_enabled,
        submission_password: '',
        show_student_ai_feedback:
            exam.show_student_ai_feedback !== false,
        show_student_expected_answers:
            exam.show_student_expected_answers !== false,
        show_student_revision_suggestions:
            exam.show_student_revision_suggestions !== false,
        assessment_file: null,
    });

    const submit = () => {
        form.put(route('lecturer.exams.update', exam.id), {
            forceFormData: !!form.data.assessment_file,
        });
    };

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Edit exam
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        {exam.fairness_report ? (
                            <Link
                                href={route('lecturer.exams.fairness.show', exam.id)}
                                className="inline-flex items-center rounded-md border border-ai-accent/40 bg-ai-accent/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-ai-accent hover:bg-ai-accent/20"
                            >
                                View fairness report
                            </Link>
                        ) : null}
                        {qCount > 0 ? (
                            <PrimaryButton
                                type="button"
                                className="text-xs uppercase tracking-widest"
                                onClick={() =>
                                    router.post(
                                        route('lecturer.exams.fairness.analyze', exam.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Analyze fairness
                            </PrimaryButton>
                        ) : (
                            <span
                                className="inline-flex cursor-not-allowed items-center rounded-md border border-theme-line bg-theme-highlight px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-theme-dim opacity-70"
                                title="Add questions before running fairness analysis."
                            >
                                Analyze fairness
                            </span>
                        )}
                        <Link
                            href={route('lecturer.exams.show', exam.id)}
                            className="text-sm text-theme-dim underline hover:text-theme-strong"
                        >
                            Back to exam
                        </Link>
                        <Link
                            href={route(
                                'lecturer.exams.proctoring.penalties.index',
                                exam.id,
                            )}
                            className="text-sm font-semibold text-amber-700 underline decoration-amber-500/40 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-200"
                        >
                            Proctoring penalties
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Edit exam" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg border border-sky-800/50 bg-sky-950/25 p-4 text-sm text-theme-muted">
                        <span className="font-medium text-theme-muted">
                            Student exam link:{' '}
                        </span>
                        <a
                            href={absoluteEntryUrl(
                                studentEntryUrl ?? route('student.entry'),
                            )}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="break-all font-medium text-sky-400 underline decoration-sky-500/40 hover:text-sky-300"
                        >
                            {absoluteEntryUrl(
                                studentEntryUrl ?? route('student.entry'),
                            )}
                        </a>
                        <span className="ms-2 text-theme-dim">
                            Code:{' '}
                            <span className="font-mono text-theme-strong">
                                {exam.exam_code ?? '—'}
                            </span>
                        </span>
                    </div>
                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <ExamForm
                            form={form}
                            submitLabel="Save changes"
                            onSubmit={submit}
                            linkableCourses={linkableCourses}
                            examId={exam.id}
                        />
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
