import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import Dropdown from '@/Components/Dropdown';
import UserAvatar from '@/Components/UserAvatar';
import { Link, usePage } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import FreeTokensModal from '@/Components/Student/FreeTokensModal';

export default function PracticeHubLayout({ children, backHref = '/', backLabel = 'Home' }) {
    const { auth } = usePage().props;
    const [showFreeTokensModal, setShowFreeTokensModal] = useState(false);

    useEffect(() => {
        if (auth.user?.role === 'user' && auth.user?.free_questions_remaining > 0) {
            const hasSeen = localStorage.getItem(`hasSeenFreeTokensModal_${auth.user.id}`);
            if (!hasSeen) {
                setShowFreeTokensModal(true);
                localStorage.setItem(`hasSeenFreeTokensModal_${auth.user.id}`, 'true');
            }
        }
    }, [auth.user]);

    return (
        <div className="ai-app-shell">
            <FlashBanner />
            <header className="sticky top-0 z-40 border-b border-theme-line backdrop-blur-xl bg-[color:var(--topbar-bg)]">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-1 sm:gap-4 px-3 py-2.5 sm:px-6 sm:py-3.5 lg:px-8">
                    <div className="flex items-center gap-1.5 sm:gap-3 min-w-0">
                        <Link
                            href={backHref}
                            className="theme-toggle-btn group inline-flex !h-8 sm:!h-10 !w-auto items-center gap-1 px-2 sm:px-3 text-xs font-semibold shadow-sm no-underline shrink-0"
                            title="Go to Home page"
                            aria-label="Go to Home page"
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
                            <span className="hidden sm:inline font-semibold">{backLabel}</span>
                        </Link>

                        <div className="h-5 w-px bg-theme-line hidden sm:block" />

                        <Link href="/" className="flex items-center gap-1.5 sm:gap-3 no-underline group min-w-0">
                            <ApplicationLogo className="h-7 sm:h-9 w-auto fill-ai-accent transition-transform group-hover:scale-105 shrink-0" />
                            <div className="hidden sm:block">
                                <div className="font-display text-base sm:text-lg font-bold text-theme-strong">
                                    {auth.user?.role === 'user' ? 'Assessiva Practice' : 'Assessiva Practice Hub'}
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div className="flex items-center gap-2 sm:gap-3">
                        <ThemeToggle />

                        {auth.user ? (
                            <>
                                {auth.user.role === 'user' && (
                                    <>
                                        {auth.user.free_questions_remaining > 0 && (
                                            <button
                                                onClick={() => setShowFreeTokensModal(true)}
                                                className="flex items-center gap-1.5 rounded-full bg-ai-accent/10 px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm font-medium text-ai-accent transition-all hover:bg-ai-accent/20"
                                                title="Free Questions Available"
                                            >
                                                <svg className="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                <span className="hidden sm:inline-block">Free:</span> {auth.user.free_questions_remaining}
                                            </button>
                                        )}
                                        <Link
                                            href={route('student.wallet')}
                                            className="flex items-center gap-1.5 whitespace-nowrap rounded-full bg-amber-500/10 px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm font-medium text-amber-500 transition-all hover:bg-amber-500/20"
                                            title="Buy Tokens"
                                        >
                                            <svg className="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span className="hidden sm:inline">Tokens:</span> <span className="font-bold">{auth.user.tokens}</span>
                                        </Link>
                                    </>
                                )}

                                <div className="relative">
                                    <Dropdown>
                                        <Dropdown.Trigger>
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-2 rounded-full sm:border sm:border-theme-line sm:bg-theme-highlight sm:pl-2 sm:pr-3 sm:py-1.5 text-sm font-medium text-theme-strong transition duration-150 ease-out hover:border-ai-accent/30 focus:outline-none"
                                            >
                                                <UserAvatar user={auth.user} size="md" />
                                                <span className="hidden sm:inline-block">{auth.user.name}</span>
                                                <svg className="hidden sm:block h-4 w-4 text-theme-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                                </svg>
                                            </button>
                                        </Dropdown.Trigger>

                                        <Dropdown.Content align="right" width="48">
                                            {auth.user.role !== 'user' && (
                                                <Dropdown.Link href={route('practice-quizzes.index')}>
                                                    Browse quizzes
                                                </Dropdown.Link>
                                            )}

                                            <Dropdown.Link href="/">Home Page</Dropdown.Link>
                                            {auth.user.role === 'user' ? (
                                                <>
                                                    <Dropdown.Link href={route('student.history')}>History</Dropdown.Link>
                                                    <Dropdown.Link href={route('student.settings')}>Settings</Dropdown.Link>
                                                </>
                                            ) : (
                                                <Dropdown.Link href={route('admin.dashboard')}>Dashboard</Dropdown.Link>
                                            )}
                                            <Dropdown.Link href={route('logout')} method="post" as="button">Log Out</Dropdown.Link>
                                        </Dropdown.Content>
                                    </Dropdown>
                                </div>
                            </>
                        ) : (
                            <div className="flex items-center gap-2">
                                <Link
                                    href={route('login')}
                                    className="px-3 py-1.5 rounded-lg text-sm font-medium text-theme-muted transition hover:bg-theme-layer-2 hover:text-theme-strong"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="px-3 py-1.5 rounded-lg text-sm font-medium text-theme-muted transition hover:bg-theme-layer-2 hover:text-theme-strong"
                                >
                                    Register
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-3 py-3 sm:px-6 sm:py-8 pb-32 sm:pb-8 lg:px-8">{children}</main>
            <footer className="border-t border-theme-line py-6 sm:py-8 text-center text-xs text-theme-dim px-3">
                Practice attempts build learning analytics for personalized revision. No proctoring on this hub.
            </footer>
            {auth.user?.role === 'user' && (
                <FreeTokensModal 
                    show={showFreeTokensModal} 
                    onClose={() => setShowFreeTokensModal(false)} 
                    freeBalance={auth.user?.free_questions_remaining || 0}
                />
            )}
            {auth.user?.role === 'user' && (
                <div className="md:hidden fixed bottom-0 left-0 right-0 z-50 border-t border-theme-line bg-[color:var(--topbar-bg)] backdrop-blur-xl pb-safe">
                    <div className="flex items-center justify-around h-16 px-2">
                        <Link href={route('practice-quizzes.index')} className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${route().current('practice-quizzes.index') ? 'text-indigo-600 dark:text-indigo-400' : 'text-theme-muted hover:text-theme-strong'}`}>
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span className="text-[10px] font-medium">Home</span>
                        </Link>
                        
                        <Link href={route('student.wallet')} className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${route().current('student.wallet') ? 'text-amber-500' : 'text-theme-muted hover:text-theme-strong'}`}>
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <span className="text-[10px] font-medium">Buy Tokens</span>
                        </Link>
                        
                        <Link href={route('student.history')} className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${route().current('student.history') ? 'text-indigo-600 dark:text-indigo-400' : 'text-theme-muted hover:text-theme-strong'}`}>
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span className="text-[10px] font-medium">History</span>
                        </Link>
                        
                        <Link href={route('student.settings')} className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${route().current('student.settings') ? 'text-indigo-600 dark:text-indigo-400' : 'text-theme-muted hover:text-theme-strong'}`}>
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span className="text-[10px] font-medium">Settings</span>
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
