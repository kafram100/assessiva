import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';

/**
 * Shell for authenticated student exam flows (entry, attempt, result).
 */
export default function StudentLayout({ children }) {
    return (
        <div className="ai-app-shell">
            <header className="sticky top-0 z-40 border-b border-theme-line backdrop-blur-xl bg-[color:var(--topbar-bg)]">
                <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
                    <Link href="/" className="flex items-center gap-2">
                        <ApplicationLogo className="h-9 w-auto fill-current text-ai-accent" />
                        <span className="text-sm font-semibold tracking-wide text-theme-strong">
                            Student
                        </span>
                    </Link>
                    <ThemeToggle />
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-8 text-theme-muted sm:px-6">
                {children}
            </main>
        </div>
    );
}
