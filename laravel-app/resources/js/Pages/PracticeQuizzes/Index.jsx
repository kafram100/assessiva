import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

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
    const diff = quiz.difficulty_level || '—';
    const duration =
        quiz.practice_duration_minutes != null
            ? `${quiz.practice_duration_minutes} min`
            : quiz.duration != null
              ? `${quiz.duration} min`
              : '—';

    return (
        <Link
            href={href}
            className="group flex flex-col rounded-2xl border border-theme-line bg-white/[0.04] p-5 shadow-lg shadow-theme-card ring-1 ring-theme-line transition hover:border-sky-500/40 hover:bg-white/[0.07]"
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 className="text-lg font-semibold text-theme-strong group-hover:text-sky-600 dark:group-hover:text-sky-200">
                        {quiz.title}
                    </h3>
                    <p className="mt-1 text-sm text-theme-dim">
                        {quiz.course_name}
                        {quiz.course_code ? ` · ${quiz.course_code}` : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-1">
                    {quiz.ai_recommended ? (
                        <span className="rounded-full bg-violet-500/20 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-violet-800 dark:text-violet-200">
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
                {quiz.owner?.name ? <span>Lecturer: {quiz.owner.name}</span> : null}
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
    lecturers,
}) {
    const [local, setLocal] = useState({
        school_id: filters.school_id ?? '',
        department_id: filters.department_id ?? '',
        program: filters.program ?? '',
        level: filters.level ?? '',
        course: filters.course ?? '',
        topic: filters.topic ?? '',
        lecturer_id: filters.lecturer_id ?? '',
        difficulty: filters.difficulty ?? '',
        search: filters.search ?? '',
        sort: sort ?? 'featured',
    });

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
            lecturer_id: '',
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

    return (
        <PracticeHubLayout>
            <Head title="Practice quizzes" />
            <div className="mb-10 text-center sm:text-left">
                <h1 className="text-3xl font-bold tracking-tight text-theme-strong sm:text-4xl">
                    Level up before exam day
                </h1>
                <p className="mx-auto mt-3 max-w-2xl text-theme-dim sm:mx-0">
                    Explore lecturer-crafted practice quizzes. Filter by school, program, or topic — then
                    practice at your own pace with instant feedback.
                </p>
            </div>

            <div className="mb-10 rounded-2xl border border-theme-line bg-theme-highlight p-4 sm:p-6">
                <div className="grid gap-4 lg:grid-cols-4">
                    <div className="lg:col-span-2">
                        <label className="text-xs font-semibold uppercase tracking-wider text-theme-dim">
                            Search
                        </label>
                        <input
                            className="mt-1 w-full rounded-xl border border-theme-line bg-theme-elevated px-3 py-2 text-sm text-theme-strong placeholder:text-slate-600 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                            placeholder="Title, course, topic, exam code…"
                            value={local.search}
                            onChange={(e) => setLocal((s) => ({ ...s, search: e.target.value }))}
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase tracking-wider text-theme-dim">
                            Sort
                        </label>
                        <select
                            className="mt-1 w-full rounded-xl border border-theme-line bg-theme-elevated px-3 py-2 text-sm text-theme-strong focus:border-sky-500 focus:outline-none"
                            value={local.sort}
                            onChange={(e) => setLocal((s) => ({ ...s, sort: e.target.value }))}
                        >
                            <option value="featured">Featured first</option>
                            <option value="newest">Newest</option>
                            <option value="most_attempted">Most attempted</option>
                            <option value="ai_recommended">AI recommended</option>
                        </select>
                    </div>
                    <div className="flex items-end gap-2">
                        <button
                            type="button"
                            onClick={applyFilters}
                            className="flex-1 rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-900/30 transition hover:bg-sky-500"
                        >
                            Apply filters
                        </button>
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="rounded-xl border border-theme-line px-3 py-2 text-sm font-medium text-theme-muted transition hover:border-theme-line hover:bg-white/[0.04]"
                        >
                            Clear all
                        </button>
                    </div>
                </div>
                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.school_id}
                        onChange={(e) => setLocal((s) => ({ ...s, school_id: e.target.value }))}
                    >
                        <option value="">All schools</option>
                        {schools.map((x) => (
                            <option key={x.id} value={x.id}>
                                {x.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.department_id}
                        onChange={(e) => setLocal((s) => ({ ...s, department_id: e.target.value }))}
                    >
                        <option value="">All departments</option>
                        {departments.map((x) => (
                            <option key={x.id} value={x.id}>
                                {x.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.program}
                        onChange={(e) => setLocal((s) => ({ ...s, program: e.target.value }))}
                    >
                        <option value="">All programs</option>
                        {programs
                            .filter(Boolean)
                            .map((p) => (
                                <option key={p} value={p}>
                                    {p}
                                </option>
                            ))}
                    </select>
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.level}
                        onChange={(e) => setLocal((s) => ({ ...s, level: e.target.value }))}
                    >
                        <option value="">All levels</option>
                        {levels
                            .filter(Boolean)
                            .map((p) => (
                                <option key={p} value={p}>
                                    {p}
                                </option>
                            ))}
                    </select>
                    <input
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        placeholder="Course contains…"
                        value={local.course}
                        onChange={(e) => setLocal((s) => ({ ...s, course: e.target.value }))}
                    />
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.topic}
                        onChange={(e) => setLocal((s) => ({ ...s, topic: e.target.value }))}
                    >
                        <option value="">All topics</option>
                        {topics
                            .filter(Boolean)
                            .map((p) => (
                                <option key={p} value={p}>
                                    {p}
                                </option>
                            ))}
                    </select>
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.lecturer_id}
                        onChange={(e) => setLocal((s) => ({ ...s, lecturer_id: e.target.value }))}
                    >
                        <option value="">All lecturers</option>
                        {lecturers.map((x) => (
                            <option key={x.id} value={x.id}>
                                {x.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className="rounded-lg border border-theme-line bg-theme-elevated px-2 py-2 text-sm"
                        value={local.difficulty}
                        onChange={(e) => setLocal((s) => ({ ...s, difficulty: e.target.value }))}
                    >
                        <option value="">Any difficulty</option>
                        {difficulties
                            .filter(Boolean)
                            .map((p) => (
                                <option key={p} value={p}>
                                    {p}
                                </option>
                            ))}
                    </select>
                </div>
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

            <div className="mb-12 grid gap-10 lg:grid-cols-2">
                <section>
                    <h2 className="mb-4 text-lg font-semibold text-theme-strong">AI recommended</h2>
                    <div className="grid gap-4">
                        {aiRecommended.length ? (
                            aiRecommended.map((q) => <QuizCard key={q.id} quiz={q} extraQuery={extraQuery} />)
                        ) : (
                            <p className="text-sm text-theme-dim">No quizzes flagged for AI recommendation yet.</p>
                        )}
                    </div>
                </section>
                <section>
                    <h2 className="mb-4 text-lg font-semibold text-theme-strong">Trending</h2>
                    <div className="grid gap-4">
                        {trending.length ? (
                            trending.map((q) => <QuizCard key={q.id} quiz={q} extraQuery={extraQuery} />)
                        ) : (
                            <p className="text-sm text-theme-dim">Complete a few quizzes to see trending picks.</p>
                        )}
                    </div>
                </section>
            </div>

            <section>
                <h2 className="mb-4 text-lg font-semibold text-theme-strong">All matching quizzes</h2>
                {quizzes.length === 0 ? (
                    <p className="rounded-xl border border-dashed border-theme-line p-8 text-center text-theme-dim">
                        No quizzes match your filters. Try clearing department or program filters.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {quizzes.map((q) => (
                            <QuizCard key={q.id} quiz={q} extraQuery={extraQuery} />
                        ))}
                    </div>
                )}
            </section>
        </PracticeHubLayout>
    );
}
