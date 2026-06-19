import { useId } from 'react';

export default function AcademicHealthScoreCard({
    title = 'Academic health',
    score,
    label,
    hint,
}) {
    const gid = useId().replace(/:/g, '');
    const pct = score == null ? 0 : Math.min(100, Math.max(0, score));
    const circumference = 2 * Math.PI * 52;
    const dash = (pct / 100) * circumference;

    return (
        <div className="relative overflow-hidden rounded-2xl border border-cyan-500/20 bg-gradient-to-br from-theme-glass via-theme-surface-soft to-indigo-100/60 p-6 shadow-[0_0_40px_-12px_rgba(34,211,238,0.2)] backdrop-blur-xl dark:from-slate-950/90 dark:via-slate-900/80 dark:to-indigo-950/90 dark:shadow-[0_0_40px_-12px_rgba(34,211,238,0.35)]">
            <div className="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-cyan-500/10 blur-2xl" />
            <div className="pointer-events-none absolute -bottom-10 -left-10 h-36 w-36 rounded-full bg-violet-600/10 blur-3xl" />
            <div className="flex flex-wrap items-center gap-8">
                <div className="relative mx-auto shrink-0 sm:mx-0">
                    <svg
                        className="h-32 w-32 -rotate-90 text-theme-meter-track"
                        viewBox="0 0 120 120"
                        aria-hidden
                    >
                        <circle
                            cx="60"
                            cy="60"
                            r="52"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="8"
                        />
                        <circle
                            cx="60"
                            cy="60"
                            r="52"
                            fill="none"
                            stroke={`url(#healthGrad-${gid})`}
                            strokeWidth="8"
                            strokeDasharray={`${dash} ${circumference}`}
                            strokeLinecap="round"
                        />
                        <defs>
                            <linearGradient
                                id={`healthGrad-${gid}`}
                                x1="0%"
                                y1="0%"
                                x2="100%"
                                y2="0%"
                            >
                                <stop offset="0%" stopColor="#22d3ee" />
                                <stop offset="100%" stopColor="#a78bfa" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span className="text-3xl font-bold tabular-nums tracking-tight text-theme-strong">
                            {score == null ? '-' : score}
                        </span>
                        <span className="text-[0.65rem] font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-200/70">
                            index
                        </span>
                    </div>
                </div>
                <div className="min-w-0 flex-1 space-y-2">
                    <p className="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300/80">
                        {title}
                    </p>
                    <p className="text-lg font-semibold text-theme-strong">
                        {label ?? 'Awaiting signals'}
                    </p>
                    {hint ? (
                        <p className="text-sm leading-relaxed text-theme-muted/85">
                            {hint}
                        </p>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
