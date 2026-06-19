import { useState } from 'react';

function typeLabel(qType) {
    switch (qType) {
        case 'mcq':
            return 'Multiple choice';
        case 'fill_in':
            return 'Fill in';
        case 'theory':
            return 'Theory';
        case 'file':
            return 'File upload';
        default:
            return qType || 'Question';
    }
}

function statusBadgeClass(status) {
    const s = (status || '').toLowerCase();
    if (s === 'correct') {
        return 'border-emerald-600/50 bg-emerald-500/15 text-emerald-800 dark:text-emerald-200';
    }
    if (s === 'partial') {
        return 'border-amber-600/50 bg-amber-500/15 text-amber-900 dark:text-amber-100';
    }
    if (s === 'incorrect' || s === 'unanswered') {
        return 'border-rose-600/50 bg-rose-500/15 text-rose-900 dark:text-rose-100';
    }
    return 'border-theme-line bg-theme-highlight text-theme-muted';
}

function understandingBadgeClass(level) {
    const l = (level || '').toLowerCase();
    if (l.includes('strong')) {
        return 'border-emerald-500/40 bg-emerald-950/30 text-emerald-100';
    }
    if (l.includes('partial')) {
        return 'border-amber-500/40 bg-amber-950/30 text-amber-100';
    }
    if (l.includes('insufficient') || l.includes('off-topic')) {
        return 'border-rose-500/40 bg-rose-950/30 text-rose-100';
    }
    if (l.includes('unanswered')) {
        return 'border-slate-500/40 bg-slate-950/30 text-slate-200';
    }
    return 'border-violet-500/40 bg-violet-950/30 text-violet-100';
}

function ConceptTagList({ items }) {
    if (!items || items.length === 0) {
        return null;
    }
    return (
        <div className="flex flex-wrap gap-1.5">
            {items.map((c) => (
                <span
                    key={c}
                    className="rounded-md border border-rose-500/25 bg-rose-950/20 px-2 py-0.5 text-xs text-theme-strong"
                >
                    {c}
                </span>
            ))}
        </div>
    );
}

function ThemeGroupList({ themes }) {
    if (!themes || themes.length === 0) {
        return null;
    }
    return (
        <div className="space-y-2">
            {themes.map((group) => (
                <div
                    key={group.theme}
                    className="rounded-lg border border-theme-line bg-theme-highlight/60 px-3 py-2"
                >
                    <p className="text-xs font-semibold text-theme-strong">{group.theme}</p>
                    <div className="mt-1.5 flex flex-wrap gap-1.5">
                        {group.concepts.map((c) => (
                            <span
                                key={`${group.theme}-${c}`}
                                className="rounded-md bg-theme-glass px-2 py-0.5 text-xs text-theme-muted"
                            >
                                {c}
                            </span>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function QuestionFeedbackCard({ item, flags }) {
    const [open, setOpen] = useState(false);
    const [detailsOpen, setDetailsOpen] = useState(false);

    if (!item) {
        return null;
    }

    const showExpected = flags?.show_expected_answers;
    const showAi = flags?.show_ai_feedback;

    const confPct =
        item.ai_confidence != null ? Math.round(Number(item.ai_confidence) * 100) : null;
    const coveragePct =
        item.ai_concept_coverage != null
            ? Math.round(Number(item.ai_concept_coverage) * 100)
            : null;

    const status = item.display_status || '-';
    const missingTags = item.missing_concept_tags ?? [];
    const hasAiInsights =
        showAi &&
        (item.understanding_level ||
            item.concise_feedback ||
            missingTags.length > 0 ||
            item.revision_focus ||
            item.detected_concepts?.length > 0);

    const hasTransparencyDetails =
        showAi &&
        (confPct != null ||
            coveragePct != null ||
            item.rubric_concepts?.length > 0 ||
            item.detected_concepts?.length > 0 ||
            item.evaluation_status);

    return (
        <div className="overflow-hidden rounded-xl border border-theme-line bg-theme-glass shadow-sm">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full flex-col gap-2 px-4 py-3 text-left sm:flex-row sm:items-center sm:justify-between"
            >
                <div className="min-w-0 flex-1">
                    <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                        {typeLabel(item.q_type)}
                    </p>
                    <p className="mt-1 line-clamp-2 text-sm font-medium text-theme-strong">
                        {item.question_text}
                    </p>
                </div>
                <div className="flex flex-shrink-0 flex-wrap items-center gap-2">
                    <span
                        className={`rounded-md border px-2 py-1 text-xs font-semibold capitalize ${statusBadgeClass(status)}`}
                    >
                        {status}
                    </span>
                    <span className="font-mono text-sm text-theme-strong">
                        {Number(item.marks_counted ?? 0).toFixed(2)} /{' '}
                        {Number(item.max_marks ?? 0).toFixed(2)}
                    </span>
                    <span className="text-xs text-theme-dim">{open ? '▼' : '▶'}</span>
                </div>
            </button>

            {open && (
                <div className="space-y-4 border-t border-theme-line px-4 py-4 text-sm">
                    {item.q_type === 'mcq' ? (
                        <dl className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs text-theme-dim">Your choice</dt>
                                <dd className="mt-0.5 text-theme-strong">
                                    {item.selected_option
                                        ? `${item.selected_option}${
                                              item.selected_option_text
                                                  ? ` - ${item.selected_option_text}`
                                                  : ''
                                          }`
                                        : '-'}
                                </dd>
                            </div>
                            {showExpected ? (
                                <div>
                                    <dt className="text-xs text-theme-dim">Expected understanding</dt>
                                    <dd className="mt-0.5 text-theme-strong">
                                        {item.correct_option
                                            ? `${item.correct_option}${
                                                  item.correct_option_text
                                                      ? ` - ${item.correct_option_text}`
                                                      : ''
                                              }`
                                            : '-'}
                                    </dd>
                                </div>
                            ) : null}
                        </dl>
                    ) : null}

                    {item.q_type === 'fill_in' ? (
                        <dl className="grid gap-3">
                            <div>
                                <dt className="text-xs text-theme-dim">Your answer</dt>
                                <dd className="mt-0.5 whitespace-pre-wrap text-theme-strong">
                                    {item.student_answer ?? '-'}
                                </dd>
                            </div>
                            {showExpected ? (
                                <div>
                                    <dt className="text-xs text-theme-dim">Expected understanding</dt>
                                    <dd className="mt-0.5 text-theme-strong">
                                        {item.accepted_fill_in ?? item.expected_answer ?? '-'}
                                    </dd>
                                </div>
                            ) : null}
                        </dl>
                    ) : null}

                    {(item.q_type === 'theory' || item.q_type === 'file') && (
                        <dl className="grid gap-3">
                            {item.q_type === 'theory' ? (
                                <div>
                                    <dt className="text-xs text-theme-dim">Your answer</dt>
                                    <dd className="mt-0.5 max-h-40 overflow-y-auto whitespace-pre-wrap text-theme-strong">
                                        {item.student_answer ?? '-'}
                                    </dd>
                                </div>
                            ) : null}
                            {item.q_type === 'file' ? (
                                <div>
                                    <dt className="text-xs text-theme-dim">Submission</dt>
                                    <dd className="mt-0.5 text-theme-strong">
                                        {item.file_upload_name
                                            ? `Uploaded file: ${item.file_upload_name}`
                                            : 'No file on record'}
                                    </dd>
                                </div>
                            ) : null}
                            {showExpected && item.expected_answer ? (
                                <div>
                                    <dt className="text-xs text-theme-dim">Expected understanding</dt>
                                    <dd className="mt-0.5 whitespace-pre-wrap text-theme-strong">
                                        {item.expected_answer}
                                    </dd>
                                </div>
                            ) : null}
                        </dl>
                    )}

                    {item.marks_recorded != null &&
                    Math.abs(Number(item.marks_recorded) - Number(item.marks_counted)) > 0.01 ? (
                        <p className="rounded-md border border-sky-500/30 bg-sky-950/25 px-3 py-2 text-xs text-sky-100">
                            Recorded mark:{' '}
                            <span className="font-mono font-semibold">
                                {Number(item.marks_recorded).toFixed(2)}
                            </span>{' '}
                            · graded total{' '}
                            <span className="font-mono font-semibold">
                                {Number(item.marks_counted).toFixed(2)}
                            </span>
                        </p>
                    ) : null}

                    {hasAiInsights ? (
                        <div className="space-y-3 rounded-lg border border-violet-500/20 bg-violet-950/15 p-3">
                            {item.understanding_level ? (
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                        AI understanding level
                                    </p>
                                    <span
                                        className={`mt-1.5 inline-flex rounded-md border px-2.5 py-1 text-xs font-semibold ${understandingBadgeClass(item.understanding_level)}`}
                                    >
                                        {item.understanding_level}
                                    </span>
                                </div>
                            ) : null}

                            {item.concise_feedback ? (
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                        Concise feedback
                                    </p>
                                    <p className="mt-1.5 leading-relaxed text-theme-muted">
                                        {item.concise_feedback}
                                    </p>
                                </div>
                            ) : null}

                            {missingTags.length > 0 ? (
                                <div>
                                    <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                        Key concepts missed
                                    </p>
                                    {item.concept_themes?.length > 0 ? (
                                        <ThemeGroupList themes={item.concept_themes} />
                                    ) : (
                                        <ConceptTagList items={missingTags} />
                                    )}
                                </div>
                            ) : null}

                            {item.revision_focus ? (
                                <div className="rounded-md border border-amber-500/25 bg-amber-950/20 px-3 py-2">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-amber-200/90">
                                        Revision focus
                                    </p>
                                    <p className="mt-1 text-sm leading-relaxed text-amber-50/95">
                                        {item.revision_focus}
                                    </p>
                                </div>
                            ) : null}

                            {hasTransparencyDetails ? (
                                <div>
                                    <button
                                        type="button"
                                        onClick={() => setDetailsOpen(!detailsOpen)}
                                        className="text-xs font-medium text-violet-200/90 underline decoration-violet-400/40 underline-offset-2 hover:text-violet-100"
                                    >
                                        {detailsOpen ? 'Hide AI details' : 'Show AI details'}
                                    </button>
                                    {detailsOpen ? (
                                        <div className="mt-3 space-y-2 rounded-md border border-violet-500/20 bg-violet-950/20 p-3 text-xs">
                                            <div className="flex flex-wrap gap-2">
                                                {item.evaluation_status ? (
                                                    <span className="rounded border border-violet-400/30 px-2 py-0.5 text-violet-100">
                                                        Status: {item.evaluation_status}
                                                    </span>
                                                ) : null}
                                                {confPct != null ? (
                                                    <span className="rounded border border-sky-500/30 px-2 py-0.5 text-sky-100">
                                                        Confidence {confPct}%
                                                    </span>
                                                ) : null}
                                                {coveragePct != null ? (
                                                    <span className="rounded border border-violet-500/30 px-2 py-0.5 text-violet-100">
                                                        Concept coverage {coveragePct}%
                                                    </span>
                                                ) : null}
                                            </div>
                                            {item.detected_concepts?.length > 0 ? (
                                                <div>
                                                    <p className="font-semibold text-theme-dim">
                                                        Detected strengths
                                                    </p>
                                                    <div className="mt-1 flex flex-wrap gap-1.5">
                                                        {item.detected_concepts.map((c) => (
                                                            <span
                                                                key={c}
                                                                className="rounded-md border border-emerald-500/25 bg-emerald-950/20 px-2 py-0.5 text-theme-strong"
                                                            >
                                                                {c}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            ) : null}
                                            {item.rubric_concepts?.length > 0 ? (
                                                <div>
                                                    <p className="font-semibold text-theme-dim">
                                                        Rubric concepts
                                                    </p>
                                                    <div className="mt-1 flex flex-wrap gap-1.5">
                                                        {item.rubric_concepts.map((c) => (
                                                            <span
                                                                key={c}
                                                                className="rounded-md bg-theme-glass px-2 py-0.5 text-theme-muted"
                                                            >
                                                                {c}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            ) : null}
                                        </div>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>
                    ) : showAi ? (
                        <p className="text-xs text-theme-dim">
                            No detailed AI feedback was recorded for this question.
                        </p>
                    ) : (
                        <p className="text-xs text-theme-dim">
                            Detailed AI feedback is hidden for this exam. Your marks are shown above.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
