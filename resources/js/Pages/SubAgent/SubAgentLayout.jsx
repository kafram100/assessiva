import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const navLinks = [
    { href: route('Sub-Agent.dashboard'), name: 'Sub-Agent.dashboard', label: 'Dashboard' },
    { href: route('Sub-Agent.orders'), name: 'Sub-Agent.orders', label: 'Orders' },
    { href: route('Sub-Agent.topup'), name: 'Sub-Agent.topup', label: 'Top Up' },
    { href: route('Sub-Agent.history'), name: 'Sub-Agent.history', label: 'History' },
];

export default function SubAgentLayout({ header, children }) {
    const subAgent = usePage().props.subAgent;
    const [showingNav, setShowingNav] = useState(false);

    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route('Sub-Agent.logout'));
    };

    return (
        <div className="min-h-screen bg-neutral-100 dark:bg-neutral-950 flex">
            <aside className="max-lg:hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white dark:bg-neutral-900 border-r border-neutral-200 dark:border-neutral-800 z-30">
                <div className="flex items-center h-16 px-5 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
                    <Link href={route('Sub-Agent.dashboard')}>
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-sm">SA</span>
                            </div>
                            <div>
                                <span className="text-lg font-bold text-neutral-900 dark:text-white leading-tight block">Sub Agent</span>
                                <span className="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 leading-tight block">{subAgent?.username}</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    {navLinks.map((link) => (
                        <Link
                            key={link.href}
                            href={link.href}
                            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                                route().current(link.name)
                                    ? 'border-primary-400 bg-primary-50 text-primary-700'
                                    : 'border-transparent text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-800'
                            } text-base font-medium transition duration-150 ease-in-out focus:outline-none`}
                        >
                            {link.label}
                        </Link>
                    ))}
                    <form onSubmit={handleLogout}>
                        <button type="submit"
                            className="flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-800 text-base font-medium transition duration-150 ease-in-out focus:outline-none"
                        >
                            Log Out
                        </button>
                    </form>
                </nav>

                <div className="px-4 py-4 border-t border-neutral-200 dark:border-neutral-800 shrink-0">
                    <div className="px-3 py-2 bg-secondary-50 border border-secondary-200 rounded-lg text-sm font-medium text-secondary-700 text-center dark:bg-secondary-900/30 dark:border-secondary-700 dark:text-secondary-300">
                        GH₵ {Number(subAgent?.balance || 0).toFixed(2)}
                    </div>
                </div>
            </aside>

            <div className="flex-1 flex flex-col min-w-0 lg:pl-64">
                <div className="flex lg:hidden items-center justify-between h-16 px-4 bg-white dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
                    <Link href={route('Sub-Agent.dashboard')}>
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-sm">SA</span>
                            </div>
                            <span className="text-lg font-bold text-neutral-900 dark:text-white">Sub Agent</span>
                        </div>
                    </Link>

                    <button
                        onClick={() => setShowingNav(prev => !prev)}
                        className="inline-flex items-center justify-center rounded-md p-2 text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-500 focus:outline-none"
                    >
                        <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path className={!showingNav ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path className={showingNav ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {showingNav && (
                    <>
                        <div className="fixed inset-0 z-40 bg-black/40" onClick={() => setShowingNav(false)} />
                        <div className="fixed top-16 left-0 right-0 bottom-0 z-50 bg-white dark:bg-neutral-900 overflow-y-auto">
                            <div className="space-y-1 pb-3 pt-2 px-3">
                                {navLinks.map((link) => (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        onClick={() => setShowingNav(false)}
                                        className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                                            route().current(link.name)
                                                ? 'border-primary-400 bg-primary-50 text-primary-700'
                                                : 'border-transparent text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-800'
                                        } text-base font-medium transition duration-150 ease-in-out focus:outline-none`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                                <form onSubmit={(e) => { handleLogout(e); setShowingNav(false); }}>
                                    <button type="submit"
                                        className="flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-800 text-base font-medium transition duration-150 ease-in-out focus:outline-none"
                                    >
                                        Log Out
                                    </button>
                                </form>
                            </div>
                            <div className="border-t border-neutral-200 dark:border-neutral-800 pb-1 pt-4 px-4">
                                <div className="text-base font-medium text-neutral-800 dark:text-neutral-200">{subAgent?.username}</div>
                                <div className="text-sm font-medium text-neutral-500 dark:text-neutral-400 mt-1">
                                    Balance: GH₵ {Number(subAgent?.balance || 0).toFixed(2)}
                                </div>
                            </div>
                        </div>
                    </>
                )}

                {header && (
                    <header className="bg-white shadow-sm border-b border-neutral-100 dark:border-neutral-800 dark:bg-neutral-900 shrink-0">
                        <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}
                <main className="flex-1 dark:text-neutral-200">{children}</main>
            </div>
        </div>
    );
}
