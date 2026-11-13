import { Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState } from 'react';
import { 
    SparklesIcon, 
    ArrowRightIcon, 
    BoltIcon,
    AcademicCapIcon,
    CheckCircleIcon 
} from '@heroicons/react/24/outline';

const fadeUp = {
    hidden: { opacity: 0, y: 24 },
    show: (i) => ({
        opacity: 1,
        y: 0,
        transition: { delay: 0.08 * i, duration: 0.7, ease: [0.16, 1, 0.3, 1] },
    }),
};

export default function HeroSection() {
    const { auth } = usePage().props;
    const [imgError, setImgError] = useState(false);

    return (
        <section className="relative overflow-hidden px-4 pt-28 pb-16 sm:px-6 sm:pt-32 sm:pb-20 lg:px-8 lg:pt-36 lg:pb-24">
            {/* Background ambient lighting */}
            <div className="pointer-events-none absolute -top-40 right-0 -z-10 h-96 w-96 rounded-full bg-gradient-to-br from-indigo-500/15 via-purple-500/10 to-transparent blur-3xl dark:from-indigo-500/10" />
            <div className="pointer-events-none absolute top-1/2 left-0 -z-10 h-80 w-80 -translate-y-1/2 rounded-full bg-gradient-to-tr from-sky-500/15 via-indigo-500/10 to-transparent blur-3xl dark:from-sky-500/10" />

            <div className="mx-auto max-w-7xl">
                <div className="grid items-center gap-12 lg:grid-cols-12 lg:gap-8 xl:gap-12">
                    
                    {/* Left Column: Headline, Description & CTAs */}
                    <div className="flex flex-col items-center text-center lg:col-span-7 lg:items-start lg:text-left">
                        
                        {/* Eyebrow Pill */}
                        <motion.div
                            custom={1}
                            variants={fadeUp}
                            initial="hidden"
                            animate="show"
                            className="inline-flex items-center gap-2 rounded-full border border-indigo-200/80 bg-indigo-50/80 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm backdrop-blur dark:border-indigo-500/30 dark:bg-indigo-950/40 dark:text-indigo-300"
                        >
                            <SparklesIcon className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <span>AI-Powered Question Bank & Exam Prep</span>
                        </motion.div>

                        {/* Main Title */}
                        <motion.h1
                            custom={2}
                            variants={fadeUp}
                            initial="hidden"
                            animate="show"
                            className="mt-6 font-display text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl xl:text-7xl dark:text-white"
                        >
                            Stop Guessing.{' '}
                            <span className="block bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 bg-clip-text text-transparent dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400">
                                Start Acing.
                            </span>
                        </motion.h1>

                        {/* Subtitle */}
                        <motion.p
                            custom={3}
                            variants={fadeUp}
                            initial="hidden"
                            animate="show"
                            className="mt-6 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-300"
                        >
                            Never walk into an exam unprepared again. Assessiva dynamically serves real questions from an extensive question bank, pinpoints your knowledge gaps, and trains you with step-by-step AI solutions.
                        </motion.p>

                        {/* CTA Buttons */}
                        <motion.div
                            custom={4}
                            variants={fadeUp}
                            initial="hidden"
                            animate="show"
                            className="mt-8 flex flex-wrap items-center justify-center gap-3 sm:gap-4 lg:justify-start"
                        >
                            <Link
                                href={auth.user ? route('practice-quizzes.index') : route('register')}
                                className="group inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-indigo-700 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition-all hover:from-indigo-500 hover:to-indigo-600 hover:scale-105 active:scale-95"
                            >
                                <span>{auth.user ? 'Go to Practice Quizzes' : 'Start Practicing Free'}</span>
                                <ArrowRightIcon className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Link>

                            <a
                                href="#how-it-works"
                                className="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 px-6 py-3.5 text-sm font-medium text-slate-700 backdrop-blur transition-all hover:bg-slate-50 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                            >
                                See How It Works
                            </a>
                        </motion.div>

                        {/* Value Metric Badges */}
                        <motion.div
                            custom={5}
                            variants={fadeUp}
                            initial="hidden"
                            animate="show"
                            className="mt-10 grid w-full max-w-lg grid-cols-3 gap-3 border-t border-slate-100 pt-6 text-left dark:border-slate-800/80"
                        >
                            <div className="flex flex-col">
                                <span className="font-display text-lg font-bold text-slate-900 sm:text-xl dark:text-white">
                                    20 Free
                                </span>
                                <span className="text-xs text-slate-500 dark:text-slate-400">Questions on sign up</span>
                            </div>
                            <div className="flex flex-col">
                                <span className="font-display text-lg font-bold text-slate-900 sm:text-xl dark:text-white">
                                    100%
                                </span>
                                <span className="text-xs text-slate-500 dark:text-slate-400">Step-by-step solutions</span>
                            </div>
                            <div className="flex flex-col">
                                <span className="font-display text-lg font-bold text-slate-900 sm:text-xl dark:text-white">
                                    All Formats
                                </span>
                                <span className="text-xs text-slate-500 dark:text-slate-400">MCQ, Blanks & Theory</span>
                            </div>
                        </motion.div>
                    </div>

                    {/* Right Column: Compact, Sleek Image Showcase Card */}
                    <motion.div
                        custom={3}
                        variants={fadeUp}
                        initial="hidden"
                        animate="show"
                        className="relative mx-auto w-full max-w-md sm:max-w-lg lg:col-span-5 lg:max-w-none"
                    >
                        {/* Glow halo behind the card */}
                        <div className="absolute -inset-2 rounded-3xl bg-gradient-to-r from-indigo-500/20 via-purple-500/20 to-sky-500/20 blur-xl opacity-75 dark:opacity-50" />

                        {/* Main visual frame */}
                        <div className="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-[#18273C] shadow-2xl shadow-indigo-950/25 dark:border-slate-800/80">
                            
                            {/* Window header decoration */}
                            <div className="flex items-center justify-between border-b border-slate-700/50 bg-[#142032] px-4 py-2.5">
                                <div className="flex items-center gap-1.5">
                                    <div className="h-2.5 w-2.5 rounded-full bg-red-400/80" />
                                    <div className="h-2.5 w-2.5 rounded-full bg-amber-400/80" />
                                    <div className="h-2.5 w-2.5 rounded-full bg-emerald-400/80" />
                                </div>
                                <div className="flex items-center gap-1.5 text-[11px] font-medium text-slate-300">
                                    <BoltIcon className="h-3 w-3 text-indigo-400" />
                                    <span>Assessiva Intelligence Engine</span>
                                </div>
                                <div className="w-8" />
                            </div>

                            {/* Image container with 1:1 square ratio preserved */}
                            <div className="relative aspect-square w-full overflow-hidden bg-[#18273C]">
                                {imgError ? (
                                    <div className="flex h-full w-full items-center justify-center p-6 text-center">
                                        <p className="text-sm text-slate-400">
                                            Place portrait at <code className="text-indigo-400">public/images/hero-ai-student.png</code>
                                        </p>
                                    </div>
                                ) : (
                                    <img
                                        src="/images/hero-ai-student.png"
                                        alt="AI student exam preparation"
                                        onError={() => setImgError(true)}
                                        className="h-full w-full object-cover object-top transition-transform duration-500 hover:scale-105"
                                    />
                                )}

                                {/* Subtle gradient overlay at bottom of card */}
                                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-[#18273C] via-[#18273C]/40 to-transparent" />
                            </div>

                            {/* Bottom Card Bar */}
                            <div className="flex items-center justify-between border-t border-slate-700/50 bg-[#142032] px-4 py-3">
                                <div className="flex items-center gap-2">
                                    <div className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                        <CheckCircleIcon className="h-4 w-4" />
                                    </div>
                                    <span className="text-xs font-semibold text-slate-200">
                                        Adaptive Question Shuffling
                                    </span>
                                </div>
                                <span className="rounded-full bg-indigo-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-300">
                                    Active
                                </span>
                            </div>
                        </div>

                        {/* Floating Micro-Badge Top Right */}
                        <div className="absolute -top-3 -right-3 hidden sm:flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white/95 px-3 py-1.5 shadow-lg backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
                            <span className="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                            <span className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                Neural Exam Bank
                            </span>
                        </div>

                        {/* Floating Micro-Badge Bottom Left */}
                        <div className="absolute -bottom-3 -left-3 hidden sm:flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white/95 px-3.5 py-2 shadow-lg backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
                            <AcademicCapIcon className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <div className="flex flex-col text-left">
                                <span className="text-[11px] font-bold text-slate-900 dark:text-white">Exam Mastery</span>
                                <span className="text-[9px] text-emerald-600 dark:text-emerald-400 font-medium">+35% average score boost</span>
                            </div>
                        </div>

                    </motion.div>
                </div>
            </div>
        </section>
    );
}
