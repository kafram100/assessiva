import { usePage } from '@inertiajs/react';

export default function FlashBanner() {
    const flash = usePage().props.flash;

    if (flash?.success) {
        return (
            <div className="border-b border-emerald-500/25 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-900 backdrop-blur-md dark:border-emerald-500/20 dark:bg-emerald-950/35 dark:text-emerald-100">
                {flash.success}
            </div>
        );
    }

    if (flash?.error) {
        return (
            <div className="border-b border-red-500/25 bg-red-50 px-4 py-3 text-center text-sm font-medium text-red-900 backdrop-blur-md dark:border-red-500/20 dark:bg-red-950/35 dark:text-red-100">
                {flash.error}
            </div>
        );
    }

    if (flash?.info) {
        return (
            <div className="border-b border-ai-accent/30 bg-sky-50 px-4 py-3 text-center text-sm font-medium text-sky-950 backdrop-blur-md dark:border-ai-accent/25 dark:bg-ai-navy-900/60 dark:text-ai-glow-soft">
                {flash.info}
            </div>
        );
    }

    return null;
}
