import ExportExcelButton from '@/Components/ExportExcelButton';
import HodLayout from '@/Layouts/HodLayout';
import { Head, Link } from '@inertiajs/react';

function StatCard({ label, value, hint }) {
    return (
        <div className="rounded-lg border border-theme-line bg-theme-glass p-4">
            <div className="text-2xl font-bold tabular-nums text-theme-strong">
                {value}
            </div>
            <div className="mt-1 text-[0.65rem] font-semibold uppercase tracking-widest text-theme-dim">
                {label}
            </div>
            {hint ? (
                <p className="mt-2 text-xs leading-snug text-theme-muted">
                    {hint}
                </p>
            ) : null}
        </div>
    );
}

export default function Dashboard({ department, stats }) {
    return (
        <HodLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Department overview
                </h2>
            }
        >
            <Head title="HOD — Overview" />

            <div className="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
                {stats.hasDepartment ? (
                    <>
                        <div className="rounded-lg border border-theme-line bg-theme-highlight p-5 text-theme-muted">
                            <p className="text-sm font-medium">
                                {department?.school?.name
                                    ? `${department.school.name} · `
                                    : ''}
                                <span className="text-theme-strong">
                                    {department?.name}
                                </span>
                            </p>
                            <p className="mt-2 text-sm leading-relaxed">
                                Review official bank submissions from your
                                lecturers, monitor department exams, and see
                                who is still pending approval.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <StatCard
                                label="Pending official reviews"
                                value={stats.pendingOfficialReviews}
                                hint="Questions awaiting your decision on the official bank."
                            />
                            <StatCard
                                label="Pending AI reviews"
                                value={stats.pendingAiReviews}
                                hint="AI drafts from your lecturers waiting in the HoD queue."
                            />
                            <StatCard
                                label="Lecturers (approved / total)"
                                value={`${stats.approvedLecturers} / ${stats.lecturersInDepartment}`}
                                hint={
                                    stats.pendingLecturers > 0
                                        ? `${stats.pendingLecturers} registration(s) still need admin approval.`
                                        : 'All lecturers in this department are approved.'
                                }
                            />
                            <StatCard
                                label="Exams (published / total)"
                                value={`${stats.publishedExams} / ${stats.departmentExams}`}
                                hint="Exams owned by lecturers (or you) in this department."
                            />
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={route(
                                    'admin.official-question-reviews.index',
                                )}
                                className="inline-flex rounded-md bg-ai-accent px-4 py-2 text-sm font-semibold text-white shadow hover:brightness-110"
                            >
                                Official question reviews
                            </Link>
                            <Link
                                href={route(
                                    'hod.ai-question-reviews.index',
                                )}
                                className="inline-flex rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-sm font-semibold text-theme-strong hover:bg-theme-surface-soft"
                            >
                                AI question reviews
                            </Link>
                            <Link
                                href={route(
                                    'hod.ai-question-bank-settings.edit',
                                )}
                                className="inline-flex rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-sm font-semibold text-theme-strong hover:bg-theme-surface-soft"
                            >
                                AI bank settings
                            </Link>
                            <Link
                                href={route('hod.lecturers.index')}
                                className="inline-flex rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-sm font-semibold text-theme-strong hover:bg-theme-surface-soft"
                            >
                                Lecturer directory
                            </Link>
                            <Link
                                href={route('hod.exams.index')}
                                className="inline-flex rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-sm font-semibold text-theme-strong hover:bg-theme-surface-soft"
                            >
                                Department exams
                            </Link>
                            <ExportExcelButton
                                href={route('hod.academic-intelligence.export')}
                                className="normal-case tracking-normal"
                            >
                                Department analytics (Excel)
                            </ExportExcelButton>
                        </div>
                    </>
                ) : (
                    <div className="rounded-lg border border-amber-400/50 bg-amber-50 p-6 text-amber-900 dark:border-amber-800/80 dark:bg-amber-950/40 dark:text-amber-100">
                        <h3 className="text-base font-semibold text-amber-950 dark:text-amber-50">
                            No department assigned
                        </h3>
                        <p className="mt-2 text-sm leading-relaxed text-amber-900/90 dark:text-amber-200/90">
                            Your account is marked as Head of Department, but
                            no department is linked yet. Ask a system
                            administrator to set your{' '}
                            <code className="rounded bg-amber-200/80 px-1.5 py-0.5 text-xs dark:bg-amber-950/80">
                                department_id
                            </code>{' '}
                            so you can see reviews, lecturers, and exams for
                            your unit.
                        </p>
                    </div>
                )}
            </div>
        </HodLayout>
    );
}
