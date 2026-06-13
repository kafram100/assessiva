import { useState } from 'react';

function statusTone(status) {
    const s = (status || '').toLowerCase();
    if (s === 'correct') {
        return 'border-emerald-600/40 bg-emerald-950/30 text-emerald-100';
    }
    if (s === 'partial') {
        return 'border-amber-600/40 bg-amber-950/30 text-amber-100';
    }
    if (
        s === 'incorrect' ||
        s === 'unanswered' ||
        s === 'ai_unavailable' ||
        s === 'ai_marking_disabled'
    ) {
        return 'border-rose-600/40 bg-rose-950/30 text-rose-100';
    }
    return 'border-theme-line bg-theme-surface-soft text-theme-muted';
}

export default function QuestionEvaluationBreakdown({
    qType,
    question,
    answer,
    contribution,
}) {
    const [open, setOpen] = useState(true);
    if (!answer) {
        return null;
    }

    const max = Number(question.marks ?? 0);
    const awarded = Number(answer.marks_awarded ?? 0);
    const aiSnap = answer.ai_marks_awarded;
    const conf =
        answer.ai_confidence != null
            ? Math.round(Number(answer.ai_confidence) * 100)
            : null;
    const detected = answer.detected_concepts ?? [];
    const missing = answer.missing_concepts ?? [];
    const hasEval =
        answer.evaluation_reason ||
        answer.ai_evaluation ||
        (detected && detected.length) ||
        (missing && missing.length) ||
        conf != null;

    return (
        <div className="mt-3 rounded-lg border border-theme-line bg-theme-surface-soft/40">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full flex-wrap items-center justify-between gap-2 px-3 py-2 text-left text-xs font-semibold uppercase tracking-widest text-theme-dim hover:bg-theme-highlight/40"
            >
                <span>Score &amp; evaluation breakdown</span>
                <span className="font-mono text-theme-strong">
                    +{Number(contribution ?? 0).toFixed(2)} / {max} toward attempt
                </span>
                <span className="text-theme-muted">{open ? '▼' : '▶'}</span>
            </button>
            {open && (
                <div className="space-y-3 border-t border-theme-line px-3 py-3 text-xs text-theme-muted">
                    <div className="flex flex-wrap gap-2">
                        <span
                            className={`rounded border px-2 py-0.5 font-semibold uppercase tracking-wide ${statusTone(answer.evaluation_status)}`}
                        >
                            {answer.evaluation_status || '—'}
                        </span>
                        {conf != null && (
                            <span className="rounded border border-sky-600/40 bg-sky-950/30 px-2 py-0.5 text-sky-100">
                                Confidence {conf}%
                            </span>
                        )}
                    </div>

                    <dl className="grid gap-2 sm:grid-cols-2">
                        <div>
                            <dt className="text-theme-dim">Final marks (stored)</dt>
                            <dd className="font-mono text-sm text-theme-strong">
                                {awarded.toFixed(2)} / {max}
                            </dd>
                        </div>
                        {aiSnap != null && (
                            <div>
                                <dt className="text-theme-dim">AI suggested marks</dt>
                                <dd className="font-mono text-sm text-theme-strong">
                                    {Number(aiSnap).toFixed(2)} / {max}
                                </dd>
                            </div>
                        )}
                    </dl>

                    {qType === 'theory' &&
                        answer.ai_concept_coverage != null && (
                            <p className="text-theme-dim">
                                Concept coverage (AI):{' '}
                                <span className="font-mono text-theme-strong">
                                    {Math.round(
                                        Number(answer.ai_concept_coverage) * 100,
                                    )}
                                    %
                                </span>
                            </p>
                        )}

                    {(answer.rubric_concepts ?? []).length > 0 &&
                        qType === 'theory' && (
                            <div>
                                <p className="font-medium text-theme-dim">
                                    Rubric concepts (AI)
                                </p>
                                <ul className="mt-1 list-inside list-disc text-theme-strong">
                                    {(answer.rubric_concepts ?? []).map((c) => (
                                        <li key={c}>{c}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                    {qType === 'mcq' && (
                        <p>
                            <span className="text-theme-dim">Correct option:</span>{' '}
                            <span className="font-mono text-theme-strong">
                                {question.correct_option ?? '—'}
                            </span>
                        </p>
                    )}

                    {(qType === 'fill_in' || qType === 'theory') && (
                        <div>
                            <p className="text-theme-dim">Expected / model answer</p>
                            <p className="mt-1 whitespace-pre-wrap rounded bg-theme-glass p-2 font-mono text-theme-strong">
                                {answer.expected_answer_snapshot ||
                                    question.correct_option ||
                                    '—'}
                            </p>
                        </div>
                    )}

                    {answer.evaluation_reason && (
                        <div>
                            <p className="font-medium text-theme-dim">Reason</p>
                            <p className="mt-1 whitespace-pre-wrap text-theme-strong">
                                {answer.evaluation_reason}
                            </p>
                        </div>
                    )}

                    {answer.ai_evaluation && (
                        <div>
                            <p className="font-medium text-theme-dim">AI notes</p>
                            <p className="mt-1 whitespace-pre-wrap text-theme-strong">
                                {answer.ai_evaluation}
                            </p>
                        </div>
                    )}

                    {qType === 'theory' &&
                        (detected?.length > 0 || missing?.length > 0) && (
                            <p className="text-theme-dim">
                                Approx. rubric coverage (from lists):{' '}
                                <span className="font-mono text-theme-strong">
                                    {Math.round(
                                        (100 * (detected?.length || 0)) /
                                            Math.max(
                                                1,
                                                (detected?.length || 0) +
                                                    (missing?.length || 0),
                                            ),
                                    )}
                                    %
                                </span>
                                <span className="ms-1 text-[10px] uppercase tracking-wide">
                                    (indicative)
                                </span>
                            </p>
                        )}

                    {hasEval && (
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p className="font-medium text-theme-dim">
                                    Detected concepts
                                </p>
                                <ul className="mt-1 list-inside list-disc text-theme-strong">
                                    {(detected || []).length ? (
                                        detected.map((c) => (
                                            <li key={c}>{c}</li>
                                        ))
                                    ) : (
                                        <li className="list-none text-theme-dim">
                                            —
                                        </li>
                                    )}
                                </ul>
                            </div>
                            <div>
                                <p className="font-medium text-theme-dim">
                                    Missing concepts
                                </p>
                                <ul className="mt-1 list-inside list-disc text-theme-strong">
                                    {(missing || []).length ? (
                                        missing.map((c) => (
                                            <li key={c}>{c}</li>
                                        ))
                                    ) : (
                                        <li className="list-none text-theme-dim">
                                            —
                                        </li>
                                    )}
                                </ul>
                            </div>
                        </div>
                    )}

                    {answer.lecturer_grade_comment && (
                        <div className="rounded border border-indigo-600/30 bg-indigo-950/20 p-2">
                            <p className="font-medium text-indigo-200">
                                Lecturer comment
                            </p>
                            <p className="mt-1 whitespace-pre-wrap text-indigo-100">
                                {answer.lecturer_grade_comment}
                            </p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
