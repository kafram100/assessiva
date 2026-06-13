/** Horizontal risk / score meter (0–100). */
export default function RiskMeter({ value = 0, label = 'Signal' }) {
    const v = Math.max(0, Math.min(100, Number(value) || 0));
    return (
        <div className="space-y-1">
            <div className="flex justify-between text-xs text-theme-dim">
                <span>{label}</span>
                <span className="font-mono text-theme-muted">{v}</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-theme-meter-track">
                <div
                    className="h-full rounded-full bg-gradient-to-r from-sky-500 via-violet-500 to-fuchsia-500 transition-all duration-500"
                    style={{ width: `${v}%` }}
                />
            </div>
        </div>
    );
}
