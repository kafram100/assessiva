import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import SupportChat from '@/Components/SupportChat';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center bg-neutral-50 dark:bg-neutral-950 overflow-hidden font-sans">
            {/* Immersive Background Elements */}
            <div className="absolute inset-0 bg-gradient-to-br from-primary-900/10 via-transparent to-primary-600/10 dark:from-primary-900/20 dark:to-primary-600/20"></div>
            <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-primary-400/20 dark:bg-primary-500/20 blur-[100px] animate-float"></div>
            <div className="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-primary-600/20 dark:bg-primary-600/20 blur-[100px] animate-float" style={{ animationDelay: '2s' }}></div>
            
            <div className="absolute top-6 right-6 z-50 bg-white/50 dark:bg-neutral-900/50 backdrop-blur-md rounded-full shadow-sm border border-white/20 dark:border-neutral-700/50">
                <ThemeToggle />
            </div>

            <div className="relative z-10 w-full sm:max-w-md px-6">
                <div className="flex justify-center mb-8">
                    <Link href="/" className="group drop-shadow-md">
                        <ApplicationLogo className="h-24 w-24 fill-current text-primary-600 dark:text-primary-500 transition-transform duration-500 group-hover:scale-110" />
                    </Link>
                </div>

                <div className="w-full overflow-hidden bg-white/80 dark:bg-neutral-900/80 backdrop-blur-xl px-8 py-10 shadow-2xl shadow-primary-900/5 sm:rounded-[2rem] border border-white/50 dark:border-neutral-700/50 animate-in zoom-in-95 duration-500">
                    {children}
                </div>
            </div>
            
            <SupportChat />
        </div>
    );
}
