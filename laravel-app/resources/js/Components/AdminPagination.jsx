import { Link } from '@inertiajs/react';

export default function AdminPagination({ links }) {
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-6 flex flex-wrap justify-center gap-1">
            {links.map((link, i) => (
                <span key={i}>
                    {link.url ? (
                        <Link
                            href={link.url}
                            preserveScroll
                            className={`inline-flex min-w-[2.25rem] items-center justify-center rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-gradient-to-r from-ai-accent to-ai-accent-deep text-white shadow-sm'
                                    : 'bg-theme-highlight text-theme-muted hover:bg-theme-glass-2 hover:text-theme-strong'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            className="inline-flex min-w-[2.25rem] cursor-default items-center justify-center rounded px-3 py-1 text-sm text-theme-dim"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </span>
            ))}
        </nav>
    );
}
