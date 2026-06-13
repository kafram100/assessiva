import { useState } from 'react';

function buildQueryString(query) {
    const q = typeof query === 'function' ? query() : query;
    if (!q || typeof q !== 'object') {
        return '';
    }
    const params = new URLSearchParams();
    Object.entries(q).forEach(([k, v]) => {
        if (v === '' || v === null || v === undefined) {
            return;
        }
        params.append(k, String(v));
    });
    const s = params.toString();
    return s ? `?${s}` : '';
}

function parseFilename(contentDisposition) {
    if (!contentDisposition) {
        return 'export.xlsx';
    }
    const utf = /filename\*=UTF-8''([^;\s]+)/i.exec(contentDisposition);
    if (utf?.[1]) {
        try {
            return decodeURIComponent(utf[1]);
        } catch {
            return utf[1];
        }
    }
    const asc = /filename="([^"]+)"/i.exec(contentDisposition);
    if (asc?.[1]) {
        return asc[1];
    }
    const plain = /filename=([^;\s]+)/i.exec(contentDisposition);
    if (plain?.[1]) {
        return plain[1].replace(/^"+|"+$/g, '');
    }
    return 'export.xlsx';
}

export default function ExportExcelButton({
    href,
    query = null,
    className = '',
    children = 'Export Excel',
}) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const run = async () => {
        setError(null);
        setLoading(true);
        const url = href + buildQueryString(query);
        try {
            const res = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: '*/*',
                    'X-Excel-Export': '1',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const ct = res.headers.get('content-type') || '';
            if (!res.ok || ct.includes('application/json')) {
                const body = await res.json().catch(() => ({}));
                setError(
                    body.message ||
                        `Export failed (${res.status}). Check permissions or try again.`,
                );
                return;
            }
            const blob = await res.blob();
            const filename = parseFilename(
                res.headers.get('content-disposition'),
            );
            const blobUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(blobUrl);
        } catch {
            setError('Network error — could not download the file.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="inline-flex flex-col items-start gap-1">
            <button
                type="button"
                onClick={run}
                disabled={loading}
                className={`inline-flex items-center gap-2 rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong shadow-sm transition hover:bg-theme-surface-soft disabled:pointer-events-none disabled:opacity-40 ${className}`.trim()}
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.75"
                    className="h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-400"
                    aria-hidden
                >
                    <path
                        d="M7 3h7l3 3v15a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"
                        strokeLinejoin="round"
                    />
                    <path d="M14 3v4h4M12 11v6M9 14l3 3 3-3" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
                {loading ? 'Preparing…' : children}
            </button>
            {error ? (
                <p className="max-w-xs text-xs text-rose-600 dark:text-rose-300">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
