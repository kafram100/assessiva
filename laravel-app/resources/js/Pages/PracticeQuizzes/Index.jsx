import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import axios from 'axios';
import QuizPlayer from '@/Components/QuizPlayer';
import QuizResult from '@/Components/QuizResult';

function QuizCard({ quiz, extraQuery }) {
    const q = extraQuery || {};
    const href = route('practice-quizzes.show', {
        quiz: quiz.id,
        ...(q.school_id ? { school_id: q.school_id } : {}),
        ...(q.department_id ? { department_id: q.department_id } : {}),
        ...(q.program ? { program: q.program } : {}),
        ...(q.level ? { level: q.level } : {}),
    });
    const attempts = quiz.attempts_count ?? 0;
    const diff = quiz.difficulty_level || '-';
    const duration =
        quiz.practice_duration_minutes != null
            ? `${quiz.practice_duration_minutes} min`
            : quiz.duration != null
              ? `${quiz.duration} min`
              : '-';

    return (
        <Link
            href={href}
            className="group flex flex-col rounded-2xl border border-theme-line bg-white/[0.04] p-5 shadow-lg shadow-theme-card ring-1 ring-theme-line transition hover:border-indigo-500/40 hover:bg-white/[0.07]"
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 className="text-lg font-semibold text-theme-strong group-hover:text-indigo-600 dark:group-hover:text-indigo-300">
                        {quiz.title}
                    </h3>
                    <p className="mt-1 text-sm text-theme-dim">
                        {quiz.course_name}
                        {quiz.course_code ? ` · ${quiz.course_code}` : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-1">
                    {quiz.ai_recommended ? (
                        <span className="rounded-full bg-indigo-500/15 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                            AI pick
                        </span>
                    ) : null}
                    {quiz.is_featured_practice ? (
                        <span className="rounded-full bg-amber-500/20 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-amber-100">
                            Featured
                        </span>
                    ) : null}
                </div>
            </div>
            {quiz.practice_topic ? (
                <p className="mt-3 text-sm text-theme-muted">Topic: {quiz.practice_topic}</p>
            ) : null}
            <div className="mt-4 flex flex-wrap gap-3 text-xs text-theme-dim">
                <span>Difficulty: {diff}</span>
                <span>Est. time: {duration}</span>
                <span>{attempts} attempts</span>
            </div>
        </Link>
    );
}

export default function Index({
    quizzes,
    featured,
    aiRecommended,
    trending,
    filters,
    sort,
    schools,
    departments,
    programs,
    levels,
    topics,
    difficulties,
    availableCourses = [],
}) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [local, setLocal] = useState({
        school_id: filters?.school_id || '',
        department_id: filters?.department_id || '',
        program: filters?.program || '',
        level: filters?.level || '',
        course: filters?.course || '',
        topic: filters?.topic || '',
        difficulty: filters?.difficulty || '',
        search: filters?.search || '',
        sort: sort || 'featured',
    });

    const freeAvailable = user?.free_questions_remaining || 0;
    const tokensAvailable = user?.tokens || 0;
    const totalAvailable = freeAvailable + tokensAvailable;

    const [practiceForm, setPracticeForm] = useState({
        topic: '',
        question_type: 'all',
        count: totalAvailable > 0 ? Math.min(20, totalAvailable) : 20,
    });

    const [isGenerating, setIsGenerating] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [practiceSession, setPracticeSession] = useState(null);
    const [questions, setQuestions] = useState(null);
    const [quizResult, setQuizResult] = useState(null);
    const [errorMessage, setErrorMessage] = useState(null);

    const extraQuery = useMemo(
        () => ({
            school_id: local.school_id || undefined,
            department_id: local.department_id || undefined,
            program: local.program || undefined,
            level: local.level || undefined,
        }),
        [local.school_id, local.department_id, local.program, local.level],
    );

    const applyFilters = () => {
        router.get(route('practice-quizzes.index'), { ...local }, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        const cleared = {
            school_id: '',
            department_id: '',
            program: '',
            level: '',
            course: '',
            topic: '',
            difficulty: '',
            search: '',
            sort: local.sort || 'featured',
        };
        setLocal(cleared);
        router.get(
            route('practice-quizzes.index'),
            { sort: cleared.sort },
            { preserveState: true, replace: true },
        );
    };

    const startRandomPractice = async () => {
        if (!practiceForm.topic || !practiceForm.topic.trim()) {
            setErrorMessage("Please enter a topic before generating practice questions.");
            return;
        }
        setIsGenerating(true);
        setErrorMessage(null);
        setQuizResult(null);
        setPracticeSession(null);
        setQuestions(null);
        try {
            const response = await axios.post(route('student.practice.generate'), practiceForm, {
                headers: { Accept: 'application/json' }
            });
            if (response.data.practiceSession && response.data.questions) {
                setPracticeSession(response.data.practiceSession);
                setQuestions(response.data.questions);
                // Reload the auth prop to instantly update the token balance display
                router.reload({ only: ['auth'] });
            }
        } catch (error) {
            console.error("Error generating practice:", error);
            if (error.response?.data?.error) {
                setErrorMessage(error.response.data.error);
            } else {
                setErrorMessage("Failed to generate practice. Please check your token balance or try another topic.");
            }
        } finally {
            setIsGenerating(false);
        }
    };

    const submitPractice = async (responses) => {
        if (!practiceSession) return;
        setIsSubmitting(true);
        try {
            const response = await axios.post(route('student.practice.submit', practiceSession.id), { responses }, {
                headers: { Accept: 'application/json' }
            });
            if (response.data.practiceSession && response.data.questions) {
                setQuizResult(response.data);
            }
        } catch (error) {
            console.error("Error submitting practice:", error);
            alert("Failed to submit practice. Please try again.");
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <PracticeHubLayout>
            <Head title="Practice quizzes" />
            <div className="mb-8 sm:mb-10 text-center sm:text-left">
                <h1 className="text-2xl font-bold tracking-tight text-theme-strong sm:text-3xl lg:text-4xl">
                    Level up before exam day
                </h1>
                <p className="mx-auto mt-2 sm:mt-3 max-w-2xl text-sm sm:text-base text-theme-dim sm:mx-0">
                    Master your courses with expert-curated quizzes. Find your topic,
                    test your knowledge, and get instant feedback.
                </p>
                {user && (
                    <div className="mt-6 sm:mt-8 rounded-2xl border border-indigo-500/30 bg-indigo-500/5 p-3 sm:p-5 max-w-4xl mx-auto sm:mx-0 text-left">
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <h3 className="text-sm font-semibold text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                Generate Custom Practice
                            </h3>
                            <div className="flex items-center gap-2 text-xs">
                                {freeAvailable > 0 && (
                                    <span className="rounded-full bg-emerald-500/10 px-2.5 py-0.5 font-medium text-emerald-500">
                                        {freeAvailable} free question{freeAvailable > 1 ? 's' : ''} left
                                    </span>
                                )}
                                {tokensAvailable > 0 && (
                                    <span className="rounded-full bg-amber-500/10 px-2.5 py-0.5 font-medium text-amber-500">
                                        {tokensAvailable} token{tokensAvailable > 1 ? 's' : ''}
                                    </span>
                                )}
                                {totalAvailable === 0 && (
                                    <Link
                                        href={route('student.wallet')}
                                        className="font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                                    >
                                        Buy Tokens
                                    </Link>
                                )}
                            </div>
                        </div>

                        {errorMessage && (
                            <div className="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-600 dark:text-rose-400">
                                <span>{errorMessage}</span>
                                {totalAvailable === 0 && (
                                    <Link
                                        href={route('student.wallet')}
                                        className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700 shrink-0"
                                    >
                                        Top Up Tokens
                                    </Link>
                                )}
                            </div>
                        )}

                        <div className="flex flex-col sm:flex-row items-end gap-2 sm:gap-3">
                            <div className="w-full sm:w-2/5">
                                <label className="block text-xs font-medium text-theme-dim mb-1">Course</label>
                                <input
                                    type="text"
                                    list="practice-available-courses"
                                    placeholder="e.g. Introduction to Digital Literacy"
                                    className="w-full rounded-xl border border-theme-line bg-theme-elevated px-3 py-2 text-sm sm:py-2.5 text-theme-strong focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder:text-theme-dim"
                                    value={practiceForm.topic}
                                    onChange={e => setPracticeForm(s => ({ ...s, topic: e.target.value }))}
                                />
                                <datalist id="practice-available-courses">
                                    {availableCourses.map((c) => (
                                        <option key={c} value={c} />
                                    ))}
                                </datalist>
                            </div>
                            <div className="w-full sm:w-1/4">
                                <label className="block text-xs font-medium text-theme-dim mb-1">Questions Type</label>
                                <select
                                    className="w-full rounded-xl border border-theme-line bg-theme-elevated px-3 py-2 text-sm sm:py-2.5 text-theme-strong focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    value={practiceForm.question_type}
                                    onChange={e => setPracticeForm(s => ({ ...s, question_type: e.target.value }))}
                                >
                                    <option value="all">All Types</option>
                                    <option value="mcqs">MCQs only</option>
                                    <option value="theory">Theory only</option>
                                    <option value="fill_in">Fill-in only</option>
                                    <option value="mcqs_theory">MCQs & Theory</option>
                                    <option value="mcqs_fill_in">MCQs & Fill-in</option>
                                    <option value="theory_fill_in">Theory & Fill-in</option>
                                </select>
                            </div>
                            <div className="w-full sm:w-1/4">
                                <label className="block text-xs font-medium text-theme-dim mb-1">Questions</label>
                                <select
                                    className="w-full rounded-xl border border-theme-line bg-theme-elevated px-3 py-2 text-sm sm:py-2.5 text-theme-strong focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    value={practiceForm.count}
                                    onChange={e => setPracticeForm(s => ({ ...s, count: parseInt(e.target.value) }))}
                                >
                                    {totalAvailable > 0 && ![1, 3, 5, 10, 15, 20, 25, 30].includes(totalAvailable) && (
                                        <option value={totalAvailable}>All remaining ({totalAvailable})</option>
                                    )}
                                    <option value="1">1</option>
                                    <option value="3">3</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="30">30</option>
                                </select>
                            </div>
                            <button
                                onClick={startRandomPractice}
                                disabled={isGenerating}
                                className="w-full sm:w-auto rounded-xl bg-indigo-600 px-6 py-2 sm:py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 whitespace-nowrap flex items-center justify-center min-w-[140px]"
                            >
                                {isGenerating ? (
                                    <div className="flex items-center gap-2">
                                        <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Generating...</span>
                                    </div>
                                ) : 'Quick Practice'}
                            </button>
                        </div>

                        {/* Inline Quiz Player / Result */}
                        {questions && !quizResult && (
                            <div className="mt-8 border-t border-theme-line pt-8">
                                <QuizPlayer 
                                    practiceSession={practiceSession} 
                                    questions={questions} 
                                    onSubmit={submitPractice} 
                                    processing={isSubmitting} 
                                />
                            </div>
                        )}

                        {quizResult && (
                            <div className="mt-8 border-t border-theme-line pt-8">
                                <QuizResult 
                                    practiceSession={quizResult.practiceSession} 
                                    questions={quizResult.questions} 
                                    onReset={() => {
                                        setQuizResult(null);
                                        setPracticeSession(null);
                                        setQuestions(null);
                                    }}
                                />
                            </div>
                        )}
                    </div>
                )}
            </div>


            {featured.length > 0 ? (
                <section className="mb-12">
                    <h2 className="mb-4 text-lg font-semibold text-theme-strong">Featured</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {featured.map((q) => (
                            <QuizCard key={q.id} quiz={q} extraQuery={extraQuery} />
                        ))}
                    </div>
                </section>
            ) : null}


        </PracticeHubLayout>
    );
}
