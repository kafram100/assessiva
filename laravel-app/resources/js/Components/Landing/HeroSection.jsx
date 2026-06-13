import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState } from 'react';

const fadeUp = {
    hidden: { opacity: 0, y: 28 },
    show: (i) => ({
        opacity: 1,
        y: 0,
        transition: { delay: 0.08 * i, duration: 0.65, ease: [0.22, 1, 0.36, 1] },
    }),
};

export default function HeroSection() {
    const [imgError, setImgError] = useState(false);

    return (
        <section className="relative z-10 px-4 pb-24 pt-28 sm:px-6 lg:px-8 lg:pb-32 lg:pt-32">
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-8">
                <div>
                    <motion.div
                        custom={0}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="mb-6 inline-flex items-center gap-2 rounded-full border border-ai-accent/25 bg-theme-highlight px-3 py-1 text-[0.7rem] font-medium uppercase tracking-[0.18em] text-ai-accent backdrop-blur-md"
                    >
                        <span className="relative flex h-2 w-2">
                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-ai-accent opacity-60" />
                            <span className="relative inline-flex h-2 w-2 rounded-full bg-ai-accent" />
                        </span>
                        Live intelligence layer
                    </motion.div>

                    <motion.h1
                        custom={1}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="font-display text-4xl font-bold leading-[1.08] tracking-tight text-theme-strong sm:text-5xl lg:text-[3.25rem] xl:text-6xl"
                    >
                        <span className="ai-text-gradient">Novagrade.ai</span>
                        {' '}for Future Universities
                    </motion.h1>

                    <motion.p
                        custom={2}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="mt-6 max-w-xl text-base leading-relaxed text-theme-muted sm:text-lg"
                    >
                        Transforming university education with AI powered assessment, predictive analytics,
                        personalized learning, and intelligent academic insights.
                    </motion.p>

                    <motion.div
                        custom={3}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="mt-10 flex flex-wrap items-center gap-4"
                    >
                        <Link
                            href={route('register')}
                            className="group relative inline-flex items-center justify-center overflow-hidden rounded-full px-8 py-3.5 text-sm font-semibold text-white no-underline"
                        >
                            <span className="absolute inset-0 bg-gradient-to-r from-ai-accent via-ai-accent-deep to-ai-accent opacity-90 transition group-hover:opacity-100" />
                            <span className="absolute inset-0 bg-ai-glow opacity-0 blur-xl transition group-hover:opacity-40" />
                            <span className="relative">Get Started</span>
                        </Link>
                        <Link
                            href={route('practice-quizzes.index')}
                            className="inline-flex items-center justify-center rounded-full border border-theme-line bg-theme-highlight px-8 py-3.5 text-sm font-semibold text-theme-muted no-underline backdrop-blur-md transition hover:border-ai-accent/50 hover:text-theme-strong"
                        >
                            Explore Practice Quizzes
                        </Link>
                    </motion.div>

                    <motion.dl
                        custom={4}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="mt-14 grid grid-cols-2 gap-6 sm:grid-cols-3"
                    >
                        {[
                            ['Predictive', 'Risk & success signals'],
                            ['Personal', 'Learning Twin & revision'],
                            ['Trusted', 'Fairness & integrity'],
                        ].map(([t, d]) => (
                            <div
                                key={t}
                                className="rounded-2xl border border-theme-line bg-theme-glass p-4 backdrop-blur-sm"
                            >
                                <dt className="font-display text-sm font-semibold text-theme-strong">{t}</dt>
                                <dd className="mt-1 text-xs text-theme-dim">{d}</dd>
                            </div>
                        ))}
                    </motion.dl>
                </div>

                <motion.div
                    initial={{ opacity: 0, scale: 0.94, x: 24 }}
                    animate={{ opacity: 1, scale: 1, x: 0 }}
                    transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1] }}
                    className="relative flex justify-center lg:justify-end"
                >
                    <div className="pointer-events-none absolute inset-0 flex justify-center lg:justify-end">
                        <div className="h-[min(90vw,520px)] w-[min(90vw,520px)] rounded-full bg-gradient-to-tr from-ai-accent/25 via-transparent to-ai-glow/20 blur-3xl lg:h-[560px] lg:w-[560px]" />
                    </div>

                    <div className="relative w-full max-w-md lg:max-w-lg">
                        <div className="absolute -inset-6 rounded-[2rem] bg-gradient-to-b from-ai-glow/20 via-ai-accent/10 to-transparent opacity-80 blur-2xl" />
                        <div className="absolute -inset-1 rounded-[1.75rem] bg-gradient-to-br from-white/20 via-ai-accent/30 to-transparent opacity-40" />

                        <motion.div
                            className="relative overflow-hidden rounded-[1.65rem] border border-theme-line bg-gradient-to-b from-theme-surface-soft to-theme-canvas p-1 shadow-theme-card"
                            animate={{ y: [0, -10, 0] }}
                            transition={{ duration: 6, repeat: Infinity, ease: 'easeInOut' }}
                        >
                            <div className="relative overflow-hidden rounded-[1.4rem] bg-theme-elevated">
                                {imgError ? (
                                    <div className="flex aspect-[3/4] max-h-[560px] min-h-[320px] w-full items-center justify-center bg-gradient-to-b from-theme-surface-soft to-theme-main">
                                        <p className="max-w-xs px-6 text-center text-sm text-theme-dim">
                                            Add your portrait at{' '}
                                            <code className="text-ai-accent">public/images/hero-ai-student.png</code>
                                        </p>
                                    </div>
                                ) : (
                                    <img
                                        src="/images/hero-ai-student.png"
                                        alt="AI enhanced learning"
                                        onError={() => setImgError(true)}
                                        className="relative z-10 mx-auto h-auto w-full max-w-md object-contain object-top drop-shadow-[0_0_48px_rgba(0,144,255,0.18)]"
                                    />
                                )}
                                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-[color:var(--bg-main)] via-[color:var(--bg-main)]/40 to-transparent" />
                            </div>

                            <motion.div
                                className="absolute -right-4 top-1/4 hidden rounded-xl border border-theme-line bg-theme-glass-2 px-3 py-2 text-[0.65rem] font-medium text-ai-accent shadow-lg backdrop-blur-md sm:block"
                                animate={{ y: [0, -6, 0] }}
                                transition={{ duration: 5, repeat: Infinity, ease: 'easeInOut', delay: 0.5 }}
                            >
                                Academic health · 94
                            </motion.div>
                            <motion.div
                                className="absolute -left-2 bottom-1/4 hidden rounded-xl border border-theme-line bg-theme-glass-2 px-3 py-2 text-[0.65rem] font-medium text-theme-muted shadow-lg backdrop-blur-md sm:block"
                                animate={{ y: [0, 8, 0] }}
                                transition={{ duration: 5.5, repeat: Infinity, ease: 'easeInOut' }}
                            >
                                Twin synced
                            </motion.div>
                        </motion.div>
                    </div>
                </motion.div>
            </div>
        </section>
    );
}
