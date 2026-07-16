import { Head, router } from '@inertiajs/react';
import SubAgentLayout from './SubAgentLayout';

export default function History({ history }) {
    return (
        <SubAgentLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-white">
                    Balance History
                </h2>
            }
        >
            <Head title="Sub-Agent - History" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-[2rem] shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm min-w-max whitespace-nowrap">
                                <thead>
                                    <tr className="bg-white text-left dark:bg-neutral-900 border-b border-neutral-100 dark:border-neutral-800">
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Date</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Amount</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Balance After</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Reason</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                    {history.data.length === 0 ? (
                                        <tr>
                                            <td colSpan="4" className="px-5 py-12 text-center text-neutral-400 dark:text-neutral-500">No history found</td>
                                        </tr>
                                    ) : (
                                        history.data.map((entry) => (
                                            <tr key={entry.id} className="hover:bg-neutral-50 transition dark:hover:bg-neutral-800/50">
                                                <td className="px-5 py-3 text-neutral-500 dark:text-neutral-400 text-xs whitespace-nowrap">{entry.created_at}</td>
                                                <td className="px-5 py-3">
                                                    <span className={`font-semibold ${entry.change_amount >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-600 dark:text-red-400'}`}>
                                                        {entry.change_amount >= 0 ? '+' : ''}GH₵ {Number(entry.change_amount).toFixed(2)}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3 font-medium text-neutral-900 dark:text-white">GH₵ {Number(entry.balance_after).toFixed(2)}</td>
                                                <td className="px-5 py-3">
                                                    <span className="capitalize text-neutral-700 dark:text-neutral-300">{entry.reason.replace(/_/g, ' ')}</span>
                                                    {entry.description && (
                                                        <p className="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{entry.description}</p>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {history.links && (
                            <div className="px-5 py-4 border-t border-neutral-100 dark:border-neutral-800 flex justify-between items-center text-sm">
                                <p className="text-neutral-500 dark:text-neutral-400">
                                    Showing {history.from}–{history.to} of {history.total}
                                </p>
                                <div className="flex gap-1">
                                    {history.links.map((link, i) => (
                                        <button
                                            key={i}
                                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                            disabled={!link.url}
                                            className={`px-3 py-1 rounded text-sm font-medium transition ${
                                                link.active
                                                    ? 'bg-primary-600 text-white'
                                                    : link.url
                                                        ? 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                                                        : 'text-neutral-300 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </SubAgentLayout>
    );
}
