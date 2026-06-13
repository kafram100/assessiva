function heat(score) {
    if (score == null) {
        return 'border-theme-line bg-gradient-to-br from-theme-highlight to-theme-glass';
    }
    if (score >= 75) {
        return 'border-emerald-400/35 bg-gradient-to-br from-emerald-100/90 to-emerald-200/50 dark:from-emerald-500/25 dark:to-emerald-900/40 dark:border-emerald-400/30';
    }
    if (score >= 55) {
        return 'border-cyan-400/30 bg-gradient-to-br from-cyan-50/90 to-sky-100/60 dark:from-cyan-500/20 dark:to-slate-900/50 dark:border-cyan-400/25';
    }
    return 'border-amber-400/35 bg-gradient-to-br from-amber-100/90 to-rose-100/70 dark:from-amber-500/25 dark:to-rose-950/50 dark:border-amber-400/35';
}

export default function RiskHeatmap({ rows = [], title, valueLabel = 'Health' }) {
    return (
        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
            <h3 className="text-sm font-semibold uppercase tracking-widest text-theme-muted/90">
                {title ?? 'Institutional risk heatmap'}
            </h3>
            <p className="mt-1 text-xs text-theme-dim">
                Colour reflects blended academic health score where available — for
                supportive prioritisation, not punitive comparison.
            </p>
            <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {(rows ?? []).map((r, i) => (
                    <div
                        key={r.department_id ?? `${r.department}-${i}`}
                        className={`rounded-xl border bg-gradient-to-br p-3 shadow-inner ${heat(r.score)}`}
                    >
                        <div className="text-[0.65rem] font-semibold uppercase tracking-wide text-theme-dim/90">
                            {r.school_name ?? 'School'}
                        </div>
                        <div className="mt-1 truncate text-sm font-semibold text-theme-strong">
                            {r.department ?? 'Department'}
                        </div>
                        <div className="mt-3 flex items-end justify-between gap-2">
                            <span className="text-[0.65rem] uppercase tracking-wider text-theme-dim">
                                {valueLabel}
                            </span>
                            <span className="text-2xl font-bold tabular-nums text-theme-strong">
                                {r.score == null ? '—' : r.score}
                            </span>
                        </div>
                        {r.label ? (
                            <div className="mt-1 text-[0.7rem] text-theme-muted/90">
                                {r.label}
                            </div>
                        ) : null}
                    </div>
                ))}
            </div>
        </div>
    );
}
