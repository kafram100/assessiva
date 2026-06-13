import { useState } from 'react';

export default function Flashcard({ front, back }) {
    const [open, setOpen] = useState(false);
    return (
        <button
            type="button"
            onClick={() => setOpen((v) => !v)}
            className="w-full rounded-xl border border-theme-line bg-gradient-to-br from-theme-glass to-theme-surface-soft p-4 text-left shadow-sm transition hover:border-sky-500/40 dark:from-slate-900/80 dark:to-slate-800/40"
        >
            <p className="text-xs font-semibold uppercase text-theme-dim">Front</p>
            <p className="mt-1 text-sm font-medium text-theme-strong">{front}</p>
            <div className="mt-3 border-t border-theme-line pt-3">
                <p className="text-xs font-semibold uppercase text-theme-dim">
                    {open ? 'Back' : 'Tap to reveal'}
                </p>
                {open ? (
                    <p className="mt-1 text-sm text-theme-muted">{back}</p>
                ) : (
                    <p className="mt-1 text-sm text-theme-dim">••••</p>
                )}
            </div>
        </button>
    );
}
