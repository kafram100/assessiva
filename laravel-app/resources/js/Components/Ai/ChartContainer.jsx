export default function ChartContainer({ title, subtitle, children, className = '' }) {
    return (
        <div className={`ai-glass-card p-5 ${className}`.trim()}>
            {(title || subtitle) && (
                <div className="mb-4">
                    {title ? (
                        <h3 className="font-display text-sm font-semibold tracking-tight text-theme-strong">
                            {title}
                        </h3>
                    ) : null}
                    {subtitle ? (
                        <p className="mt-1 text-xs text-theme-dim">{subtitle}</p>
                    ) : null}
                </div>
            )}
            {children}
        </div>
    );
}
