/**
 * Consistent section titles across dashboards (Space Grotesk via `font-display`).
 */
export default function SectionHeader({
    title,
    description,
    action,
    className = '',
}) {
    return (
        <div
            className={`mb-6 flex flex-col gap-3 border-b border-theme-line pb-5 sm:flex-row sm:items-end sm:justify-between ${className}`.trim()}
        >
            <div className="min-w-0">
                <h2 className="font-display text-lg font-semibold tracking-tight text-theme-strong sm:text-xl">
                    {title}
                </h2>
                {description ? (
                    <p className="mt-1 max-w-2xl text-sm leading-relaxed text-theme-muted">
                        {description}
                    </p>
                ) : null}
            </div>
            {action ? <div className="shrink-0">{action}</div> : null}
        </div>
    );
}
