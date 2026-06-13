export default function StudyPlanTimeline({ days }) {
    if (!days?.length) {
        return (
            <p className="text-sm text-theme-dim">No study plan items yet.</p>
        );
    }
    return (
        <ol className="relative border-l border-theme-line pl-6">
            {days.map((d, i) => (
                <li key={`${d.day}-${i}`} className="mb-8 last:mb-0">
                    <span className="absolute -left-[9px] mt-1.5 h-3 w-3 rounded-full border-2 border-sky-500 bg-theme-elevated" />
                    <p className="text-xs font-semibold uppercase text-sky-400">
                        Day {d.day ?? i + 1}
                    </p>
                    <p className="text-base font-semibold text-theme-strong">{d.title}</p>
                    <ul className="mt-2 list-disc space-y-1 pl-4 text-sm text-theme-muted">
                        {(d.tasks || []).map((t, j) => (
                            <li key={j}>{t}</li>
                        ))}
                    </ul>
                </li>
            ))}
        </ol>
    );
}
