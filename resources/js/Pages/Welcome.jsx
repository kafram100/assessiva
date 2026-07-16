import { Head, Link } from '@inertiajs/react';
import ThemeToggle from '@/Components/ThemeToggle';
import ApplicationLogo from '@/Components/ApplicationLogo';
import SupportChat from '@/Components/SupportChat';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Welcome to AirLinks - Smarter Way to Buy Data" />
            <div className="min-h-screen bg-neutral-50 dark:bg-neutral-950 font-sans text-neutral-900 dark:text-neutral-100 flex flex-col">
                
                {/* Navigation Bar */}
                <nav className="w-full bg-white/80 dark:bg-neutral-900/80 backdrop-blur-md border-b border-neutral-200 dark:border-neutral-800 sticky top-0 z-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-sm">AL</span>
                            </div>
                            <span className="text-xl font-bold tracking-tight">AirLinks</span>
                        </div>
                        
                        <div className="flex items-center gap-4 sm:gap-6">
                            <ThemeToggle />
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 transition"
                                >
                                    Go to Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-white transition"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="text-sm font-semibold bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl transition shadow-sm hover:shadow-md"
                                    >
                                        Register
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <main className="flex-1">
                    <section className="relative px-4 pt-20 pb-24 sm:px-6 lg:px-8 flex flex-col items-center text-center overflow-hidden">
                        {/* Background glowing blobs */}
                        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-gradient-to-r from-primary-500/20 to-tertiary-500/20 blur-[100px] rounded-full pointer-events-none dark:from-primary-900/30 dark:to-tertiary-900/30"></div>
                        
                        <div className="relative z-10 max-w-3xl mx-auto space-y-8">
                            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight text-neutral-900 dark:text-white">
                                The Smarter Way to <br className="hidden sm:block" />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-tertiary-500 dark:from-primary-400 dark:to-tertiary-400">Buy & Sell Data</span>
                            </h1>
                            <p className="text-lg sm:text-xl text-neutral-600 dark:text-neutral-400 max-w-2xl mx-auto leading-relaxed">
                                Join AirLinks to access instant data bundles across all networks. Whether you're a customer, an agent, or a dealer looking to build your own store via API, we've got you covered.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                                {auth.user ? (
                                    <Link href={route('dashboard')} className="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-2xl shadow-lg hover:shadow-primary-600/30 transition-all hover:-translate-y-0.5">
                                        Go to Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link href={route('register')} className="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-2xl shadow-lg hover:shadow-primary-600/30 transition-all hover:-translate-y-0.5">
                                            Get Started Now
                                        </Link>
                                        <Link href={route('login')} className="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700 rounded-2xl shadow-sm transition-all hover:-translate-y-0.5">
                                            Sign In
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Features Grid */}
                    <section className="px-4 py-20 bg-white dark:bg-neutral-900 border-y border-neutral-200 dark:border-neutral-800">
                        <div className="max-w-7xl mx-auto">
                            <div className="text-center max-w-2xl mx-auto mb-16">
                                <h2 className="text-3xl font-bold tracking-tight text-neutral-900 dark:text-white mb-4">Everything you need to succeed</h2>
                                <p className="text-neutral-600 dark:text-neutral-400">Our platform is built to provide seamless, automated delivery and powerful management tools for all types of users.</p>
                            </div>
                            
                            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                                {/* Feature 1 */}
                                <div className="bg-neutral-50 dark:bg-neutral-800/50 p-8 rounded-3xl border border-neutral-100 dark:border-neutral-800 hover:shadow-lg transition-all hover:-translate-y-1">
                                    <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-6">
                                        <svg className="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <h3 className="text-xl font-bold text-neutral-900 dark:text-white mb-3">Instant Delivery</h3>
                                    <p className="text-neutral-600 dark:text-neutral-400 text-sm leading-relaxed">Purchases are processed automatically in milliseconds. Experience true instant data and airtime delivery directly to any network.</p>
                                </div>
                                {/* Feature 2 */}
                                <div className="bg-neutral-50 dark:bg-neutral-800/50 p-8 rounded-3xl border border-neutral-100 dark:border-neutral-800 hover:shadow-lg transition-all hover:-translate-y-1">
                                    <div className="w-12 h-12 bg-tertiary-100 dark:bg-tertiary-900/30 rounded-xl flex items-center justify-center mb-6">
                                        <svg className="w-6 h-6 text-tertiary-600 dark:text-tertiary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <h3 className="text-xl font-bold text-neutral-900 dark:text-white mb-3">Agent & Dealer Portals</h3>
                                    <p className="text-neutral-600 dark:text-neutral-400 text-sm leading-relaxed">Dedicated portals for Agents and Dealers with heavily discounted pricing, sub-agent management, and a robust wallet system.</p>
                                </div>
                                {/* Feature 3 */}
                                <div className="bg-neutral-50 dark:bg-neutral-800/50 p-8 rounded-3xl border border-neutral-100 dark:border-neutral-800 hover:shadow-lg transition-all hover:-translate-y-1">
                                    <div className="w-12 h-12 bg-secondary-100 dark:bg-secondary-900/30 rounded-xl flex items-center justify-center mb-6">
                                        <svg className="w-6 h-6 text-secondary-600 dark:text-secondary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    </div>
                                    <h3 className="text-xl font-bold text-neutral-900 dark:text-white mb-3">Public Storefronts</h3>
                                    <p className="text-neutral-600 dark:text-neutral-400 text-sm leading-relaxed">Dealers can configure their own unique public URLs with custom pricing. Customers can buy directly via Paystack without logging in.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="bg-white dark:bg-neutral-950 py-10 border-t border-neutral-200 dark:border-neutral-800 text-center">
                    <div className="max-w-7xl mx-auto px-4">
                        <div className="flex items-center justify-center gap-2 mb-4">
                            <div className="w-6 h-6 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-md flex items-center justify-center">
                                <span className="text-white font-bold text-[10px]">AL</span>
                            </div>
                            <span className="text-lg font-bold text-neutral-900 dark:text-white">AirLinks</span>
                        </div>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            &copy; {new Date().getFullYear()} AirLinks. All rights reserved.
                        </p>
                    </div>
                </footer>

                {/* Install PWA Prompt */}
                <InstallPWA />
            </div>
        </>
    );
}
