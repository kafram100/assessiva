export default function AIRecommendationPanel({
    lines = [],
    title = 'AI recommendations',
    subtitle,
}) {
    const items = (lines ?? []).filter(Boolean);

    return (
        <div className="rounded-2xl border border-cyan-400/25 bg-gradient-to-b from-theme-glass to-theme-surface-soft p-5 shadow-[inset_0_1px_0_var(--border-main)] backdrop-blur-xl dark:from-slate-950/80 dark:to-slate-900/70 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]">
            <div className="mb-4 flex items-start gap-3">
                <span className="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-500/15 text-lg text-cyan-600 shadow-[0_0_20px_rgba(34,211,238,0.25)] dark:text-cyan-300">
                    ✦
                </span>
                <div>
                    <h3 className="text-sm font-semibold uppercase tracking-widest text-cyan-800 dark:text-cyan-100/90">
                        {title}
                    </h3>
                    {subtitle ? (
                        <p className="mt-1 text-xs text-theme-dim">{subtitle}</p>
                    ) : (
                        <p className="mt-1 text-xs text-theme-dim">
                            Synthesised from stored analytics, twins, and prediction
                            outputs — educational use only.
                        </p>
                    )}
                </div>
            </div>
            {items.length === 0 ? (
                <p className="text-sm text-theme-dim">
                    Run intelligence workflows in courses to surface department-level
                    guidance here.
                </p>
            ) : (
                <ul className="space-y-3">
                    {items.map((line, i) => (
                        <li
                            key={i}
                            className="flex gap-3 rounded-xl border border-theme-line bg-theme-highlight px-3 py-2.5 text-sm leading-snug text-theme-muted/95"
                        >
                            <span className="shrink-0 font-mono text-[0.65rem] text-cyan-600 dark:text-cyan-400/80">
                                {String(i + 1).padStart(2, '0')}
                            </span>
                            <span>{line}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
