export default function CohortAnalyticsCard({ items = [], title }) {
    return (
        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
            <h3 className="text-sm font-semibold uppercase tracking-widest text-theme-muted/90">
                {title ?? 'Intelligence inventory'}
            </h3>
            <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                {(items ?? []).map((row) => (
                    <div
                        key={row.label}
                        className="rounded-xl border border-theme-line bg-theme-highlight px-4 py-3"
                    >
                        <dt className="text-[0.65rem] font-semibold uppercase tracking-wider text-theme-dim">
                            {row.label}
                        </dt>
                        <dd className="mt-1 text-2xl font-bold tabular-nums text-theme-strong">
                            {row.value}
                        </dd>
                        {row.hint ? (
                            <p className="mt-1 text-xs text-theme-dim">{row.hint}</p>
                        ) : null}
                    </div>
                ))}
            </dl>
        </div>
    );
}
