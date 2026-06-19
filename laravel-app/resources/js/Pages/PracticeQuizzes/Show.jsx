import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Show({ quiz, filterQuery }) {
    const { auth } = usePage().props;
    const user = auth.user;
    
    const form = useForm({});

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
                    <dl className="mt-6 grid grid-cols-1 sm:grid-cols- gap-3 text-sm sm:grid-cols-3">
                        <div className="rounded-lg bg-white/5 p-3">
                            <dt className="text-theme-dim">Questions</dt>
                            <dd className="font-semibold text-theme-strong">{qCount}</dd>
                        </div>
                        <div className="rounded-lg bg-white/5 p-3">
                            <dt className="text-theme-dim">Difficulty</dt>
                            <dd className="font-semibold text-theme-strong">{quiz.difficulty_level || '-'}</dd>
                        </div>
                        <div className="rounded-lg bg-white/5 p-3">
                            <dt className="text-theme-dim">Est. time</dt>
                            <dd className="font-semibold text-theme-strong">
                                {quiz.practice_duration_minutes != null
                                    ? `${quiz.practice_duration_minutes} min`
                                    : quiz.duration != null
                                      ? `${quiz.duration} min`
                                      : '-'}
                            </dd>
                        </div>
                    </dl>
                </div>
                <div className="rounded-2xl border border-sky-500/30 bg-sky-950/20 p-6">
                    <h2 className="text-lg font-semibold text-theme-strong">Start practice</h2>
                    
                    <div className="mt-4 p-4 rounded-xl bg-theme-layer-1 border border-theme-line">
                        <div className="flex justify-between items-center mb-2">
                            <span className="text-sm text-theme-dim">Your Free Questions:</span>
                            <span className="font-semibold text-emerald-400">{user?.free_questions_remaining || 0}</span>
                        </div>
                        <div className="flex justify-between items-center mb-4">
                            <span className="text-sm text-theme-dim">Your Tokens:</span>
                            <span className="font-semibold text-amber-400">{user?.tokens || 0}</span>
                        </div>
                        <div className="border-t border-theme-line pt-4 flex justify-between items-center">
                            <span className="text-sm text-theme-dim">Cost (Tokens):</span>
                            <span className="font-bold text-red-400">-{qCount}</span>
                        </div>
                    </div>

                    {!quiz.allow_retry ? (
                        <p className="mt-4 text-xs text-amber-200/90 text-center">
                            This quiz allows only one attempt.
                        </p>
                    ) : null}
                    
                    {form.errors.quiz ? (
                        <p className="mt-4 text-sm text-red-400 text-center font-medium">{form.errors.quiz}</p>
                    ) : null}

                    <form
                        className="mt-6 space-y-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            start();
                        }}
                    >
                        <button
                            type="submit"
                            disabled={form.processing || qCount === 0}
                            className="w-full rounded-xl bg-sky-600 py-3 text-sm font-semibold text-white disabled:opacity-40 transition-colors hover:bg-sky-500"
                        >
                            {qCount === 0 ? 'No questions yet' : 'Begin practice session'}
                        </button>
                        
                        <Link 
                            href="/student/wallet" 
                            className="block text-center mt-3 text-sm text-sky-400 hover:text-sky-300"
                        >
                            Top Up Tokens
                        </Link>
                    </form>
                </div>
            </div>
        </PracticeHubLayout>
    );
}
