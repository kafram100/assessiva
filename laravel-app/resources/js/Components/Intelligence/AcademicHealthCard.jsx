export default function AcademicHealthCard({ title, score, subtitle, accent = 'sky' }) {
    const ring =
        accent === 'emerald'
            ? 'from-emerald-500/30 to-cyan-500/20'
            : accent === 'amber'
              ? 'from-amber-500/30 to-orange-500/20'
              : 'from-sky-500/30 to-indigo-500/20';

    return (
        <div
            className={`relative overflow-hidden rounded-2xl border border-theme-line bg-gradient-to-br ${ring} p-5 shadow-lg`}
        >
            <div className="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/5 blur-2xl" />
            <p className="text-xs font-semibold uppercase tracking-widest text-theme-dim">
                {title}
            </p>
            <p className="mt-2 text-3xl font-bold tracking-tight text-theme-strong">
                {score ?? '-'}
            </p>
            {subtitle ? (
                <p className="mt-2 text-sm text-theme-muted">{subtitle}</p>
            ) : null}
        </div>
    );
}
