import { Link } from '@inertiajs/react';

export default function LandingFooter() {
    return (
        <footer className="relative z-10 border-t border-slate-100 bg-white px-4 py-12 sm:px-6 lg:px-8 dark:border-slate-800 dark:bg-slate-950">
            <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 sm:flex-row">
                <div className="text-center sm:text-left">
                    <div className="font-display text-sm font-bold text-slate-900 dark:text-white">Assessiva</div>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        © {new Date().getFullYear()} · A project by <span className="font-semibold">Assessiva</span>
                    </p>
                </div>
                <div className="flex flex-wrap items-center justify-center gap-6 text-sm text-slate-500 dark:text-slate-400">
                    <Link href={route('practice-quizzes.index')} className="transition hover:text-indigo-600 dark:hover:text-indigo-400">
                        Practice Hub
                    </Link>
                    <Link href={route('login')} className="transition hover:text-slate-900 dark:hover:text-white">
                        Sign In
                    </Link>
                    <Link href={route('register')} className="transition hover:text-slate-900 dark:hover:text-white">
                        Register
                    </Link>
                </div>
            </div>
        </footer>
    );
}
