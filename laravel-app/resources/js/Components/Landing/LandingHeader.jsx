import ThemeToggle from '@/Components/ThemeToggle';
import { Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';

export default function LandingHeader() {
    const { auth } = usePage().props;

    return (
        <motion.header
            initial={{ y: -16, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
            className="fixed left-0 right-0 top-0 z-50 border-b border-slate-100 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/80"
        >
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" className="group flex items-center gap-3 no-underline">
                    <img 
                        src="/logo.png" 
                        alt="Assessiva Logo" 
                        className="h-8 w-8 object-contain" 
                    />
                    <div className="leading-tight">
                        <div className="font-display text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                            Assessiva
                        </div>
                    </div>
                </Link>
                <nav className="flex items-center gap-2 sm:gap-4">

                    <ThemeToggle />
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-medium text-slate-900 transition hover:bg-slate-50 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={route('login')}
                                className="rounded-full px-3 py-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                            >
                                Sign In
                            </Link>
                            <Link
                                href={route('register')}
                                className="rounded-full bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                            >
                                Register
                            </Link>
                        </>
                    )}
                </nav>
            </div>
        </motion.header>
    );
}
