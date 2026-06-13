import { Link } from '@inertiajs/react';

export default function LandingFooter() {
    return (
        <footer className="landing-footer-bar relative z-10 px-4 py-14 sm:px-6 lg:px-8">
            <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 sm:flex-row">
                <div className="text-center sm:text-left">
                    <div className="font-display text-sm font-semibold text-theme-strong">NovaGrade.ai</div>
                    <p className="mt-1 text-xs text-theme-dim">© {new Date().getFullYear()} · Built for future universities</p>
                </div>
                <div className="flex flex-wrap items-center justify-center gap-6 text-xs text-theme-dim">
                    <Link href={route('practice-quizzes.index')} className="transition hover:text-ai-accent">
                        Practice Hub
                    </Link>
                    <Link href={route('login')} className="transition hover:text-theme-strong">
                        Sign in
                    </Link>
                    <Link href={route('register')} className="transition hover:text-theme-strong">
                        Create account
                    </Link>
                    <a href="/student/entry" className="transition hover:text-theme-strong">
                        Student exam portal
                    </a>
                </div>
            </div>
        </footer>
    );
}
