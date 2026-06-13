export default function CohortTrendChart({ items = [] }) {
    if (!items.length) {
        return (
            <p className="text-sm text-theme-dim">No trend data to chart.</p>
        );
    }
    const max = Math.max(...items.map((i) => Number(i.value) || 0), 1);
    return (
        <div className="space-y-3">
            {items.map((row, i) => {
                const v = Number(row.value) || 0;
                const w = Math.round((v / max) * 100);
                return (
                    <div key={i}>
                        <div className="mb-1 flex justify-between text-xs text-theme-dim">
                            <span>{row.label}</span>
                            <span className="font-mono text-theme-muted">{v}</span>
                        </div>
                        <div className="h-2 rounded-full bg-theme-surface-soft">
                            <div
                                className="h-2 rounded-full bg-gradient-to-r from-fuchsia-500 to-sky-400"
                                style={{ width: `${w}%` }}
                            />
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
