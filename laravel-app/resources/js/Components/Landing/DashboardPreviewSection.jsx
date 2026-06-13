import { motion } from 'framer-motion';

function Bar({ delay, h, color }) {
    const px = Math.round((h / 100) * 140);
    return (
        <motion.div
            className={`w-full max-w-full rounded-sm ${color}`}
            initial={{ height: 4 }}
            whileInView={{ height: px }}
            viewport={{ once: true }}
            transition={{ duration: 0.9, delay, ease: [0.22, 1, 0.36, 1] }}
        />
    );
}

function MiniCard({ title, children, accent }) {
    return (
        <div
            className={`rounded-xl border border-theme-line bg-theme-glass p-4 backdrop-blur-md ${accent}`}
        >
            <div className="text-[0.65rem] font-semibold uppercase tracking-wider text-theme-dim">{title}</div>
            <div className="mt-3">{children}</div>
        </div>
    );
}

export default function DashboardPreviewSection() {
    const bars = [45, 72, 38, 88, 55, 92, 48, 76, 64, 82, 58, 95];
    const colors = [
        'bg-ai-accent/80',
        'bg-ai-glow-soft/70',
        'bg-ai-accent-deep/80',
        'bg-ai-glow/60',
        'bg-ai-accent/60',
        'bg-cyan-300/50',
    ];

    return (
        <section className="relative z-10 overflow-hidden border-t border-theme-line px-4 py-24 sm:px-6 lg:px-8">
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-[color:var(--mesh-bg)] via-theme-canvas to-[color:var(--mesh-bg)]" />

            <div className="relative mx-auto max-w-7xl">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-80px' }}
                    transition={{ duration: 0.6 }}
                    className="flex flex-col justify-between gap-6 lg:flex-row lg:items-end"
                >
                    <div className="max-w-xl">
                        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-ai-glow/90">Live intelligence</p>
                        <h2 className="mt-3 font-display text-3xl font-bold tracking-tight text-theme-strong sm:text-4xl">
                            Command center clarity for academic leaders
                        </h2>
                        <p className="mt-4 text-theme-muted">
                            Health scores, risk bands, fairness signals, and engagement, all synthesized for decisions
                            that move faster than spreadsheets ever could.
                        </p>
                    </div>
                    <div className="flex gap-3 text-xs text-theme-dim">
                        <span className="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-emerald-300/90">
                            Low risk cohort
                        </span>
                        <span className="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-amber-200/90">
                            Review flagged items
                        </span>
                    </div>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 32 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-60px' }}
                    transition={{ duration: 0.75, delay: 0.1 }}
                    className="mt-14 rounded-3xl border border-theme-line bg-theme-glass p-4 shadow-theme-card backdrop-blur-2xl sm:p-6 lg:p-8"
                >
                    <div className="grid gap-4 lg:grid-cols-12">
                        <div className="lg:col-span-4">
                            <MiniCard title="Academic health" accent="ring-1 ring-ai-accent/20">
                                <div className="flex items-end justify-between gap-4">
                                    <motion.div
                                        className="font-display text-5xl font-bold tabular-nums text-theme-strong"
                                        initial={{ opacity: 0 }}
                                        whileInView={{ opacity: 1 }}
                                        viewport={{ once: true }}
                                        transition={{ duration: 0.8, delay: 0.2 }}
                                    >
                                        87
                                    </motion.div>
                                    <div className="text-right text-xs text-theme-dim">
                                        Composite index
                                        <br />
                                        <span className="text-emerald-400/90">+4.2 vs last term</span>
                                    </div>
                                </div>
                                <div className="mt-4 h-2 overflow-hidden rounded-full bg-theme-highlight">
                                    <motion.div
                                        className="h-full rounded-full bg-gradient-to-r from-ai-accent to-ai-glow"
                                        initial={{ width: 0 }}
                                        whileInView={{ width: '87%' }}
                                        viewport={{ once: true }}
                                        transition={{ duration: 1.1, ease: [0.22, 1, 0.36, 1] }}
                                    />
                                </div>
                            </MiniCard>
                            <div className="mt-4 grid grid-cols-2 gap-3">
                                <MiniCard title="At risk" accent="">
                                    <div className="font-display text-2xl font-bold text-amber-200/90">12%</div>
                                    <div className="text-[0.65rem] text-theme-dim">Early signal</div>
                                </MiniCard>
                                <MiniCard title="Engagement" accent="">
                                    <div className="font-display text-2xl font-bold text-ai-glow">0.84</div>
                                    <div className="text-[0.65rem] text-theme-dim">Twin weighted</div>
                                </MiniCard>
                            </div>
                        </div>

                        <div className="lg:col-span-5">
                            <MiniCard title="Performance pulse" accent="min-h-[220px]">
                                <div className="flex h-40 items-end justify-between gap-1.5 px-1">
                                    {bars.map((h, i) => (
                                        <div key={i} className="flex h-full flex-1 flex-col justify-end">
                                            <Bar delay={0.03 * i} h={h} color={colors[i % colors.length]} />
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-3 flex justify-between text-[0.65rem] text-theme-dim">
                                    <span>Week 1</span>
                                    <span>Now</span>
                                </div>
                            </MiniCard>
                        </div>

                        <div className="lg:col-span-3 space-y-4">
                            <MiniCard title="Fairness scan" accent="">
                                <ul className="space-y-2 text-xs text-theme-muted">
                                    <li className="flex justify-between">
                                        <span>DIF flags</span>
                                        <span className="text-emerald-400">0 critical</span>
                                    </li>
                                    <li className="flex justify-between">
                                        <span>Item drift</span>
                                        <span className="text-amber-200/90">2 watch</span>
                                    </li>
                                    <li className="flex justify-between">
                                        <span>Cohort parity</span>
                                        <span className="text-ai-glow">Stable</span>
                                    </li>
                                </ul>
                            </MiniCard>
                            <MiniCard title="Student intelligence" accent="">
                                <div className="space-y-2 text-xs">
                                    <div className="flex items-center justify-between rounded-lg bg-theme-highlight px-2 py-1.5">
                                        <span className="text-theme-dim">Twin confidence</span>
                                        <span className="font-mono text-ai-glow">0.91</span>
                                    </div>
                                    <div className="flex items-center justify-between rounded-lg bg-theme-highlight px-2 py-1.5">
                                        <span className="text-theme-dim">Next best quiz</span>
                                        <span className="text-theme-strong">SQL joins</span>
                                    </div>
                                </div>
                            </MiniCard>
                        </div>
                    </div>
                </motion.div>
            </div>
        </section>
    );
}
