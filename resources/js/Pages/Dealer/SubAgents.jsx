import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Dialog, DialogPanel, DialogTitle, Transition, TransitionChild } from '@headlessui/react';

export default function SubAgents({ SubAgents }) {
    const { flash } = usePage().props;
    const [showCreate, setShowCreate] = useState(false);
    const [showEdit, setShowEdit] = useState(null);
    const [form, setForm] = useState({
        username: '', email: '', password: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [showPassword, setShowPassword] = useState(false);
    const [showEditPassword, setShowEditPassword] = useState(false);

    const [creditTarget, setCreditTarget] = useState(null);
    const [creditAmount, setCreditAmount] = useState('');
    const [resetTarget, setResetTarget] = useState(null);
    const [newPassword, setNewPassword] = useState('');
    const [actionLoading, setActionLoading] = useState(false);

    const [flashMsg, setFlashMsg] = useState(flash?.success || null);
    const [linkCopied, setLinkCopied] = useState(false);

    const loginUrl = route('Sub-Agent.login');

    const resetForm = () => {
        setForm({ username: '', email: '', password: '' });
        setError(null);
    };

    const openCreate = () => {
        resetForm();
        setShowCreate(true);
    };

    const openEdit = (sub) => {
        setForm({
            username: sub.username,
            email: sub.email || '',
            password: '',
        });
        setShowEdit(sub);
        setError(null);
    };

    const handleSubmit = async (e, id = null) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        const payload = { ...form };
        if (!payload.password && id) delete payload.password;

        try {
            if (id) {
                await router.put(route('dealer.Sub-Agents.update', id), payload, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => { setShowEdit(null); resetForm(); },
                    onError: (errs) => setError(Object.values(errs).flat().join(', ')),
                });
            } else {
                await router.post(route('dealer.Sub-Agents.store'), payload, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => { setShowCreate(false); resetForm(); },
                    onError: (errs) => setError(Object.values(errs).flat().join(', ')),
                });
            }
        } catch (err) {
            setError('Something went wrong');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    Sub-Agents
                </h2>
            }
        >
            <Head title="Sub-Agents" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {flashMsg && (
                        <div className="mb-6 px-5 py-3 rounded-xl text-sm font-medium bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                            {flashMsg}
                        </div>
                    )}

                    <div className="mb-6 px-5 py-4 rounded-xl bg-primary-50 border border-primary-200 dark:bg-primary-900/10 dark:border-primary-800">
                        <p className="text-sm font-semibold text-primary-700 dark:text-primary-300">Sub-Agent Login URL</p>
                        <p className="text-xs text-primary-600 dark:text-primary-400 mt-0.5">Share this link with your Sub-Agents to access their account:</p>
                        <div className="mt-2 flex items-center gap-1">
                            <code className="inline-block px-3 py-1.5 bg-white dark:bg-neutral-800 rounded-lg text-sm font-mono text-neutral-800 dark:text-neutral-200 border border-primary-200 dark:border-primary-800 break-all">
                                {loginUrl}
                            </code>
                            <a href={loginUrl} target="_blank" rel="noopener noreferrer"
                                className="inline-flex items-center justify-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-100 dark:text-primary-400 dark:hover:bg-primary-900/20 transition shrink-0"
                                title="Preview Sub-Agent login page">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <button onClick={() => {
                                navigator.clipboard.writeText(loginUrl);
                                setLinkCopied(true);
                                setTimeout(() => setLinkCopied(false), 2000);
                            }}
                                className="inline-flex items-center justify-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-100 dark:text-primary-400 dark:hover:bg-primary-900/20 transition shrink-0"
                                title="Copy login URL">
                                {linkCopied ? (
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                ) : (
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                )}
                            </button>
                        </div>
                    </div>

                    <div className="bg-white rounded-[2rem] shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                        <div className="px-6 py-5 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900/50">
                            <h3 className="text-lg font-bold text-neutral-900 dark:text-neutral-200">
                                All Sub-Agents ({SubAgents.length})
                            </h3>
                            <button onClick={openCreate}
                                className="px-6 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-500 hover:shadow-lg shadow-primary-600/30 transition-all active:scale-95">
                                + Add Sub-Agent
                            </button>
                        </div>

                        {/* Data Display */}
                        <div className="w-full">
                            {/* Mobile Card View (hidden on md+) */}
                            <div className="md:hidden flex flex-col gap-3 p-4">
                                {SubAgents.length === 0 ? (
                                    <div className="py-12 text-center text-neutral-400 dark:text-neutral-500 bg-neutral-50 dark:bg-neutral-800/50 rounded-2xl">
                                        No Sub-Agents yet
                                    </div>
                                ) : (
                                    SubAgents.map((sub) => (
                                        <div key={sub.id} className="bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                                            {/* Colored left border based on status */}
                                            <div className={`absolute left-0 top-0 bottom-0 w-1 ${
                                                sub.is_active ? 'bg-primary-500' : 'bg-red-500'
                                            }`}></div>
                                            
                                            <div className="flex justify-between items-start mb-3">
                                                <div>
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <span className="font-bold text-neutral-900 dark:text-white">{sub.username}</span>
                                                    </div>
                                                    <div className="text-sm text-neutral-600 dark:text-neutral-300 font-medium">
                                                        {sub.email || '—'}
                                                    </div>
                                                    <div className="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                                        Orders: {sub.order_count}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-bold text-primary-600 dark:text-primary-400 mb-1">
                                                        GH₵ {sub.balance.toFixed(2)}
                                                    </div>
                                                    <span className={`inline-block text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                                        sub.is_active
                                                            ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                                    }`}>
                                                        {sub.is_active ? 'Active' : 'Suspended'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="flex justify-end items-center pt-3 border-t border-neutral-100 dark:border-neutral-700 gap-2">
                                                <button onClick={() => openEdit(sub)}
                                                    className="px-3 py-1.5 rounded-lg text-sm font-medium text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-900/20 active:scale-95 transition"
                                                    title="Edit">
                                                    Edit
                                                </button>
                                                <button onClick={() => { setCreditTarget(sub); setCreditAmount(''); setError(null); }}
                                                    className="px-3 py-1.5 rounded-lg text-sm font-medium text-secondary-600 bg-secondary-50 dark:text-secondary-400 dark:bg-secondary-900/20 active:scale-95 transition"
                                                    title="Credit Balance">
                                                    Credit
                                                </button>
                                                <button onClick={() => { setResetTarget(sub); setNewPassword(''); setError(null); }}
                                                    className="p-1.5 rounded-lg text-neutral-500 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition"
                                                    title="Reset Password">
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            {/* Desktop Table View (hidden on mobile) */}
                            <div className="hidden md:block overflow-x-auto">
                                <table className="w-full text-sm min-w-max whitespace-nowrap">
                                    <thead>
                                        <tr className="bg-white text-left dark:bg-neutral-900 border-b border-neutral-100 dark:border-neutral-800">
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider">Username</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider">Email</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider">Balance</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider">Orders</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                        {SubAgents.length === 0 ? (
                                            <tr>
                                                <td colSpan="6" className="px-5 py-12 text-center text-neutral-400">No Sub-Agents yet</td>
                                            </tr>
                                        ) : (
                                            SubAgents.map((sub) => (
                                                <tr key={sub.id} className="hover:bg-neutral-50 transition dark:hover:bg-neutral-800/50">
                                                    <td className="px-5 py-3 font-medium text-neutral-900 dark:text-neutral-200">{sub.username}</td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{sub.email || '—'}</td>
                                                    <td className="px-5 py-3 font-medium text-neutral-900 dark:text-neutral-200">GH₵ {sub.balance.toFixed(2)}</td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{sub.order_count}</td>
                                                    <td className="px-5 py-3">
                                                        <span className={`inline-block text-xs px-2 py-0.5 rounded-full font-medium ${
                                                            sub.is_active
                                                                ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                                        }`}>
                                                            {sub.is_active ? 'Active' : 'Suspended'}
                                                        </span>
                                                    </td>
                                                    <td className="px-5 py-3">
                                                        <div className="flex items-center gap-2">
                                                            <button onClick={() => openEdit(sub)}
                                                                className="p-1.5 rounded-lg text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20 transition"
                                                                title="Edit">
                                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button onClick={() => { setCreditTarget(sub); setCreditAmount(''); setError(null); }}
                                                                className="p-1.5 rounded-lg text-secondary-600 hover:bg-secondary-50 dark:text-secondary-400 dark:hover:bg-secondary-900/20 transition"
                                                                title="Credit Balance">
                                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                                </svg>
                                                            </button>
                                                            <button onClick={() => { setResetTarget(sub); setNewPassword(''); setError(null); }}
                                                                className="p-1.5 rounded-lg text-neutral-500 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition"
                                                                title="Reset Password">
                                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Create Modal */}
            <Transition show={showCreate}>
                <Dialog as="div" className="relative z-50" onClose={() => setShowCreate(false)}>
                    <TransitionChild
                        enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100"
                        leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
                        <div className="fixed inset-0 bg-black/40" />
                    </TransitionChild>
                    <div className="fixed inset-0 flex items-center justify-center p-4">
                        <TransitionChild
                            enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                            <DialogPanel className="w-full max-w-lg bg-white rounded-2xl shadow-xl p-6 dark:bg-neutral-900 dark:border dark:border-neutral-800">
                                <DialogTitle className="text-lg font-bold text-neutral-900 mb-4 dark:text-neutral-200">
                                    Add Sub-Agent
                                </DialogTitle>
                                <form onSubmit={(e) => handleSubmit(e)} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Username *</label>
                                        <input type="text" value={form.username}
                                            onChange={e => setForm({ ...form, username: e.target.value })}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Email *</label>
                                        <input type="email" value={form.email}
                                            onChange={e => setForm({ ...form, email: e.target.value })}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            required />
                                    </div>
                                    <div className="relative">
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Password *</label>
                                        <input type={showPassword ? 'text' : 'password'} value={form.password}
                                            onChange={e => setForm({ ...form, password: e.target.value })}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 pr-10 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            minLength="6" maxLength="12" required />
                                        <button type="button" onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-3 top-[38px] text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                                            {showPassword ? (
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>

                                    {error && (
                                        <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                                    )}

                                    <div className="flex gap-3 pt-2">
                                        <button type="button" onClick={() => { setShowCreate(false); resetForm(); }}
                                            className="flex-1 py-3 border-2 border-neutral-200 rounded-xl font-semibold text-sm text-neutral-600 hover:bg-neutral-50 transition dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                            Cancel
                                        </button>
                                        <button type="submit" disabled={loading}
                                            className="flex-1 bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition">
                                            {loading ? 'Creating...' : 'Create Sub-Agent'}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>

            {/* Edit Modal */}
            <Transition show={!!showEdit}>
                <Dialog as="div" className="relative z-50" onClose={() => { setShowEdit(null); resetForm(); }}>
                    <TransitionChild
                        enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100"
                        leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
                        <div className="fixed inset-0 bg-black/40" />
                    </TransitionChild>
                    <div className="fixed inset-0 flex items-center justify-center p-4">
                        <TransitionChild
                            enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                            <DialogPanel className="w-full max-w-lg bg-white rounded-2xl shadow-xl p-6 dark:bg-neutral-900 dark:border dark:border-neutral-800">
                                <DialogTitle className="text-lg font-bold text-neutral-900 mb-4 dark:text-neutral-200">
                                    Edit Sub-Agent
                                </DialogTitle>
                                <form onSubmit={(e) => handleSubmit(e, showEdit?.id)} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Username *</label>
                                        <input type="text" value={form.username}
                                            onChange={e => setForm({ ...form, username: e.target.value })}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Email *</label>
                                        <input type="email" value={form.email}
                                            onChange={e => setForm({ ...form, email: e.target.value })}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            required />
                                    </div>
                                    <div className="relative">
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Password (leave blank to keep current)</label>
                                        <input type={showEditPassword ? 'text' : 'password'} value={form.password}
                                            onChange={e => setForm({ ...form, password: e.target.value })}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 pr-10 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            minLength="6" maxLength="12" />
                                        <button type="button" onClick={() => setShowEditPassword(!showEditPassword)}
                                            className="absolute right-3 top-[38px] text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                                            {showEditPassword ? (
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>

                                    {error && (
                                        <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                                    )}

                                    <div className="flex gap-3 pt-2">
                                        <button type="button" onClick={() => { setShowEdit(null); resetForm(); }}
                                            className="flex-1 py-3 border-2 border-neutral-200 rounded-xl font-semibold text-sm text-neutral-600 hover:bg-neutral-50 transition dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                            Cancel
                                        </button>
                                        <button type="submit" disabled={loading}
                                            className="flex-1 bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition">
                                            {loading ? 'Saving...' : 'Save Changes'}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>

            {/* Credit Modal */}
            <Transition show={!!creditTarget}>
                <Dialog as="div" className="relative z-50" onClose={() => { setCreditTarget(null); setCreditAmount(''); setError(null); }}>
                    <TransitionChild
                        enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100"
                        leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
                        <div className="fixed inset-0 bg-black/40" />
                    </TransitionChild>
                    <div className="fixed inset-0 flex items-center justify-center p-4">
                        <TransitionChild
                            enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                            <DialogPanel className="w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 dark:bg-neutral-900 dark:border dark:border-neutral-800">
                                <DialogTitle className="text-lg font-bold text-neutral-900 mb-1 dark:text-neutral-200">
                                    Credit Balance
                                </DialogTitle>
                                <p className="text-sm text-neutral-500 mb-4">
                                    Add credit to <strong className="text-neutral-700 dark:text-neutral-300">{creditTarget?.username}</strong>
                                </p>
                                <form onSubmit={async (e) => {
                                    e.preventDefault();
                                    setActionLoading(true);
                                    setError(null);
                                    try {
                                        await router.post(route('dealer.Sub-Agents.credit', creditTarget.id), {
                                            amount: creditAmount,
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                            onSuccess: () => { setCreditTarget(null); setCreditAmount(''); },
                                            onError: (errs) => setError(Object.values(errs).flat().join(', ')),
                                        });
                                    } catch { setError('Something went wrong'); }
                                    finally { setActionLoading(false); }
                                }} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Amount (GH₵)</label>
                                        <input type="number" step="0.01" value={creditAmount}
                                            onChange={e => setCreditAmount(e.target.value)}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            placeholder="Positive = credit, negative = debit"
                                            required />
                                    </div>
                                    {error && (
                                        <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                                    )}
                                    <div className="flex gap-3 pt-2">
                                        <button type="button" onClick={() => { setCreditTarget(null); setCreditAmount(''); setError(null); }}
                                            className="flex-1 py-3 border-2 border-neutral-200 rounded-xl font-semibold text-sm text-neutral-600 hover:bg-neutral-50 transition dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                            Cancel
                                        </button>
                                        <button type="submit" disabled={actionLoading}
                                            className="flex-1 bg-secondary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-secondary-700 disabled:opacity-50 transition">
                                            {actionLoading ? 'Processing...' : parseFloat(creditAmount) < 0 ? 'Debit Account' : 'Credit Account'}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>

            {/* Reset Password Modal */}
            <Transition show={!!resetTarget}>
                <Dialog as="div" className="relative z-50" onClose={() => { setResetTarget(null); setNewPassword(''); setError(null); }}>
                    <TransitionChild
                        enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100"
                        leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
                        <div className="fixed inset-0 bg-black/40" />
                    </TransitionChild>
                    <div className="fixed inset-0 flex items-center justify-center p-4">
                        <TransitionChild
                            enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                            <DialogPanel className="w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 dark:bg-neutral-900 dark:border dark:border-neutral-800">
                                <DialogTitle className="text-lg font-bold text-neutral-900 mb-1 dark:text-neutral-200">
                                    Reset Password
                                </DialogTitle>
                                <p className="text-sm text-neutral-500 mb-4">
                                    Changing password for <strong className="text-neutral-700 dark:text-neutral-300">{resetTarget?.username}</strong>
                                </p>
                                <form onSubmit={async (e) => {
                                    e.preventDefault();
                                    setActionLoading(true);
                                    setError(null);
                                    try {
                                        await router.post(route('dealer.Sub-Agents.reset-password', resetTarget.id), {
                                            password: newPassword,
                                        }, {
                                            preserveState: true,
                                            preserveScroll: true,
                                            onSuccess: () => { setResetTarget(null); setNewPassword(''); },
                                            onError: (errs) => setError(Object.values(errs).flat().join(', ')),
                                        });
                                    } catch { setError('Something went wrong'); }
                                    finally { setActionLoading(false); }
                                }} className="space-y-4">
                                    <div className="relative">
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">New Password</label>
                                        <input type={showPassword ? 'text' : 'password'} value={newPassword}
                                            onChange={e => setNewPassword(e.target.value)}
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 pr-10 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                            minLength="6" maxLength="12" required />
                                        <button type="button" onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-3 top-[38px] text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                                            {showPassword ? (
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {error && (
                                        <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                                    )}
                                    <div className="flex gap-3 pt-2">
                                        <button type="button" onClick={() => { setResetTarget(null); setNewPassword(''); setError(null); }}
                                            className="flex-1 py-3 border-2 border-neutral-200 rounded-xl font-semibold text-sm text-neutral-600 hover:bg-neutral-50 transition dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                            Cancel
                                        </button>
                                        <button type="submit" disabled={actionLoading}
                                            className="flex-1 bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition">
                                            {actionLoading ? 'Resetting...' : 'Reset Password'}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>
        </AuthenticatedLayout>
    );
}
