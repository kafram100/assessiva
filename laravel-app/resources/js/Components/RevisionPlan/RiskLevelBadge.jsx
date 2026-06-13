const styles = {
    low: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-800 dark:text-emerald-200',
    medium: 'border-amber-500/50 bg-amber-500/15 text-amber-900 dark:text-amber-100',
    high: 'border-rose-500/50 bg-rose-500/15 text-rose-800 dark:text-rose-100',
};

export default function RiskLevelBadge({ level }) {
    const key = (level || 'medium').toLowerCase();
    const cls = styles[key] || styles.medium;
    return (
        <span
            className={`inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide ${cls}`}
        >
            Risk: {key}
        </span>
    );
}
