export default function RevisionSuggestionCard({
    revisionPlanSummary,
    aiEnabled,
    revisionEnabled,
}) {
    if (!aiEnabled && !revisionEnabled) {
        return null;
    }

    const hasPlan =
        revisionPlanSummary &&
        (revisionPlanSummary.summary ||
            (revisionPlanSummary.weak_topics &&
                revisionPlanSummary.weak_topics.length > 0) ||
            (revisionPlanSummary.recommended_actions &&
                revisionPlanSummary.recommended_actions.length > 0));

    if (!revisionEnabled) {
        return null;
    }

    return (
        <div className="rounded-xl border border-indigo-500/30 bg-indigo-950/25 px-4 py-4 shadow-sm">
            <h2 className="text-sm font-semibold uppercase tracking-widest text-indigo-100/90">
                Next steps
            </h2>

            {hasPlan ? (
                <div className="mt-3 space-y-3">
                    {revisionPlanSummary.summary ? (
                        <p className="text-sm leading-relaxed text-theme-muted">
                            {revisionPlanSummary.summary}
                        </p>
                    ) : null}
                    {revisionPlanSummary.weak_topics &&
                    revisionPlanSummary.weak_topics.length > 0 ? (
                        <div>
                            <p className="text-xs font-semibold text-theme-dim">Priority topics</p>
                            <div className="mt-2 flex flex-wrap gap-1.5">
                                {revisionPlanSummary.weak_topics.slice(0, 8).map((t) => (
                                    <span
                                        key={t}
                                        className="rounded-md border border-indigo-400/30 bg-indigo-950/40 px-2 py-0.5 text-xs text-indigo-100"
                                    >
                                        {t}
                                    </span>
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {revisionPlanSummary.recommended_actions &&
                    revisionPlanSummary.recommended_actions.length > 0 ? (
                        <div>
                            <p className="text-xs font-semibold text-theme-dim">Suggested actions</p>
                            <ul className="mt-2 space-y-1.5 text-sm text-theme-muted">
                                {revisionPlanSummary.recommended_actions.slice(0, 4).map((a, i) => (
                                    <li key={i} className="flex gap-2">
                                        <span className="text-indigo-300/80">•</span>
                                        <span>{a}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </div>
            ) : (
                <p className="mt-3 text-sm text-theme-muted">
                    Open your AI revision plan for structured study steps, practice prompts, and
                    flashcards tailored to this attempt.
                </p>
            )}
        </div>
    );
}
