import { Link, usePage } from '@inertiajs/react';
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
    const { auth } = usePage().props;
    const [imgError, setImgError] = useState(false);

    return (
        <section className="relative z-10 px-4 pb-24 pt-28 sm:px-6 lg:px-8 lg:pb-32 lg:pt-32">
            <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-8">
                <div className="text-center lg:text-left">
                    <motion.h1
                        custom={1}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="font-display text-3xl font-bold leading-[1.08] tracking-tight text-theme-strong sm:text-5xl lg:text-[3.25rem] xl:text-6xl"
                    >
                        Stop Guessing, <span className="ai-text-gradient">Start Acing</span>
                    </motion.h1>

                    <motion.p
                        custom={2}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="mx-auto mt-6 max-w-xl text-base leading-relaxed text-theme-muted sm:text-lg lg:mx-0"
                    >
                        Never walk into an exam unprepared again. NovaGrade analyzes your knowledge, tests you with real questions, and explains exactly how to improve.
                    </motion.p>

                    <motion.div
                        custom={3}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="mt-10 flex flex-wrap items-center justify-center gap-4 lg:justify-start"
                    >
                        <Link
                            href={auth.user ? route('practice-quizzes.index') : route('register')}
                            className="group relative inline-flex items-center justify-center overflow-hidden rounded-full px-4 sm:px-8 py-3.5 text-sm font-semibold text-white no-underline"
                        >
                            <span className="absolute inset-0 bg-gradient-to-r from-ai-accent via-ai-accent-deep to-ai-accent opacity-90 transition group-hover:opacity-100" />
                            <span className="absolute inset-0 bg-ai-glow opacity-0 blur-xl transition group-hover:opacity-40" />
                            <span className="relative">Start Practicing</span>
                        </Link>
                    </motion.div>
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
                        {/* Floating Node 1 */}
                        <motion.div
                            className="absolute -right-6 top-1/4 z-30 flex h-12 w-12 items-center justify-center rounded-full border border-ai-accent/30 bg-theme-elevated/80 shadow-[0_0_24px_rgba(0,163,255,0.4)] backdrop-blur-md"
                            animate={{ y: [0, -20, 0], x: [0, 10, 0] }}
                            transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }}
                        >
                            <span className="h-2 w-2 rounded-full bg-ai-accent animate-pulse" />
                        </motion.div>

                        {/* Floating Node 2 */}
                        <motion.div
                            className="absolute -left-8 bottom-1/3 z-30 flex h-16 w-16 items-center justify-center rounded-full border border-purple-500/30 bg-theme-elevated/80 shadow-[0_0_24px_rgba(168,85,247,0.4)] backdrop-blur-md"
                            animate={{ y: [0, 25, 0], x: [0, -15, 0] }}
                            transition={{ duration: 6, repeat: Infinity, ease: 'easeInOut' }}
                        >
                            <span className="h-3 w-3 rounded-full bg-purple-500 animate-pulse" />
                        </motion.div>

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
                                    <>
                                        <img
                                            src="/images/hero-ai-student.png"
                                            alt="AI enhanced learning"
                                            onError={() => setImgError(true)}
                                            className="relative z-10 mx-auto h-full w-full object-cover aspect-[3/4] drop-shadow-[0_0_48px_rgba(0,144,255,0.18)]"
                                        />
                                    </>
                                )}
                                <div className="pointer-events-none absolute inset-x-0 bottom-0 z-20 h-1/3 bg-gradient-to-t from-[color:var(--bg-main)] via-[color:var(--bg-main)]/40 to-transparent" />
                            </div>
                        </motion.div>
                    </div>
                </motion.div>
            </div>
        </section>
    );
}
