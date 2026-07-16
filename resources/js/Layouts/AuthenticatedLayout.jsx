import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import ThemeToggle from '@/Components/ThemeToggle';
import SupportChat from '@/Components/SupportChat';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const userName = user?.full_name || user?.username || 'User';
    const [showingNav, setShowingNav] = useState(false);

    const isDealer = user?.role === 'dealer';

    const logoutUrl = isDealer ? route('dealer.logout') : route('agent.logout');

    const allNavLinks = [
        { href: route('dashboard'), name: 'dashboard', label: 'Dashboard' },
        { href: isDealer ? route('dealer.buy-data') : route('agent.buy-data'), name: isDealer ? 'dealer.buy-data' : 'agent.buy-data', label: 'Buy Data' },
        { href: isDealer ? route('dealer.orders') : route('agent.orders'), name: isDealer ? 'dealer.orders' : 'agent.orders', label: 'Orders' },
        { href: isDealer ? route('dealer.wallet') : route('agent.wallet'), name: isDealer ? 'dealer.wallet' : 'agent.wallet', label: 'Wallet' },
        ...(isDealer ? [
            { href: route('dealer.Sub-Agents'), name: 'dealer.Sub-Agents', label: 'Sub-Agents' },
            { href: route('dealer.store'), name: 'dealer.store', label: 'Store' },
            { href: route('dealer.store-orders'), name: 'dealer.store-orders', label: 'Store Orders' },
            { href: route('dealer.payment-settings'), name: 'dealer.payment-settings', label: 'Payment Settings' },
            { href: route('dealer.pricelist'), name: 'dealer.pricelist', label: 'Pricelist' },
        ] : []),
    ];

    return (
        <div className="min-h-screen bg-neutral-100 dark:bg-neutral-950 flex pb-16 md:pb-0">
            {/* Sidebar — hidden on small screens, fixed on md+ */}
            <aside className="max-md:hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 bg-white dark:bg-neutral-900 border-r border-neutral-200 dark:border-neutral-800 z-30">
                <div className="flex items-center h-16 px-5 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
                    <Link href={route('home')}>
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-sm">AL</span>
                            </div>
                            <div>
                                <span className="text-lg font-bold text-neutral-900 dark:text-white leading-tight block">AirLinks</span>
                                <span className="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 leading-tight block">{userName} - {user.role}</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    {allNavLinks.map((link) => (
                        <ResponsiveNavLink
                            key={link.href}
                            href={link.href}
                            active={route().current(link.name)}
                        >
                            {link.label}
                        </ResponsiveNavLink>
                    ))}
                    <form method="POST" action={logoutUrl}>
                        <button type="submit"
                            className="flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-800 text-base font-medium transition duration-150 ease-in-out focus:outline-none"
                        >
                            Log Out
                        </button>
                    </form>
                </nav>

                <div className="px-4 py-4 border-t border-neutral-200 dark:border-neutral-800 shrink-0 space-y-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-neutral-600 dark:text-neutral-400">Theme</span>
                        <ThemeToggle />
                    </div>
                    <div className="px-3 py-2 bg-secondary-50 border border-secondary-200 rounded-lg text-sm font-medium text-secondary-700 text-center dark:bg-secondary-900/30 dark:border-secondary-700 dark:text-secondary-300">
                        GH₵ {user.balance?.toFixed(2) || '0.00'}
                    </div>
                </div>
            </aside>

            {/* Right side wrapper — stacks top bar + content vertically */}
            <div className="flex-1 flex flex-col min-w-0 md:pl-64">
                {/* Mobile Top Bar only */}
                <div className="flex md:hidden items-center justify-between h-14 px-4 bg-white dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-800 shrink-0 sticky top-0 z-20">
                    <Link href={route('home')}>
                        <div className="flex items-center gap-2">
                            <div className="w-7 h-7 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-xs">AL</span>
                            </div>
                            <span className="text-lg font-bold text-neutral-900 dark:text-white">AirLinks</span>
                        </div>
                    </Link>

                    <div className="flex items-center gap-3">
                        <ThemeToggle />
                        <div className="text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-2 py-1 rounded-md">
                            GH₵ {user.balance?.toFixed(2) || '0.00'}
                        </div>
                    </div>
                </div>

                {/* Mobile Overlay (used for "More" menu) */}
                {showingNav && (
                    <>
                        <div className="fixed inset-0 z-40 bg-black/40 md:hidden" onClick={() => setShowingNav(false)} />
                        <div className="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-neutral-900 rounded-t-3xl overflow-hidden md:hidden shadow-2xl pb-16">
                            <div className="p-4 border-b border-neutral-100 dark:border-neutral-800 flex justify-between items-center bg-neutral-50 dark:bg-neutral-800/50">
                                <div>
                                    <h3 className="font-bold text-neutral-900 dark:text-white">{userName}</h3>
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400 capitalize">{user.role}</p>
                                </div>
                                <button onClick={() => setShowingNav(false)} className="p-2 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200">
                                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div className="max-h-[60vh] overflow-y-auto px-2 py-2">
                                {allNavLinks.slice(4).map((link) => (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        onClick={() => setShowingNav(false)}
                                        className={`flex items-center px-4 py-3 my-1 rounded-xl text-sm font-medium transition ${
                                            route().current(link.name)
                                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                                : 'text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                        }`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                                <form method="POST" action={logoutUrl} onSubmit={() => setShowingNav(false)}>
                                    <button type="submit"
                                        className="flex w-full items-center px-4 py-3 my-1 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition"
                                    >
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </>
                )}

                {/* Page content */}
                {header && (
                    <header className="bg-white shadow-sm border-b border-neutral-100 dark:border-neutral-800 dark:bg-neutral-900 shrink-0 sticky top-14 md:static z-10">
                        <div className="mx-auto max-w-7xl px-4 py-4 md:py-6 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}
                <main className="flex-1 dark:text-neutral-200">{children}</main>
            </div>

            {/* Mobile Bottom Navigation */}
            <div className="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-neutral-800 z-50 flex justify-around items-center px-2 pb-[env(safe-area-inset-bottom)]">
                {allNavLinks.slice(0, 4).map((link) => {
                    const isActive = route().current(link.name);
                    return (
                        <Link
                            key={link.href}
                            href={link.href}
                            className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${
                                isActive ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                            }`}
                        >
                            <div className={`p-1 rounded-lg transition-colors ${isActive ? 'bg-primary-50 dark:bg-primary-900/20' : ''}`}>
                                {link.label === 'Dashboard' && <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>}
                                {link.label === 'Buy Data' && <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>}
                                {link.label === 'Orders' && <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>}
                                {link.label === 'Wallet' && <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>}
                            </div>
                            <span className="text-[10px] font-medium tracking-wide">{link.label}</span>
                        </Link>
                    );
                })}
                
                {allNavLinks.length > 4 && (
                    <button
                        onClick={() => setShowingNav(true)}
                        className={`flex flex-col items-center justify-center w-full h-full space-y-1 ${
                            showingNav ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                        }`}
                    >
                        <div className={`p-1 rounded-lg transition-colors ${showingNav ? 'bg-primary-50 dark:bg-primary-900/20' : ''}`}>
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </div>
                        <span className="text-[10px] font-medium tracking-wide">More</span>
                    </button>
                )}
            </div>

            <SupportChat />
        </div>
    );
}
