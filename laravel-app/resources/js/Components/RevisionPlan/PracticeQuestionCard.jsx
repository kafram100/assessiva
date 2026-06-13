import { useState } from 'react';

export default function PracticeQuestionCard({ topic, type, question, expected_answer: expected }) {
    const [show, setShow] = useState(false);
    return (
        <div className="rounded-xl border border-theme-line bg-theme-glass p-4 shadow-sm">
            <div className="flex flex-wrap items-center gap-2">
                {topic ? (
                    <span className="rounded-full bg-theme-surface-soft px-2 py-0.5 text-xs text-theme-muted">
                        {topic}
                    </span>
                ) : null}
                {type ? (
                    <span className="rounded-full bg-theme-surface-soft px-2 py-0.5 text-xs uppercase text-theme-dim">
                        {type}
                    </span>
                ) : null}
            </div>
            <p className="mt-3 text-sm font-medium text-theme-strong">{question}</p>
            <button
                type="button"
                onClick={() => setShow((v) => !v)}
                className="mt-3 text-xs font-semibold uppercase tracking-wide text-sky-400 hover:text-sky-300"
            >
                {show ? 'Hide study key' : 'Show study key'}
            </button>
            {show ? (
                <p className="mt-2 rounded-lg bg-theme-elevated p-3 text-sm text-theme-muted">
                    {expected}
                </p>
            ) : null}
        </div>
    );
}
