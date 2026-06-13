function RiskPill({ tier }) {
    const cls =
        tier === 'high'
            ? 'border-rose-400/40 bg-rose-500/10 text-rose-100'
            : 'border-amber-400/35 bg-amber-500/10 text-amber-100';
    return (
        <span
            className={`inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide ${cls}`}
        >
            {tier}
        </span>
    );
}

function Row({ row, tier }) {
    return (
        <tr className="border-b border-theme-line last:border-0">
            <td className="py-2.5 pr-3 font-mono text-xs text-theme-muted">
                {row.student_index_masked ?? '—'}
            </td>
            <td className="py-2.5 pr-3 text-xs text-theme-dim">
                {row.course ?? row.course_id ?? '—'}
            </td>
            <td className="py-2.5 pr-3 text-xs text-theme-muted">
                {row.note ??
                    (([
                        row.dropout_risk ? `Dropout: ${row.dropout_risk}` : null,
                        row.burnout_risk ? `Burnout: ${row.burnout_risk}` : null,
                        row.failure_probability != null
                            ? `Fail %: ${row.failure_probability}`
                            : null,
                        row.urgency ? `Urgency: ${row.urgency}` : null,
                    ]
                        .filter(Boolean)
                        .join(' · ')) ||
                        '—')}
            </td>
            <td className="py-2.5 text-right">
                <RiskPill tier={tier} />
            </td>
        </tr>
    );
}

export default function AtRiskStudentsTable({ atRisk }) {
    const high = atRisk?.high ?? [];
    const medium = atRisk?.medium ?? [];
    const note = atRisk?.note;

    return (
        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
            <h3 className="text-sm font-semibold uppercase tracking-widest text-rose-200/85">
                At-risk signals
            </h3>
            {note ? (
                <p className="mt-2 text-xs leading-relaxed text-theme-dim">
                    {note}
                </p>
            ) : null}
            <div className="mt-4 overflow-x-auto">
                <table className="w-full min-w-[520px] text-left text-sm">
                    <thead>
                        <tr className="text-[0.65rem] font-semibold uppercase tracking-wider text-theme-dim">
                            <th className="pb-2 pr-3">Learner ref.</th>
                            <th className="pb-2 pr-3">Course</th>
                            <th className="pb-2 pr-3">Context</th>
                            <th className="pb-2 text-right">Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        {high.map((r, i) => (
                            <Row key={`h-${i}`} row={r} tier="high" />
                        ))}
                        {medium.map((r, i) => (
                            <Row key={`m-${i}`} row={r} tier="medium" />
                        ))}
                    </tbody>
                </table>
                {high.length === 0 && medium.length === 0 ? (
                    <p className="mt-3 text-sm text-theme-dim">
                        No stored at-risk rows for this scope.
                    </p>
                ) : null}
            </div>
        </div>
    );
}
