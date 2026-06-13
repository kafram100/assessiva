export default function PenaltyRuleForm({ rule, onChange }) {
    const idx = rule.event_type;

    return (
        <tr className="border-b border-theme-line text-sm">
            <td className="py-2 pr-3 font-mono text-xs text-theme-muted">
                {rule.event_type}
            </td>
            <td className="py-2 pr-3">
                <input
                    type="number"
                    min="0"
                    step="0.25"
                    className="w-24 rounded-md border-theme-line bg-theme-surface-soft text-theme-strong"
                    value={rule.penalty_marks}
                    onChange={(e) =>
                        onChange(idx, {
                            penalty_marks: parseFloat(e.target.value) || 0,
                        })
                    }
                />
            </td>
            <td className="py-2 pr-3">
                <label className="inline-flex items-center gap-2 text-theme-muted">
                    <input
                        type="checkbox"
                        className="rounded border-theme-line"
                        checked={!!rule.is_enabled}
                        onChange={(e) =>
                            onChange(idx, { is_enabled: e.target.checked })
                        }
                    />
                    <span>Enabled</span>
                </label>
            </td>
            <td className="py-2 pr-3">
                <input
                    type="text"
                    className="w-full max-w-[140px] rounded-md border-theme-line bg-theme-surface-soft text-xs text-theme-strong"
                    placeholder="Severity tag"
                    value={rule.severity ?? ''}
                    onChange={(e) =>
                        onChange(idx, { severity: e.target.value || null })
                    }
                />
            </td>
            <td className="py-2">
                <input
                    type="text"
                    className="w-full min-w-[160px] rounded-md border-theme-line bg-theme-surface-soft text-xs text-theme-strong"
                    placeholder="Note"
                    value={rule.description ?? ''}
                    onChange={(e) =>
                        onChange(idx, { description: e.target.value || null })
                    }
                />
            </td>
        </tr>
    );
}
