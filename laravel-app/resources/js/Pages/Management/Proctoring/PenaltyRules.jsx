import PenaltyRuleForm from '@/Components/Proctoring/PenaltyRuleForm';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';
import { useCallback } from 'react';

export default function PenaltyRules({ exam, rules: initialRules, eventTypes }) {
    const form = useForm({ rules: initialRules });

    const updateRule = useCallback(
        (eventType, patch) => {
            form.setData(
                'rules',
                (form.data.rules ?? []).map((r) =>
                    r.event_type === eventType ? { ...r, ...patch } : r,
                ),
            );
        },
        [form],
    );

    const resetDefaults = () => {
        form.setData(
            'rules',
            (eventTypes ?? []).map((event_type) => ({
                id: null,
                event_type,
                penalty_marks: 0,
                is_enabled: false,
                severity: null,
                description: null,
            })),
        );
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.management.exams.proctoring.penalties.store', exam.id), {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            Proctoring penalty rules
                        </h2>
                        <p className="text-sm text-theme-dim">
                            {exam.title}{' '}
                            <span className="font-mono text-xs">
                                ({exam.exam_code})
                            </span>
                        </p>
                    </div>
                    <Link
                        href={route('admin.management.proctoring.report', {
                            exam_id: exam.id,
                        })}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-200"
                    >
                        ← Proctoring report
                    </Link>
                </div>
            }
        >
            <Head title="Proctoring penalties" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-amber-500/30 bg-amber-950/20 p-4 text-sm text-amber-100">
                        Events are logged as <strong>suspicious activity</strong>{' '}
                        until you review them. Only events you mark{' '}
                        <strong>Apply</strong> with enabled rules deduct marks.
                        Raw scores are never overwritten; the stored score is the
                        adjusted total (never below zero).
                    </div>

                    <form
                        onSubmit={submit}
                        className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow"
                    >
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left">
                                <thead>
                                    <tr className="border-b text-xs font-semibold uppercase tracking-widest text-theme-dim">
                                        <th className="py-2 pr-3">Event type</th>
                                        <th className="py-2 pr-3">Marks deducted</th>
                                        <th className="py-2 pr-3">Use penalty</th>
                                        <th className="py-2 pr-3">Tag</th>
                                        <th className="py-2">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(form.data.rules ?? []).map((rule) => (
                                        <PenaltyRuleForm
                                            key={rule.event_type}
                                            rule={rule}
                                            onChange={updateRule}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {form.errors.rules && (
                            <p className="mt-2 text-sm text-rose-600">
                                {form.errors.rules}
                            </p>
                        )}

                        <div className="mt-6 flex flex-wrap gap-3">
                            <PrimaryButton type="submit" disabled={form.processing}>
                                Save rules
                            </PrimaryButton>
                            <button
                                type="button"
                                onClick={resetDefaults}
                                className="inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted hover:bg-theme-highlight/80"
                            >
                                Reset defaults
                            </button>
                            <Link
                                href={route('admin.management.exams.edit', exam.id)}
                                className="self-center text-sm text-sky-700 hover:text-sky-900 dark:text-sky-400"
                            >
                                Edit exam
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
