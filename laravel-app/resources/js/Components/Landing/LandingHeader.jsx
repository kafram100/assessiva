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
                    <img 
                        src="/logo.png" 
                        alt="NovaGrade Logo" 
                        className="h-10 w-10 rounded-lg object-cover shadow-[var(--shadow-glow)] ring-1 ring-ai-accent/20" 
                    />
                    <div className="leading-tight">
                        <div className="font-display text-sm font-semibold tracking-tight text-theme-strong">
                            NovaGrade
                        </div>
                        <div className="text-[0.65rem] font-medium uppercase tracking-[0.2em] text-theme-dim">
                            Smart Exam Prep
                        </div>
                    </div>
                </Link>
                <nav className="flex items-center gap-2 sm:gap-3">

                    <ThemeToggle />
                    <Link
                        href={route('login')}
                        className="rounded-full px-3 py-1.5 text-xs font-medium text-theme-muted transition hover:text-theme-strong sm:text-sm"
                    >
                        Sign in
                    </Link>
                </nav>
            </div>
        </motion.header>
    );
}
