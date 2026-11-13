import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link } from '@inertiajs/react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export default function History({ customSessions, quizAttempts, analytics = {} }) {
    const formatDate = (dateString) => {
        if (!dateString) return 'Unknown';
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(dateString));
    };

    return (
        <PracticeHubLayout>
            <Head title="My Learning History" />
            
            <div className="mb-10 text-center sm:text-left">
                <h1 className="text-3xl font-bold tracking-tight text-theme-strong sm:text-4xl">
                    My Learning History
                </h1>
                <p className="mx-auto mt-3 max-w-2xl text-theme-dim sm:mx-0">
                    Review your past practice sessions and quiz attempts. Track your progress over time and see where you can improve.
                </p>
            </div>

            {/* Analytics Dashboard */}
            <div className="mb-12">
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-6">
                    {/* Stat Card: Total Questions Solved */}
                    <div className="rounded-xl border border-theme-line bg-theme-elevated p-6 shadow-sm">
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-indigo-500/10 p-3 text-indigo-600 dark:text-indigo-400">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-theme-dim">Total Questions Solved</p>
                                <p className="text-2xl font-bold text-theme-strong">{analytics.totalQuestionsSolved || 0}</p>
                            </div>
                        </div>
                    </div>

                    {/* Stat Card: Average Score */}
                    <div className="rounded-xl border border-theme-line bg-theme-elevated p-6 shadow-sm">
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-emerald-500/10 p-3 text-emerald-500">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-theme-dim">Average Score</p>
                                <p className="text-2xl font-bold text-theme-strong">{analytics.averageScore || 0}%</p>
                            </div>
                        </div>
                    </div>

                    {/* Stat Card: Weak Topics */}
                    <div className="rounded-xl border border-theme-line bg-theme-elevated p-6 shadow-sm md:col-span-2 lg:col-span-1">
                        <div className="flex items-center gap-4 mb-3">
                            <div className="rounded-lg bg-rose-500/10 p-3 text-rose-500">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-theme-dim">Areas for Improvement</p>
                            </div>
                        </div>
                        {analytics.weakTopics && analytics.weakTopics.length > 0 ? (
                            <div className="flex flex-wrap gap-2">
                                {analytics.weakTopics.map((topic, i) => (
                                    <span key={i} className="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20">
                                        {topic}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-theme-dim">No specific weak areas detected yet. Keep practicing!</p>
                        )}
                    </div>
                </div>

                {/* Progress Chart */}
                {analytics.progressData && analytics.progressData.length > 1 && (
                    <div className="rounded-xl border border-theme-line bg-theme-elevated p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-theme-strong mb-6">Improvement Progress</h3>
                        <div className="h-72 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={analytics.progressData} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
                                    <Line type="monotone" dataKey="score" stroke="#0ea5e9" strokeWidth={3} dot={{ r: 4, fill: "#0ea5e9" }} activeDot={{ r: 6 }} />
                                    <CartesianGrid stroke="#ccc" strokeDasharray="5 5" opacity={0.2} vertical={false} />
                                    <XAxis dataKey="date" stroke="#888" fontSize={12} tickLine={false} axisLine={false} />
                                    <YAxis stroke="#888" fontSize={12} tickLine={false} axisLine={false} domain={[0, 100]} tickFormatter={(value) => `${value}%`} />
                                    <Tooltip 
                                        contentStyle={{ backgroundColor: '#1e293b', border: 'none', borderRadius: '8px', color: '#fff' }}
                                        itemStyle={{ color: '#0ea5e9' }}
                                        labelStyle={{ color: '#94a3b8', marginBottom: '4px' }}
                                        formatter={(value, name, props) => [`${value}%`, props.payload.topic]}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                )}
            </div>

            <div className="grid gap-10 lg:grid-cols-1">

                {/* Custom Practice Sessions Section */}
                <section>
                    <h2 className="mb-6 text-xl font-semibold text-theme-strong flex items-center gap-2">
                        <svg className="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                        Generated Practice
                    </h2>
                    
                    {customSessions.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-theme-line p-4 sm:p-8 text-center bg-white/[0.02]">
                            <p className="text-theme-dim">You haven't generated any custom practice sessions yet.</p>
                        </div>
                    ) : (
                        <div className="grid gap-4">
                            {customSessions.map((session) => (
                                <div key={session.id} className="rounded-xl border border-theme-line bg-theme-elevated p-5 shadow-sm transition hover:border-theme-line-hover">
                                    <div className="flex justify-between items-start gap-4">
                                        <div>
                                            <h3 className="font-semibold text-theme-strong truncate max-w-[250px] sm:max-w-md">
                                                {session.topic || 'Custom Practice'}
                                            </h3>
                                            <p className="text-sm text-theme-dim mt-1">
                                                {session.questions?.length || 0}-Question Practice
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                                {session.completed_at ? `${Math.round((session.score / 100) * (session.questions?.length || 0))}/${session.questions?.length || 0}` : 'In Progress'}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="mt-4 flex items-center justify-between border-t border-theme-line pt-4">
                                        <div className="text-xs text-theme-muted">
                                            {session.completed_at ? `Completed: ${formatDate(session.completed_at)}` : 'Incomplete'}
                                        </div>
                                        {session.completed_at ? (
                                            <Link 
                                                href={route('student.practice.result', { session: session.id })}
                                                className="text-sm font-medium text-theme-strong underline decoration-theme-line hover:decoration-indigo-500 transition-colors"
                                            >
                                                Review Feedback
                                            </Link>
                                        ) : (
                                            <Link 
                                                href={route('student.practice.take', { session: session.id })}
                                                className="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition-colors"
                                            >
                                                Resume Practice
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </PracticeHubLayout>
    );
}
