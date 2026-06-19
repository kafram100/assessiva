import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Dashboard({ walletBalance, recentSessions, recentAttempts, freeQuestionsUsed }) {
    const { data, setData, post, processing } = useForm({
        topic: '',
        difficulty: 'medium',
        count: '5',
    });

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route('student.practice.generate'));
    };

    return (
        <PracticeHubLayout>
            <Head title="Student Dashboard" />

            <div className="mb-10 text-center sm:text-left">
                <h1 className="text-3xl font-bold tracking-tight text-theme-strong sm:text-4xl">
                    Student Dashboard
                </h1>
                <p className="mx-auto mt-3 max-w-2xl text-theme-dim sm:mx-0">
                    Your central hub for taking quizzes, generating practice material, and tracking your progress.
                </p>
            </div>

            <div className="grid gap-8 lg:grid-cols-3">
                {/* Main Action Area (Takes 2 columns) */}
                <div className="lg:col-span-2 space-y-8">
                    {/* Generator Card */}
                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 shadow-sm sm:p-8">
                        <div className="flex items-center gap-4 mb-6">
                            <div className="rounded-full bg-violet-500/10 p-3 text-violet-500">
                                <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <div>
                                <h2 className="text-2xl font-bold text-theme-strong">Quick Practice Generator</h2>
                                <p className="text-theme-dim mt-1">Generate a custom practice session powered by AI.</p>
                            </div>
                        </div>

                        <form onSubmit={handleGenerate} className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-theme-strong mb-1">Topic / Keyword</label>
                                <input 
                                    type="text" 
                                    placeholder="e.g. Calculus"
                                    className="w-full rounded-xl border border-theme-line bg-theme-highlight px-3 py-2 text-sm text-theme-strong focus:border-violet-500 focus:ring-1 focus:ring-violet-500 placeholder:text-theme-dim/30"
                                    value={data.topic}
                                    onChange={e => setData('topic', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-theme-strong mb-1">Difficulty</label>
                                <select 
                                    className="w-full rounded-xl border border-theme-line bg-theme-highlight px-3 py-2 text-sm text-theme-strong focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                    value={data.difficulty}
                                    onChange={e => setData('difficulty', e.target.value)}
                                >
                                    <option value="">Any Difficulty</option>
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div className="sm:col-span-3">
                                <label className="block text-sm font-medium text-theme-strong mb-1">Number of Questions (5 - 30)</label>
                                <input 
                                    type="number" 
                                    min="5" 
                                    max="30"
                                    className="w-full rounded-xl border border-theme-line bg-theme-highlight px-3 py-2 text-sm text-theme-strong focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                    value={data.count}
                                    onChange={e => setData('count', e.target.value)}
                                />
                            </div>
                            <div className="sm:col-span-3 pt-2 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white transition hover:bg-violet-500 disabled:opacity-50"
                                >
                                    {processing ? 'Generating...' : 'Generate Questions'}
                                </button>
                                <p className="text-center text-xs text-theme-muted mt-2">
                                    Generates instantly. Uses free questions or tokens.
                                </p>
                            </div>
                        </form>
                    </div>

                    {/* Practice Quizzes Callout */}
                    {freeQuestionsUsed < 20 && (
                        <div className="rounded-2xl border border-theme-line bg-sky-500/5 p-6 sm:p-8 flex flex-col gap-6 transition hover:bg-sky-500/10">
                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-sky-500/10 p-3 text-sky-500 hidden sm:block">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253" />
                                    </svg>
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-theme-strong">Browse Practice Quizzes</h3>
                                    <p className="text-theme-dim mt-1 text-sm">Explore our catalog of practice questions organized by topic and difficulty to help you prepare for your exams.</p>
                                    
                                    <div className="mt-5 pt-4">
                                        <Link 
                                            href={route('practice-quizzes.index')}
                                            className="inline-block rounded-xl bg-sky-500 px-6 py-3 font-semibold text-white shadow-md transition hover:bg-sky-400"
                                        >
                                            Go to Practice Catalog &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Sidebar Column */}
                <div className="space-y-8">
                    {/* Recent History */}
                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 shadow-sm">
                        <div className="flex items-center justify-between mb-6">
                            <h3 className="font-semibold text-theme-strong">Recent Activity</h3>
                            <Link href={route('student.history')} className="text-sm text-theme-muted hover:text-theme-strong">View All</Link>
                        </div>
                        
                        <div className="space-y-4">
                            {recentSessions.length === 0 && recentAttempts.length === 0 ? (
                                <p className="text-sm text-theme-dim text-center py-4">No recent activity found.</p>
                            ) : null}

                            {recentSessions.map(session => (
                                <Link 
                                    key={`gen-${session.id}`} 
                                    href={route('student.practice.result', session.id)}
                                    className="block border border-theme-line rounded-lg p-3 hover:border-violet-500/50 transition bg-theme-highlight"
                                >
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-medium text-sm text-theme-strong">Generated Practice</p>
                                            <p className="text-xs text-theme-dim mt-0.5">Custom 20-Questions</p>
                                        </div>
                                        <span className="text-violet-500 font-bold text-sm">{session.score}%</span>
                                    </div>
                                </Link>
                            ))}

                            {recentAttempts.map(attempt => (
                                <Link 
                                    key={`quiz-${attempt.id}`} 
                                    href={route('practice-quizzes.results', { quiz: attempt.quiz_id, attempt: attempt.id })}
                                    className="block border border-theme-line rounded-lg p-3 hover:border-sky-500/50 transition bg-theme-highlight"
                                >
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-medium text-sm text-theme-strong line-clamp-1">{attempt.quiz?.title || 'Unknown Quiz'}</p>
                                            <p className="text-xs text-theme-dim mt-0.5">Official Quiz</p>
                                        </div>
                                        <span className="text-sky-500 font-bold text-sm shrink-0 ml-2">{attempt.score}/{attempt.total_marks}</span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </PracticeHubLayout>
    );
}
