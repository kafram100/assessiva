import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';

export default function LandingHeader() {
    return (
        <motion.header
            initial={{ y: -16, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
            className="landing-header-bar fixed left-0 right-0 top-0 z-50"
        >
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" className="group flex items-center gap-2 no-underline">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-ai-accent to-ai-accent-deep shadow-[var(--shadow-glow)]">
                        <svg className="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" aria-hidden>
                            <path
                                d="M12 2L4 7v10l8 5 8-5V7l-8-5z"
                                stroke="currentColor"
                                strokeWidth="1.5"
                                strokeLinejoin="round"
                            />
                            <path d="M12 12l8-5M12 12v10M12 12L4 7" stroke="currentColor" strokeWidth="1.5" />
                        </svg>
                    </span>
                    <div className="leading-tight">
                        <div className="font-display text-sm font-semibold tracking-tight text-theme-strong">
                            NovaGrade.ai
                        </div>
                        <div className="text-[0.65rem] font-medium uppercase tracking-[0.2em] text-theme-dim">
                            For future universities
                        </div>
                    </div>
                </Link>
                <nav className="flex items-center gap-2 sm:gap-3">
                    <Link
                        href={route('practice-quizzes.index')}
                        className="hidden rounded-full border border-theme-line bg-theme-highlight px-3 py-1.5 text-xs font-medium text-theme-muted transition hover:border-ai-accent/40 hover:text-theme-strong sm:inline-block sm:text-sm"
                    >
                        Practice Hub
                    </Link>
                    <ThemeToggle />
                    <Link
                        href={route('login')}
                        className="rounded-full px-3 py-1.5 text-xs font-medium text-theme-muted transition hover:text-theme-strong sm:text-sm"
                    >
                        Sign in
                    </Link>
                    <Link
                        href={route('register')}
                        className="rounded-full bg-gradient-to-r from-ai-accent to-ai-accent-deep px-4 py-2 text-xs font-semibold text-white shadow-[var(--shadow-glow)] transition hover:brightness-110 sm:text-sm"
                    >
                        Get Started
                    </Link>
                </nav>
            </div>
        </motion.header>
    );
}
