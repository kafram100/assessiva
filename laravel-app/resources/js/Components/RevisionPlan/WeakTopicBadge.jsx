export default function WeakTopicBadge({ topic }) {
    if (!topic) {
        return null;
    }
    return (
        <span className="inline-flex items-center rounded-full border border-sky-500/40 bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-800 dark:text-sky-200">
            {topic}
        </span>
    );
}
