import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center border-b-2 px-1 pt-1 font-display text-sm font-medium leading-5 transition duration-150 ease-out focus:outline-none ' +
                (active
                    ? 'border-ai-accent text-theme-strong focus:border-ai-accent'
                    : 'border-transparent text-theme-dim hover:border-theme-line hover:bg-theme-highlight hover:text-theme-muted focus:border-theme-line') +
                className
            }
        >
            {children}
        </Link>
    );
}
