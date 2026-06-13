import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';

const mockQuizzes = [
    { topic: 'Database normalization', score: 'Weak signal', tag: 'AI pick' },
    { topic: 'Distributed systems', score: 'Trending', tag: 'Hub' },
    { topic: 'Linear algebra drills', score: 'Revision', tag: 'Twin' },
];

export default function PracticeHubTeaserSection() {
    return (
        <section className="relative z-10 border-t border-theme-line px-4 py-24 sm:px-6 lg:px-8">
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-[color:var(--mesh-bg)] to-theme-canvas" />
            <div className="relative mx-auto max-w-7xl">
                <div className="grid items-center gap-12 lg:grid-cols-2">
                    <motion.div
                        initial={{ opacity: 0, x: -24 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true, margin: '-80px' }}
                        transition={{ duration: 0.65 }}
                    >
                        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-ai-glow/90">
                            AI Practice &amp; Mastery Hub
                        </p>
                        <h2 className="mt-3 font-display text-3xl font-bold text-theme-strong sm:text-4xl">
                            Self paced loops that sharpen your learners without adding stress
                        </h2>
                        <ul className="mt-8 space-y-4 text-sm text-theme-muted">
                            <li className="flex gap-3">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-ai-accent shadow-[0_0_10px_rgba(0,163,255,0.8)]" />
                                AI ranked quizzes and weak topic targeting without exam penalties.
                            </li>
                            <li className="flex gap-3">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-ai-glow shadow-[0_0_10px_rgba(94,235,255,0.7)]" />
                                Personalized revision nudges powered by attempt level intelligence.
                            </li>
                            <li className="flex gap-3">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-ai-accent-deep shadow-[0_0_10px_rgba(0,144,255,0.7)]" />
                                Practice data feeds the twin while official exams stay clean and proctored.
                            </li>
                        </ul>
                        <Link
                            href={route('practice-quizzes.index')}
                            className="mt-10 inline-flex rounded-full border border-ai-accent/40 bg-ai-accent/10 px-6 py-3 text-sm font-semibold text-ai-glow no-underline backdrop-blur-md transition hover:bg-ai-accent/20"
                        >
                            Open the Practice Hub →
                        </Link>
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, x: 24 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true, margin: '-80px' }}
                        transition={{ duration: 0.65, delay: 0.1 }}
                        className="space-y-4"
                    >
                        {mockQuizzes.map((q, i) => (
                            <motion.div
                                key={q.topic}
                                whileHover={{ scale: 1.02, x: 4 }}
                                transition={{ type: 'spring', stiffness: 400, damping: 25 }}
                                className="flex items-center justify-between gap-4 rounded-2xl border border-theme-line bg-theme-glass px-5 py-4 shadow-theme-card backdrop-blur-xl"
                            >
                                <div>
                                    <div className="font-medium text-theme-strong">{q.topic}</div>
                                    <div className="mt-1 text-xs text-theme-dim">{q.score}</div>
                                </div>
                                <span className="shrink-0 rounded-full border border-ai-glow/30 bg-ai-glow/10 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-wider text-ai-glow">
                                    {q.tag}
                                </span>
                            </motion.div>
                        ))}
                    </motion.div>
                </div>
            </div>
        </section>
    );
}
