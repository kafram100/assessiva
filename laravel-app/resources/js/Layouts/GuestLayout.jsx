import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, maxWidth = 'sm:max-w-md', backHref = '/' }) {
    const handleBack = (e) => {
        if (
            typeof window !== 'undefined' &&
            window.history.length > 1 &&
            document.referrer &&
            document.referrer.includes(window.location.host)
        ) {
            e.preventDefault();
            window.history.back();
        }
    };

    return (
        <div className="relative ai-app-shell flex min-h-screen flex-col items-center justify-center overflow-hidden p-4 sm:p-6">
            {/* Ambient background lighting matching landing page */}
            <div className="pointer-events-none absolute -top-40 right-0 -z-0 h-96 w-96 rounded-full bg-gradient-to-br from-indigo-500/15 via-purple-500/10 to-transparent blur-3xl dark:from-indigo-500/15" />
            <div className="pointer-events-none absolute top-1/2 left-0 -z-0 h-80 w-80 -translate-y-1/2 rounded-full bg-gradient-to-tr from-sky-500/15 via-indigo-500/10 to-transparent blur-3xl dark:from-sky-500/15" />

            {backHref && (
                <div className="absolute start-4 top-4 z-20 sm:start-8 sm:top-8">
                    <Link
                        href={backHref}
                        onClick={handleBack}
                        className="theme-toggle-btn group inline-flex !w-auto items-center gap-2 px-3 py-2 text-xs font-semibold shadow-sm"
                        title="Go back"
                        aria-label="Go back"
                    >
                        <svg
                            className="h-4 w-4 transition-transform group-hover:-translate-x-0.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        <span className="font-semibold">Back</span>
                    </Link>
                </div>
            )}
            <div className="absolute end-4 top-4 z-20 sm:end-8 sm:top-8">
                <ThemeToggle />
            </div>
            <div className="relative z-10 w-full max-w-md">
                <FlashBanner />
            </div>
            <div className="relative z-10 mb-4">
                <Link href="/" className="inline-flex items-center gap-2.5 transition-transform hover:scale-105">
                    <ApplicationLogo className="h-10 w-10 shadow-sm" />
                    <span className="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Assessiva
                    </span>
                </Link>
            </div>

            <div className={`ai-panel relative z-10 w-full ${maxWidth}`}>{children}</div>
        </div>
    );
}
