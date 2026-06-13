export default function InterventionTimeline({ items = [] }) {
    if (!items.length) {
        return (
            <p className="text-sm text-theme-dim">No interventions listed.</p>
        );
    }
    return (
        <ol className="relative border-l border-theme-line pl-6">
            {items.map((text, i) => (
                <li key={i} className="mb-6 last:mb-0">
                    <span className="absolute -left-[7px] mt-1.5 h-3 w-3 rounded-full bg-violet-500 shadow-[0_0_12px_rgba(139,92,246,0.8)]" />
                    <p className="text-sm text-theme-muted">{text}</p>
                </li>
            ))}
        </ol>
    );
}
