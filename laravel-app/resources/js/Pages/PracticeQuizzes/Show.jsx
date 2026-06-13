import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ quiz, filterQuery }) {
    const form = useForm({
        student_name: '',
        student_email: '',
        student_index: '',
        department: '',
    });

    const fq = filterQuery || {};
    const backHref = route('practice-quizzes.index', {
        ...(fq.school_id ? { school_id: fq.school_id } : {}),
        ...(fq.department_id ? { department_id: fq.department_id } : {}),
        ...(fq.program ? { program: fq.program } : {}),
        ...(fq.level ? { level: fq.level } : {}),
    });

    const start = () => {
        form.post(
            route('practice-quizzes.start', {
                quiz: quiz.id,
                ...(fq.school_id ? { school_id: fq.school_id } : {}),
                ...(fq.department_id ? { department_id: fq.department_id } : {}),
                ...(fq.program ? { program: fq.program } : {}),
                ...(fq.level ? { level: fq.level } : {}),
            }),
        );
    };

    const qCount = quiz.questions_count ?? 0;

    return (
        <PracticeHubLayout>
            <Head title={quiz.title} />
            <Link href={backHref} className="text-sm text-sky-400 hover:text-sky-300">
                ← Back to hub
            </Link>
            <div className="mt-6 grid gap-10 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <h1 className="text-3xl font-bold text-theme-strong">{quiz.title}</h1>
                    <p className="mt-2 text-theme-dim">
                        {quiz.course_name}
                        {quiz.course_code ? ` · ${quiz.course_code}` : ''}
                    </p>
                    {quiz.practice_topic ? (
                        <p className="mt-2 text-sm text-theme-muted">Topic: {quiz.practice_topic}</p>
                    ) : null}
                    {quiz.instructions ? (
                        <div className="mt-6 rounded-xl border border-theme-line bg-theme-highlight p-4 text-sm leading-relaxed text-theme-muted">
                            {quiz.instructions}
                        </div>
                    ) : null}
                    <dl className="mt-6 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        <div className="rounded-lg bg-white/5 p-3">
                            <dt className="text-theme-dim">Questions</dt>
                            <dd className="font-semibold text-theme-strong">{qCount}</dd>
                        </div>
                        <div className="rounded-lg bg-white/5 p-3">
                            <dt className="text-theme-dim">Difficulty</dt>
                            <dd className="font-semibold text-theme-strong">{quiz.difficulty_level || '—'}</dd>
                        </div>
                        <div className="rounded-lg bg-white/5 p-3">
                            <dt className="text-theme-dim">Est. time</dt>
                            <dd className="font-semibold text-theme-strong">
                                {quiz.practice_duration_minutes != null
                                    ? `${quiz.practice_duration_minutes} min`
                                    : quiz.duration != null
                                      ? `${quiz.duration} min`
                                      : '—'}
                            </dd>
                        </div>
                    </dl>
                </div>
                <div className="rounded-2xl border border-sky-500/30 bg-sky-950/20 p-6">
                    <h2 className="text-lg font-semibold text-theme-strong">Start practice</h2>
                    <p className="mt-2 text-xs text-theme-dim">
                        Optional details help lecturers with analytics. No proctoring, no exam codes.
                    </p>
                    {!quiz.allow_retry ? (
                        <p className="mt-2 text-xs text-amber-200/90">
                            This quiz allows only one attempt per email address.
                        </p>
                    ) : null}
                    <form
                        className="mt-4 space-y-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            start();
                        }}
                    >
                        <div>
                            <label className="text-xs text-theme-dim">Name (optional)</label>
                            <input
                                className="mt-1 w-full rounded-lg border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.student_name}
                                onChange={(e) => form.setData('student_name', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="text-xs text-theme-dim">Email (optional)</label>
                            <input
                                type="email"
                                className="mt-1 w-full rounded-lg border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.student_email}
                                onChange={(e) => form.setData('student_email', e.target.value)}
                            />
                            {form.errors.student_email ? (
                                <p className="mt-1 text-xs text-red-400">{form.errors.student_email}</p>
                            ) : null}
                        </div>
                        <div>
                            <label className="text-xs text-theme-dim">Index # (optional)</label>
                            <input
                                className="mt-1 w-full rounded-lg border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.student_index}
                                onChange={(e) => form.setData('student_index', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="text-xs text-theme-dim">Department (optional)</label>
                            <input
                                className="mt-1 w-full rounded-lg border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.department}
                                onChange={(e) => form.setData('department', e.target.value)}
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing || qCount === 0}
                            className="w-full rounded-xl bg-sky-600 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                        >
                            {qCount === 0 ? 'No questions yet' : 'Begin practice session'}
                        </button>
                    </form>
                </div>
            </div>
        </PracticeHubLayout>
    );
}
