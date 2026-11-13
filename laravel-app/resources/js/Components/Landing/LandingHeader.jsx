import ThemeToggle from '@/Components/ThemeToggle';
import UserAvatar from '@/Components/UserAvatar';
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
            <div className="mx-auto flex h-14 sm:h-16 max-w-7xl items-center justify-between px-3 sm:px-6 lg:px-8">
                <Link href="/" className="group flex items-center gap-2 sm:gap-2.5 no-underline shrink-0">
                    <img 
                        src="/logo.png" 
                        alt="Assessiva Logo" 
                        className="h-7 w-7 sm:h-8 sm:w-8 object-contain shrink-0"
                    />
                    <span className="font-display text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white shrink-0">
                        Assessiva
                    </span>
                </Link>

                <nav className="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    <ThemeToggle className="!h-8 !w-8 sm:!h-9 sm:!w-9 rounded-lg sm:rounded-xl shrink-0" />
                    {auth.user ? (
                        <Link
                            href={auth.user.role === 'user' ? route('practice-quizzes.index') : route('admin.dashboard')}
                            className="flex items-center gap-2 whitespace-nowrap rounded-full border border-slate-200 px-2.5 sm:px-3.5 py-1 text-xs sm:text-sm font-semibold text-slate-900 transition hover:bg-slate-50 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 shrink-0 leading-normal"
                        >
                            <UserAvatar user={auth.user} size="xs" />
                            <span>{auth.user.role === 'user' ? 'Practice Hub' : 'Dashboard'}</span>
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={route('login')}
                                className="whitespace-nowrap rounded-full px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white shrink-0 leading-normal"
                            >
                                Sign In
                            </Link>
                            <Link
                                href={route('register')}
                                className="whitespace-nowrap rounded-full bg-indigo-600 px-3 sm:px-4 py-1.5 text-xs sm:text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 shrink-0 leading-normal"
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
