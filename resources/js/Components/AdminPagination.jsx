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
                                    ? 'bg-sky-600 text-white'
                                    : 'bg-slate-800 text-slate-200 hover:bg-slate-700'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            className="inline-flex min-w-[2.25rem] cursor-default items-center justify-center rounded px-3 py-1 text-sm text-slate-500"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </span>
            ))}
        </nav>
    );
}
