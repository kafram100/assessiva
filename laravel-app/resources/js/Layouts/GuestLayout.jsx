import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative ai-app-shell flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0">
            <div className="absolute end-4 top-4 z-20 sm:end-8 sm:top-8">
                <ThemeToggle />
            </div>
            <div className="w-full">
                <FlashBanner />
            </div>
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-ai-accent" />
                </Link>
            </div>

            <div className="ai-panel mt-6 w-full sm:max-w-md">{children}</div>
        </div>
    );
}
