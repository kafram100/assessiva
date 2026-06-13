import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';

export default function PracticeHubLayout({ children }) {
    return (
        <div className="ai-app-shell">
            <FlashBanner />
            <header className="sticky top-0 z-40 border-b border-theme-line backdrop-blur-xl bg-[color:var(--topbar-bg)]">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-3 no-underline">
                        <ApplicationLogo className="h-9 w-auto fill-ai-accent" />
                        <div>
                            <div className="font-display text-xs font-semibold uppercase tracking-widest text-ai-accent">
                                AI Practice &amp; Mastery Hub
                            </div>
                            <div className="text-sm font-medium text-theme-muted">
                                Self-paced practice — not an official exam
                            </div>
                        </div>
                    </Link>
                    <nav className="flex items-center gap-2 sm:gap-3">
                        <Link
                            href={route('practice-quizzes.index')}
                            className="rounded-xl border border-theme-line bg-theme-highlight px-4 py-2 text-sm font-semibold text-theme-strong shadow-sm backdrop-blur-sm transition duration-150 ease-out hover:border-ai-accent/40 hover:bg-theme-glass-2"
                        >
                            Browse quizzes
                        </Link>
                        <ThemeToggle />
                        <Link
                            href={route('login')}
                            className="text-sm text-theme-muted underline decoration-[color:var(--border-subtle)] underline-offset-4 transition hover:text-theme-strong"
                        >
                            Staff login
                        </Link>
                    </nav>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{children}</main>
            <footer className="border-t border-theme-line py-8 text-center text-xs text-theme-dim">
                Practice attempts build learning analytics for personalized revision. No proctoring on this
                hub.
            </footer>
        </div>
    );
}
