function LearningThemeGroups({ title, groups, tone }) {
    if (!groups || groups.length === 0) {
        return null;
    }

    const wrap =
        tone === 'weak'
            ? 'border-rose-500/25 bg-rose-950/15'
            : 'border-emerald-500/25 bg-emerald-950/15';

    return (
        <div>
            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-theme-dim">
                {title}
            </p>
            <div className="space-y-2">
                {groups.map((group) => (
                    <div
                        key={group.theme}
                        className={`rounded-lg border px-3 py-2.5 ${wrap}`}
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
        </div>
    );
}

export default function PerformanceSummaryCard({
    scoreSummary,
    learningThemes,
    showAi,
}) {
    if (!scoreSummary) {
        return null;
    }

    const raw = Number(scoreSummary.raw_score ?? 0);
    const pen = Number(scoreSummary.proctoring_penalty_total ?? 0);
    const final = Number(scoreSummary.final_score ?? 0);
    const total = Number(scoreSummary.total_marks ?? 0);
    const showPenalty = Math.abs(pen) > 0.0001;

    const strengthen = learningThemes?.to_strengthen ?? [];
    const demonstrated = learningThemes?.demonstrated ?? [];
    const showThemes = showAi && (strengthen.length > 0 || demonstrated.length > 0);

    return (
        <div className="rounded-xl border border-theme-line bg-gradient-to-br from-slate-900/40 to-slate-950/60 p-5 shadow-lg dark:from-slate-950/50 dark:to-black/40">
            <h2 className="text-sm font-semibold uppercase tracking-widest text-theme-dim">
                Score overview
            </h2>
            <dl className="mt-4 grid gap-4 sm:grid-cols-3">
                <div className="rounded-lg border border-theme-line bg-theme-glass/80 px-3 py-3">
                    <dt className="text-xs text-theme-dim">Raw score</dt>
                    <dd className="mt-1 font-mono text-lg font-bold text-theme-strong">
                        {raw.toFixed(2)} / {total}
                    </dd>
                    <p className="mt-1 text-xs text-theme-dim">Before proctoring penalties</p>
                </div>
                <div className="rounded-lg border border-theme-line bg-theme-glass/80 px-3 py-3">
                    <dt className="text-xs text-theme-dim">Penalty deduction</dt>
                    <dd
                        className={`mt-1 font-mono text-lg font-bold ${
                            showPenalty
                                ? 'text-rose-600 dark:text-rose-300'
                                : 'text-theme-strong'
                        }`}
                    >
                        {showPenalty ? `−${pen.toFixed(2)}` : '0'}
                    </dd>
                    <p className="mt-1 text-xs text-theme-dim">
                        Applied after lecturer review (if any)
                    </p>
                </div>
                <div className="rounded-lg border border-emerald-600/40 bg-emerald-950/25 px-3 py-3">
                    <dt className="text-xs text-emerald-100/80">Final score</dt>
                    <dd className="mt-1 font-mono text-lg font-bold text-emerald-100">
                        {final.toFixed(2)} / {total}
                    </dd>
                    <p className="mt-1 text-xs text-emerald-100/70">
                        What counts on your transcript for this attempt
                    </p>
                </div>
            </dl>

            {showThemes ? (
                <div className="mt-6 space-y-4 border-t border-theme-line pt-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                        Learning themes
                    </p>
                    <LearningThemeGroups
                        title="Demonstrated well"
                        groups={demonstrated}
                        tone="strong"
                    />
                    <LearningThemeGroups
                        title="Strengthen next"
                        groups={strengthen}
                        tone="weak"
                    />
                </div>
            ) : null}
        </div>
    );
}
