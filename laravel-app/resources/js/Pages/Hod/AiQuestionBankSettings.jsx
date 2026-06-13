import HodLayout from '@/Layouts/HodLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';

export default function AiQuestionBankSettings({ department }) {
    const form = useForm({
        require_hod_approval_for_ai_questions: !!department
            ?.require_hod_approval_for_ai_questions,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('hod.ai-question-bank-settings.update'));
    };

    return (
        <HodLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    AI question bank (department)
                </h2>
            }
        >
            <Head title="AI question bank settings" />

            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('hod.dashboard')}
                    className="mb-6 inline-block text-sm text-ai-soft transition hover:text-theme-strong"
                >
                    ← Back to HoD overview
                </Link>

                <form
                    onSubmit={submit}
                    className="ai-glass-card space-y-6 p-6"
                >
                    <div>
                        <h3 className="font-display text-base font-semibold text-theme-strong">
                            Lecturer AI drafts
                        </h3>
                        <p className="mt-2 text-sm text-ai-muted">
                            When enabled, lecturers in{' '}
                            <span className="font-medium text-theme-strong">
                                {department?.name ?? 'your department'}
                            </span>{' '}
                            cannot self-approve AI-generated bank questions. They
                            submit each batch for your review; you approve or
                            reject from the AI review queue.
                        </p>
                    </div>

                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-theme-line bg-theme-highlight p-4">
                        <input
                            type="checkbox"
                            className="mt-1 size-4 rounded border-theme-line bg-ai-navy-900 accent-ai-accent"
                            checked={
                                !!form.data
                                    .require_hod_approval_for_ai_questions
                            }
                            onChange={(e) =>
                                form.setData(
                                    'require_hod_approval_for_ai_questions',
                                    e.target.checked,
                                )
                            }
                        />
                        <span>
                            <span className="block text-sm font-medium text-theme-strong">
                                Require HoD approval for AI-generated questions
                            </span>
                            <span className="mt-1 block text-xs text-ai-soft">
                                When off, lecturers approve AI drafts themselves
                                (default).
                            </span>
                        </span>
                    </label>

                    <PrimaryButton disabled={form.processing} type="submit">
                        Save settings
                    </PrimaryButton>
                </form>
            </div>
        </HodLayout>
    );
}
