export default function RevisionNoteCard({ topic, note }) {
    return (
        <div className="rounded-xl border border-theme-line bg-theme-glass p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-sky-400">
                {topic || 'Topic'}
            </p>
            <p className="mt-2 text-sm leading-relaxed text-theme-muted">{note}</p>
        </div>
    );
}
