import { usePage } from '@inertiajs/react';

export default function FlashBanner() {
    const flash = usePage().props.flash;

    if (flash?.success) {
        return (
            <div className="border-b border-emerald-500/20 bg-emerald-950/35 px-4 py-3 text-center text-sm font-medium text-emerald-100/95 backdrop-blur-md">
                {flash.success}
            </div>
        );
    }

    if (flash?.error) {
        return (
            <div className="border-b border-red-500/20 bg-red-950/35 px-4 py-3 text-center text-sm font-medium text-red-100/95 backdrop-blur-md">
                {flash.error}
            </div>
        );
    }

    if (flash?.info) {
        return (
            <div className="border-b border-ai-accent/25 bg-ai-navy-900/60 px-4 py-3 text-center text-sm font-medium text-ai-glow-soft backdrop-blur-md">
                {flash.info}
            </div>
        );
    }

    return null;
}
