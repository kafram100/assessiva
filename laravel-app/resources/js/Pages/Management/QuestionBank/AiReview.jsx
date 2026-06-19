import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

export default function AiReview({
    generationRequest,
    questions,
    approvableCount,
}) {
    const bulk = useForm({});
    const pageErrors = usePage().props.errors || {};

    const approveAll = () => {
        if (
            !confirm(
                `Approve all ${approvableCount} draft/rejected question(s) in this run?`,
            )
        ) {
            return;
        }
        bulk.post(
            route(
                'lecturer.question-bank.review.approve-all',
                generationRequest.id,
            ),
        );
    };

    const showSelfApprove = approvableCount > 0;

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Review AI batch #{generationRequest.id}
                    </h2>
                    <Link
                        href={route('admin.management.question-bank.index')}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Question bank
                    </Link>
                </div>
            }
        >
            <Head title="AI review" />

            <div className="space-y-6 py-8">
                {pageErrors.approve && (
                    <div className="mx-auto max-w-4xl rounded-md border border-amber-800 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 sm:px-6 lg:px-8">
                        {pageErrors.approve}
                    </div>
                )}
                {pageErrors.submit && (
                    <div className="mx-auto max-w-4xl rounded-md border border-amber-800 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 sm:px-6 lg:px-8">
                        {pageErrors.submit}
                    </div>
                )}



                <div className="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 sm:px-6 lg:px-8">
                    <p className="text-sm text-theme-dim">
                        Provider:{' '}
                        <strong>
                            {generationRequest.ai_provider ?? '-'}
                        </strong>{' '}
                        · Model:{' '}
                        <strong>
                            {generationRequest.ai_model ?? '-'}
                        </strong>{' '}
                        · Generated:{' '}
                        <strong>{generationRequest.generated_count}</strong>
                    </p>

                        {showSelfApprove && (
                            <PrimaryButton
                                type="button"
                                disabled={bulk.processing}
                                onClick={approveAll}
                            >
                                Approve all ({approvableCount})
                            </PrimaryButton>
                        )}
                    </div>

                <div className="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
                    {questions.map((q) => (
                        <div
                            key={q.id}
                            className="rounded-lg border border-theme-line bg-theme-glass p-4 shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <p className="text-sm font-medium text-theme-strong">
                                    {q.question_text}
                                </p>
                                <span className="shrink-0 rounded bg-theme-surface-soft px-2 py-0.5 text-xs uppercase text-theme-dim">
                                    {q.question_type} · {q.status}
                                </span>
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {['draft', 'rejected'].includes(q.status) && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.post(
                                                    route(
                                                        'lecturer.questions.approve',
                                                        q.id,
                                                    ),
                                                )
                                            }
                                            className="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600"
                                        >
                                            Approve
                                        </button>
                                    )}
                                {q.status === 'draft' && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const r = window.prompt(
                                                'Reject reason (optional)',
                                            );
                                            router.post(
                                                route(
                                                    'lecturer.questions.reject',
                                                    q.id,
                                                ),
                                                { reason: r ?? '' },
                                            );
                                        }}
                                        className="rounded border border-theme-line px-3 py-1.5 text-xs font-semibold text-theme-muted hover:bg-theme-surface-soft/60"
                                    >
                                        Reject
                                    </button>
                                )}
                            </div>
                            {q.options?.length > 0 && (
                                <ul className="mt-3 list-inside list-disc text-xs text-theme-dim">
                                    {q.options.map((o) => (
                                        <li key={o.id}>
                                            <span className="font-mono">
                                                {o.option_key}
                                            </span>
                                            . {o.option_text}
                                            {o.is_correct ? ' ✓' : ''}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
