import { motion } from 'framer-motion';

const steps = [
    { n: '01', title: 'Create assessment', body: 'Author once with questions, timing, integrity rules, and cohorts.' },
    { n: '02', title: 'AI analyzes', body: 'Fairness, difficulty, and predictive signals compile in real time.' },
    { n: '03', title: 'Students learn', body: 'Practice hub and formal paths deliver feedback without mixing contexts.' },
    { n: '04', title: 'AI predicts', body: 'Risk, revision focus, and institutional trends surface early.' },
    { n: '05', title: 'Institutions improve', body: 'Close the loop with evidence backed policy and pedagogy.' },
];

export default function HowItWorksSection() {
    return (
        <section className="relative z-10 border-t border-theme-line bg-[color:var(--mesh-bg)] px-4 py-24 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-7xl">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-80px' }}
                    className="max-w-2xl"
                >
                    <p className="text-xs font-semibold uppercase tracking-[0.25em] text-ai-glow/90">How it works</p>
                    <h2 className="mt-3 font-display text-3xl font-bold text-theme-strong sm:text-4xl">From signal to strategy</h2>
                </motion.div>

                <div className="relative mt-16">
                    <div className="pointer-events-none absolute left-0 right-0 top-8 hidden h-px bg-gradient-to-r from-transparent via-ai-accent/40 to-transparent lg:block" />

                    <div className="grid gap-8 lg:grid-cols-5">
                        {steps.map((s, i) => (
                            <motion.div
                                key={s.n}
                                initial={{ opacity: 0, y: 24 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: '-40px' }}
                                transition={{ delay: 0.08 * i, duration: 0.55 }}
                                className="relative"
                            >
                                {i < steps.length - 1 ? (
                                    <div className="absolute left-1/2 top-10 hidden h-full w-px -translate-x-1/2 bg-gradient-to-b from-ai-accent/50 to-transparent lg:left-full lg:top-5 lg:h-px lg:w-full lg:translate-x-0 lg:bg-gradient-to-r" />
                                ) : null}
                                <div className="flex flex-col items-center text-center lg:block lg:text-left">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full border border-ai-accent/40 bg-ai-accent/15 font-mono text-xs font-bold text-ai-glow shadow-[0_0_24px_rgba(0,163,255,0.25)]">
                                        {s.n}
                                    </div>
                                    <h3 className="mt-4 font-display text-lg font-semibold text-theme-strong">{s.title}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-theme-dim">{s.body}</p>
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
