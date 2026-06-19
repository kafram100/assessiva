import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function PracticeHubLayout({ children }) {
    const { auth } = usePage().props;
    const [showMobileMenu, setShowMobileMenu] = useState(false);

    return (
        <div className="ai-app-shell">
            <FlashBanner />
            <header className="sticky top-0 z-40 border-b border-theme-line backdrop-blur-xl bg-[color:var(--topbar-bg)]">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-3 no-underline">
                        <ApplicationLogo className="h-9 w-auto fill-ai-accent" />
                        <div>
                            <div className="font-display text-base sm:text-lg font-bold text-theme-strong">
                                {auth.user ? auth.user.name : 'NovaGrade Student'}
                            </div>
                        </div>
                    </Link>
                    
                    {/* Desktop Nav */}
                    <nav className="hidden items-center gap-3 sm:flex">
                        {auth.user?.role !== 'student' && (
                            <Link
                                href={route('practice-quizzes.index')}
                                className="rounded-xl border border-theme-line bg-theme-highlight px-4 py-2 text-sm font-semibold text-theme-strong shadow-sm backdrop-blur-sm transition duration-150 ease-out hover:border-ai-accent/40 hover:bg-theme-glass-2"
                            >
                                Browse quizzes
                            </Link>
                        )}
                        <ThemeToggle />
                        
                        {auth.user ? (
                            <>
                                {auth.user.role === 'student' ? (
                                        <>
                                            <Link
                                                href={route('student.wallet')}
                                                className="px-3 py-1.5 rounded-full text-sm font-medium text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 hover:text-amber-300 transition-all duration-200 active:scale-95 flex items-center gap-1.5"
                                                title="Click to top up tokens"
                                            >
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Tokens ({auth.user.tokens})
                                            </Link>
                                            <Link
                                                href={route('student.history')}
                                                className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 active:scale-95 ${
                                                    route().current('student.history')
                                                        ? 'bg-theme-layer-2 text-theme-strong'
                                                        : 'text-theme-muted hover:bg-theme-layer-2 hover:text-theme-strong'
                                                }`}
                                            >
                                                History
                                            </Link>
                                            <Link
                                                href={route('student.settings')}
                                                className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 active:scale-95 ${
                                                    route().current('student.settings')
                                                        ? 'bg-theme-layer-2 text-theme-strong'
                                                        : 'text-theme-muted hover:bg-theme-layer-2 hover:text-theme-strong'
                                                }`}
                                            >
                                                Settings
                                            </Link>
                                        </>
                                ) : (
                                    <Link
                                        href={route('admin.dashboard')}
                                        className="px-3 py-1.5 rounded-lg text-sm font-medium text-sky-400 hover:bg-sky-500/10 hover:text-sky-300 transition-all duration-200 active:scale-95"
                                    >
                                        Dashboard
                                    </Link>
                                )}
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="ml-2 px-3 py-1.5 rounded-lg text-sm font-medium text-theme-muted transition-all duration-200 active:scale-95 hover:bg-theme-layer-2 hover:text-theme-strong"
                                >
                                    Log out
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="ml-2 px-3 py-1.5 rounded-lg text-sm font-medium text-theme-muted transition-all duration-200 active:scale-95 hover:bg-theme-layer-2 hover:text-theme-strong"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="px-3 py-1.5 rounded-lg text-sm font-medium text-theme-muted transition-all duration-200 active:scale-95 hover:bg-theme-layer-2 hover:text-theme-strong"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>

                    {/* Mobile Hamburger */}
                    <div className="flex items-center gap-3 sm:hidden">
                        <ThemeToggle />
                        <button
                            onClick={() => setShowMobileMenu(!showMobileMenu)}
                            className="inline-flex items-center justify-center rounded-md p-2 text-theme-muted transition duration-150 ease-out hover:bg-theme-highlight hover:text-theme-strong focus:outline-none"
                        >
                            <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    className={!showMobileMenu ? 'inline-flex' : 'hidden'}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    className={showMobileMenu ? 'inline-flex' : 'hidden'}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Mobile Menu Dropdown */}
                {showMobileMenu && (
                    <div className="border-t border-theme-line bg-theme-highlight px-4 py-4 sm:hidden">
                        <div className="flex flex-col space-y-4">
                            {auth.user?.role !== 'student' && (
                                <Link
                                    href={route('practice-quizzes.index')}
                                    className="text-sm font-semibold text-theme-strong"
                                >
                                    Browse quizzes
                                </Link>
                            )}
                            {auth.user ? (
                                <>
                                    {auth.user.role === 'student' ? (
                                        <>
                                            <Link
                                                href={route('student.wallet')}
                                                className="block w-full text-left px-4 py-3 rounded-xl text-sm font-medium text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 transition-all duration-200 active:scale-[0.98] flex items-center gap-2"
                                            >
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Tokens ({auth.user.tokens})
                                            </Link>
                                            <Link
                                                href={route('student.history')}
                                                className={`block w-full text-left px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 active:scale-[0.98] ${
                                                    route().current('student.history')
                                                        ? 'bg-theme-layer-2 text-theme-strong'
                                                        : 'text-theme-muted hover:bg-theme-layer-2 hover:text-theme-strong'
                                                }`}
                                            >
                                                History
                                            </Link>
                                            <Link
                                                href={route('student.settings')}
                                                className={`block w-full text-left px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 active:scale-[0.98] ${
                                                    route().current('student.settings')
                                                        ? 'bg-theme-layer-2 text-theme-strong'
                                                        : 'text-theme-muted hover:bg-theme-layer-2 hover:text-theme-strong'
                                                }`}
                                            >
                                                Settings
                                            </Link>
                                        </>
                                    ) : (
                                        <Link
                                            href={route('admin.dashboard')}
                                            className="text-sm font-medium text-sky-400"
                                        >
                                            Dashboard
                                        </Link>
                                    )}
                                    <Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                        className="w-full text-left px-4 py-3 rounded-xl text-sm font-medium text-theme-muted transition-all duration-200 active:scale-[0.98] hover:bg-theme-layer-2 hover:text-theme-strong"
                                    >
                                        Log out
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="text-sm text-theme-muted transition hover:text-theme-strong"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="text-sm text-theme-muted transition hover:text-theme-strong"
                                    >
                                        Register
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </header>
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{children}</main>
            <footer className="border-t border-theme-line py-8 text-center text-xs text-theme-dim">
                Practice attempts build learning analytics for personalized revision. No proctoring on this
                hub.
            </footer>
        </div>
    );
}
