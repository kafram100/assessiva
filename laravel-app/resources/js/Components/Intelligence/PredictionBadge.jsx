const tone = {
    low: 'border-emerald-500/50 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200',
    medium: 'border-amber-500/50 bg-amber-500/10 text-amber-900 dark:text-amber-100',
    high: 'border-rose-500/50 bg-rose-500/10 text-rose-800 dark:text-rose-100',
};

export default function PredictionBadge({ label, value }) {
    const key = (value || 'medium').toLowerCase();
    const cls = tone[key] || tone.medium;
    return (
        <span
            className={`inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide ${cls}`}
        >
            {label}: {key}
        </span>
    );
}
