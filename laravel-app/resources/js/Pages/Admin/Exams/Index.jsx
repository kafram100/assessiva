import AdminPagination from '@/Components/AdminPagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function fmt(dt) {
    if (!dt) {
        return '-';
    }
    return new Date(dt).toLocaleString();
}

function scheduleBadge(exam, nowIso) {
    const now = new Date(nowIso);
    const start = exam.start_datetime ? new Date(exam.start_datetime) : null;
    const end = exam.end_datetime ? new Date(exam.end_datetime) : null;
    if (!exam.is_published) {
        return { label: 'Draft', cls: 'bg-slate-600 text-theme-strong' };
    }
    if (start && now < start) {
        return { label: 'Upcoming', cls: 'bg-sky-700 text-white' };
    }
    if (end && now > end) {
        return { label: 'Ended', cls: 'bg-theme-surface-soft text-theme-muted' };
    }
    return { label: 'In window', cls: 'bg-emerald-700 text-white' };
}

export default function Index({
    exams,
    schools,
    departments,
    lecturers,
    schedule,
    filters,
    now,
}) {
    const [local, setLocal] = useState({
        schedule: schedule ?? 'all',
        school_id: filters?.school_id ?? '',
        department_id: filters?.department_id ?? '',
        lecturer_id: filters?.lecturer_id ?? '',
        q: filters?.q ?? '',
    });

    useEffect(() => {
        setLocal({
            schedule: schedule ?? 'all',
            school_id: filters?.school_id ?? '',
            department_id: filters?.department_id ?? '',
            lecturer_id: filters?.lecturer_id ?? '',
            q: filters?.q ?? '',
        });
    }, [schedule, filters]);

    const deptOptions = useMemo(() => {
        if (!local.school_id) {
            return departments;
        }
        return departments.filter(
            (d) => String(d.school_id) === String(local.school_id),
        );
    }, [departments, local.school_id]);

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('admin.exams'), local, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        const cleared = {
            schedule: 'all',
            school_id: '',
            department_id: '',
            lecturer_id: '',
            q: '',
        };
        setLocal(cleared);
        router.get(route('admin.exams'), cleared, { replace: true });
    };

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    All exams (system-wide)
                </h2>
            }
        >
            <Head title="All exams" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.dashboard')}
                    className="inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to admin
                </Link>
                <p className="text-sm text-theme-dim">
                    Filter by schedule, school, department, or lecturer. Each row
                    shows how many students have started or completed the exam.
                </p>

                <form
                    onSubmit={applyFilters}
                    className="rounded-lg border border-theme-line bg-theme-glass p-4"
                >
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        <div>
                            <label className="block text-xs text-theme-dim">
                                Schedule
                            </label>
                            <select
                                className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                                value={local.schedule}
                                onChange={(e) =>
                                    setLocal((p) => ({
                                        ...p,
                                        schedule: e.target.value,
                                    }))
                                }
                            >
                                <option value="all">
                                    All published &amp; drafts
                                </option>
                                <option value="active">In window (now)</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="ended">Ended (past)</option>
                                <option value="unpublished">Drafts only</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs text-theme-dim">
                                School
                            </label>
                            <select
                                className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                                value={local.school_id}
                                onChange={(e) =>
                                    setLocal((p) => ({
                                        ...p,
                                        school_id: e.target.value,
                                        department_id: '',
                                    }))
                                }
                            >
                                <option value="">Any</option>
                                {schools.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs text-theme-dim">
                                Department
                            </label>
                            <select
                                className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                                value={local.department_id}
                                onChange={(e) =>
                                    setLocal((p) => ({
                                        ...p,
                                        department_id: e.target.value,
                                    }))
                                }
                            >
                                <option value="">Any</option>
                                {deptOptions.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                        {d.school
                                            ? ` - ${d.school.name}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs text-theme-dim">
                                Lecturer
                            </label>
                            <select
                                className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                                value={local.lecturer_id}
                                onChange={(e) =>
                                    setLocal((p) => ({
                                        ...p,
                                        lecturer_id: e.target.value,
                                    }))
                                }
                            >
                                <option value="">Any</option>
                                {lecturers.map((l) => (
                                    <option key={l.id} value={l.id}>
                                        {l.username}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-xs text-theme-dim">
                                Search
                            </label>
                            <input
                                type="text"
                                value={local.q}
                                onChange={(e) =>
                                    setLocal((p) => ({
                                        ...p,
                                        q: e.target.value,
                                    }))
                                }
                                placeholder="Title, course, code…"
                                className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                            />
                        </div>
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <button
                            type="submit"
                            className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                        >
                            Apply
                        </button>
                        <button
                            type="button"
                            onClick={resetFilters}
                            className="rounded-md border border-theme-line px-4 py-2 text-sm text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Reset
                        </button>
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="border-b border-theme-line px-4 py-3 text-sm font-medium text-theme-muted">
                        Exams ({exams.total})
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Exam</th>
                                    <th className="px-4 py-3">Course</th>
                                    <th className="px-4 py-3">Lecturer</th>
                                    <th className="px-4 py-3">Dept / school</th>
                                    <th className="px-4 py-3">Window</th>
                                    <th className="px-4 py-3 text-center">
                                        Students
                                    </th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {exams.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No exams match these filters.
                                        </td>
                                    </tr>
                                )}
                                {exams.data.map((exam) => {
                                    const badge = scheduleBadge(exam, now);
                                    const u = exam.user;
                                    return (
                                        <tr
                                            key={exam.id}
                                            className="hover:bg-theme-elevated"
                                        >
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-block rounded px-2 py-0.5 text-xs ${badge.cls}`}
                                                >
                                                    {badge.label}
                                                </span>
                                                {exam.exam_type && (
                                                    <span className="ms-1 rounded border border-theme-line px-1.5 py-0.5 text-xs text-theme-dim">
                                                        {exam.exam_type}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-theme-strong">
                                                {exam.title}
                                            </td>
                                            <td className="px-4 py-3">
                                                {exam.course_name}
                                                {exam.course_code
                                                    ? ` (${exam.course_code})`
                                                    : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                {u?.username ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {u?.department ? (
                                                    <>
                                                        {u.department.name}
                                                        {u.department.school
                                                            ? ` - ${u.department.school.name}`
                                                            : ''}
                                                    </>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-xs">
                                                {fmt(exam.start_datetime)} →{' '}
                                                {fmt(exam.end_datetime)}
                                            </td>
                                            <td className="px-4 py-3 text-center text-xs">
                                                <span className="text-theme-dim">
                                                    ongoing{' '}
                                                </span>
                                                {exam.attempts_ongoing_count}
                                                <span className="mx-1 text-slate-600">
                                                    /
                                                </span>
                                                <span className="text-theme-dim">
                                                    done{' '}
                                                </span>
                                                {
                                                    exam.attempts_completed_count
                                                }
                                                <span className="mx-1 text-slate-600">
                                                    /
                                                </span>
                                                <span className="text-theme-dim">
                                                    total{' '}
                                                </span>
                                                {exam.attempts_total_count}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={route(
                                                        'admin.exams.show',
                                                        exam.id,
                                                    )}
                                                    className="text-sky-400 hover:text-sky-300"
                                                >
                                                    Attempts
                                                </Link>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination links={exams.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
