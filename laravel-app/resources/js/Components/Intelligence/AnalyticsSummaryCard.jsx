export default function AnalyticsSummaryCard({ title, children }) {
    return (
        <div className="rounded-2xl border border-theme-line bg-theme-elevated/80 p-5 shadow-inner backdrop-blur">
            <h3 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                {title}
            </h3>
            <div className="mt-3 text-sm leading-relaxed text-theme-muted">
                {children}
            </div>
        </div>
    );
}
