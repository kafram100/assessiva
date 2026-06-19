/**
 * Full-screen loading state for long-running AI requests (e.g. question generation).
 */
export default function AiGenerationLoadingOverlay({
    show,
    title = 'Generating with AI…',
    subtitle = 'This can take a minute or two. Please keep this tab open until we finish.',
}) {
    if (!show) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-[2000] flex items-center justify-center p-6"
            role="alertdialog"
            aria-modal="true"
            aria-busy="true"
            aria-labelledby="ai-generation-loading-title"
        >
            <div
                className="absolute inset-0 bg-[color:var(--modal-backdrop)] backdrop-blur-md"
                aria-hidden
            />
            <div className="ai-glass-card relative w-full max-w-md p-4 sm:p-8 text-center">
                <div
                    className="mx-auto mb-6 h-14 w-14 rounded-full border-2 border-theme-line border-t-ai-accent border-r-ai-glow-soft animate-spin"
                    aria-hidden
                />
                <h2
                    id="ai-generation-loading-title"
                    className="font-display text-lg font-semibold tracking-tight text-theme-strong"
                >
                    {title}
                </h2>
                <p className="mt-3 text-sm leading-relaxed text-theme-muted">
                    {subtitle}
                </p>
                <p className="mt-4 text-xs text-theme-dim">
                    Do not close or refresh this page while generation is in
                    progress.
                </p>
            </div>
        </div>
    );
}
