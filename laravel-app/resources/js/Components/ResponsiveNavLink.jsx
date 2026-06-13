import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 text-base font-medium transition duration-150 ease-out focus:outline-none ${
                active
                    ? 'border-ai-accent bg-theme-highlight text-theme-strong focus:border-ai-accent'
                    : 'border-transparent text-theme-muted hover:border-theme-line hover:bg-theme-highlight hover:text-theme-strong focus:border-theme-line'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
