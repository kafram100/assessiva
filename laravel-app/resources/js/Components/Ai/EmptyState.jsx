import GlassCard from '@/Components/Ai/GlassCard';

export default function EmptyState({
    title,
    description,
    action,
    className = '',
}) {
    return (
        <GlassCard
            className={`flex flex-col items-center justify-center py-14 text-center ${className}`.trim()}
        >
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border border-theme-line bg-theme-highlight text-ai-accent">
                <svg
                    className="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    aria-hidden
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12V15z"
                    />
                </svg>
            </div>
            <h3 className="font-display text-base font-semibold text-theme-strong">
                {title}
            </h3>
            {description ? (
                <p className="mt-2 max-w-md text-sm text-theme-dim">{description}</p>
            ) : null}
            {action ? <div className="mt-6">{action}</div> : null}
        </GlassCard>
    );
}
