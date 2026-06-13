export default function PenaltySummaryCard({ summary, title = 'Proctoring penalties' }) {
    if (!summary) {
        return null;
    }
    const raw = Number(summary.raw_score ?? 0);
    const pen = Number(summary.total_penalty ?? 0);
    const adj = Number(summary.adjusted_score ?? 0);

    return (
        <div className="rounded-lg border border-theme-line bg-theme-glass p-4 shadow">
            <h3 className="text-sm font-semibold uppercase tracking-widest text-theme-dim">
                {title}
            </h3>
            <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                <div>
                    <dt className="text-theme-dim">Raw score</dt>
                    <dd className="font-mono font-semibold text-theme-strong">
                        {raw.toFixed(2)}
                    </dd>
                </div>
                <div>
                    <dt className="text-theme-dim">Total penalty</dt>
                    <dd className="font-mono font-semibold text-amber-700 dark:text-amber-400">
                        −{pen.toFixed(2)}
                    </dd>
                </div>
                <div>
                    <dt className="text-theme-dim">Adjusted score</dt>
                    <dd className="font-mono font-semibold text-emerald-700 dark:text-emerald-400">
                        {adj.toFixed(2)}
                    </dd>
                </div>
            </dl>
        </div>
    );
}
