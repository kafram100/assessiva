import { Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState } from 'react';

const fadeUp = {
    hidden: { opacity: 0, y: 30 },
    show: (i) => ({
        opacity: 1,
        y: 0,
        transition: { delay: 0.1 * i, duration: 0.8, ease: [0.16, 1, 0.3, 1] },
    }),
};

export default function HeroSection() {
    const { auth } = usePage().props;
    const [imgError, setImgError] = useState(false);

    return (
        <section className="relative z-10 px-4 pb-24 pt-32 sm:px-6 lg:px-8 lg:pb-40 lg:pt-48">
            <div className="mx-auto flex max-w-7xl flex-col items-center text-center">
                <motion.h1
                    custom={1}
                    variants={fadeUp}
                    initial="hidden"
                    animate="show"
                    className="max-w-5xl font-display text-5xl font-bold tracking-tighter text-slate-900 sm:text-7xl lg:text-8xl dark:text-white"
                >
                    Stop Guessing. <br className="hidden sm:block" />
                    <span className="text-indigo-600 dark:text-indigo-400">Start Acing.</span>
                </motion.h1>

                <motion.p
                    custom={2}
                    variants={fadeUp}
                    initial="hidden"
                    animate="show"
                    className="mx-auto mt-8 max-w-2xl text-lg leading-relaxed text-slate-500 sm:text-xl dark:text-slate-400"
                >
                    Never walk into an exam unprepared again. Assessiva analyzes your knowledge, tests you with real questions, and explains exactly how to improve.
                </motion.p>

                <motion.div
                    custom={3}
                    variants={fadeUp}
                    initial="hidden"
                    animate="show"
                    className="mt-12 flex flex-wrap items-center justify-center gap-4"
                >
                    <Link
                        href={auth.user ? route('practice-quizzes.index') : route('register')}
                        className="inline-flex items-center justify-center rounded-full bg-indigo-600 px-8 py-4 text-sm font-semibold text-white transition-all hover:bg-indigo-700 hover:scale-105 active:scale-95 shadow-lg shadow-indigo-600/20"
                    >
                        Start Practicing
                    </Link>
                </motion.div>

                <motion.div
                    custom={4}
                    variants={fadeUp}
                    initial="hidden"
                    animate="show"
                    className="mt-24 w-full max-w-5xl"
                >
                    <div className="mx-auto relative overflow-hidden rounded-3xl border border-slate-100 bg-slate-50 shadow-2xl shadow-slate-200/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        {imgError ? (
                            <div className="flex aspect-video w-full items-center justify-center">
                                <p className="text-sm text-slate-400">
                                    Add your portrait at <code className="text-indigo-500">public/images/hero-ai-student.png</code>
                                </p>
                            </div>
                        ) : (
                            <img
                                src="/images/hero-ai-student.png"
                                alt="AI enhanced learning"
                                onError={() => setImgError(true)}
                                className="w-full object-cover max-h-[600px] object-top"
                            />
                        )}
                    </div>
                </motion.div>
            </div>
        </section>
    );
}
