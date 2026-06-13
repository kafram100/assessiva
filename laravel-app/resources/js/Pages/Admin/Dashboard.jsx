import AdminLayout from '@/Layouts/AdminLayout';
import ExportExcelButton from '@/Components/ExportExcelButton';
import { Head, Link } from '@inertiajs/react';

function StatCard({ label, value, tone = 'sky' }) {
    const tones = {
        sky: 'text-sky-400',
        violet: 'text-violet-400',
        amber: 'text-amber-400',
        emerald: 'text-emerald-400',
        rose: 'text-rose-400',
        slate: 'text-theme-muted',
    };

    return (
        <div className="rounded-lg border border-theme-line bg-theme-elevated p-4 text-center">
            <div className={`text-2xl font-bold ${tones[tone] ?? tones.sky}`}>
                {value}
            </div>
            <div className="mt-1 text-xs text-theme-dim">{label}</div>
        </div>
    );
}

function ToolCard({ title, description, href, primaryHref, primaryLabel, secondaryHref, secondaryLabel }) {
    return (
        <div className="flex h-full flex-col rounded-lg border border-theme-line bg-theme-elevated p-5">
            <h3 className="text-base font-semibold text-theme-strong">{title}</h3>
            <p className="mt-2 flex-1 text-sm text-theme-dim">{description}</p>
            <div className="mt-4 flex flex-wrap gap-2">
                <Link
                    href={href}
                    className="inline-flex rounded-md bg-sky-600 px-3 py-2 text-xs font-semibold text-white hover:bg-sky-500"
                >
                    {primaryLabel}
                </Link>
                {secondaryHref && (
                    <Link
                        href={secondaryHref}
                        className="inline-flex rounded-md border border-theme-line px-3 py-2 text-xs font-semibold text-theme-muted hover:bg-theme-surface-soft"
                    >
                        {secondaryLabel}
                    </Link>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({
    schoolCount,
    departmentCount,
    lecturerCount,
    pendingCount,
    examCount,
    attemptCount,
}) {
    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Administrator dashboard
                </h2>
            }
        >
            <Head title="Admin" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mb-8 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <StatCard label="Schools" value={schoolCount} tone="sky" />
                    <StatCard
                        label="Departments"
                        value={departmentCount}
                        tone="violet"
                    />
                    <StatCard
                        label="Academic staff"
                        value={lecturerCount}
                        tone="amber"
                    />
                    <StatCard label="Pending" value={pendingCount} tone="rose" />
                    <StatCard label="Exams" value={examCount} tone="emerald" />
                    <StatCard
                        label="Quizzes taken"
                        value={attemptCount}
                        tone="emerald"
                    />
                </div>

                <div className="mb-6 flex flex-wrap items-center gap-3 rounded-lg border border-theme-line bg-theme-elevated/60 p-4">
                    <span className="text-sm font-medium text-theme-strong">
                        Excel exports
                    </span>
                    <ExportExcelButton
                        href={route(
                            'admin.institutional-intelligence.export',
                        )}
                        className="normal-case tracking-normal"
                    >
                        Institutional analytics (Excel)
                    </ExportExcelButton>
                    <ExportExcelButton
                        href={route('admin.proctoring.export')}
                        className="normal-case tracking-normal"
                    >
                        Export proctoring (Excel)
                    </ExportExcelButton>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ToolCard
                        title="Schools"
                        description="Manage university schools (for example School of Engineering)."
                        href={route('admin.schools')}
                        primaryLabel="Manage schools"
                    />
                    <ToolCard
                        title="Departments"
                        description="Manage departments within each school."
                        href={route('admin.departments')}
                        primaryLabel="Manage departments"
                    />
                    <ToolCard
                        title="Academic staff"
                        description="Approve, reject, or add lecturers and HODs by department."
                        href={route('admin.lecturers')}
                        primaryLabel="View staff"
                        secondaryHref={route('admin.lecturers.create')}
                        secondaryLabel="Add staff"
                    />
                    <ToolCard
                        title="Proctoring incidents"
                        description="Students with the most proctoring alerts across quizzes."
                        href={route('admin.frequent-cheaters')}
                        primaryLabel="View report"
                    />
                    <ToolCard
                        title="All exams"
                        description="Browse in-window, upcoming, past, and draft exams from every lecturer."
                        href={route('admin.exams')}
                        primaryLabel="Browse exams"
                    />
                    <ToolCard
                        title="All quizzes taken"
                        description="View every completed quiz attempt across all exams."
                        href={route('admin.quizzes')}
                        primaryLabel="View all quizzes"
                    />
                    <ToolCard
                        title="All students"
                        description="Students who have completed at least one quiz."
                        href={route('admin.students')}
                        primaryLabel="View all students"
                    />
                    <ToolCard
                        title="Email settings"
                        description="Shared SMTP sender used when the platform sends mail on behalf of staff."
                        href={route('admin.email-settings')}
                        primaryLabel="Configure email"
                    />
                </div>
            </div>
        </AdminLayout>
    );
}
