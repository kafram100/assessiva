export default function MetricCard({ label, value, hint, className = '' }) {
    return (
        <div
            className={`ai-glass-card ai-glass-card-hover p-5 ${className}`.trim()}
        >
            <div className="ai-metric-label">{label}</div>
            <div className="ai-metric-value mt-2">{value}</div>
            {hint ? (
                <p className="mt-2 text-xs leading-snug text-theme-dim">{hint}</p>
            ) : null}
        </div>
    );
}
