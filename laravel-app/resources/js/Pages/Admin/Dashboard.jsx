import AdminLayout from '@/Layouts/AdminLayout';
import ExportExcelButton from '@/Components/ExportExcelButton';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

function StatCard({ label, value, tone = 'sky' }) {
    const tones = {
        sky: 'text-sky-400',
        violet: 'text-violet-400',
        amber: 'text-amber-400',
        emerald: 'text-emerald-400',
        rose: 'text-rose-400',
        slate: 'text-theme-muted',
    };

    return (
        <div className="rounded-lg border border-theme-line bg-theme-elevated p-4 text-center">
            <div className={`text-2xl font-bold ${tones[tone] ?? tones.sky}`}>
                {value}
            </div>
            <div className="mt-1 text-xs text-theme-dim">{label}</div>
        </div>
    );
}

function RevenueCard({ label, current, history = [], tone = 'emerald' }) {
    const [isOpen, setIsOpen] = useState(false);
    
    const tones = {
        emerald: 'text-emerald-400',
    };

    const formatCurrency = (val) => 
        new Intl.NumberFormat('en-GH', { style: 'currency', currency: 'GHS' })
            .format(val || 0)
            .replace('GHS', 'GH₵'); // Force shorter symbol

    return (
        <div className="rounded-lg border border-theme-line bg-theme-elevated p-4 relative z-20">
            <div className="text-center">
                <div className={`text-2xl font-bold ${tones[tone] ?? tones.emerald}`}>
                    {formatCurrency(current)}
                </div>
                <div 
                    className="mt-1 text-xs text-theme-dim flex items-center justify-center gap-1 cursor-pointer hover:text-theme-strong transition-colors" 
                    onClick={() => setIsOpen(!isOpen)}
                >
                    {label}
                    <svg className={`w-3 h-3 transform transition-transform ${isOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
            {isOpen && history.length > 0 && (
                <div className="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 border border-theme-line rounded-lg shadow-xl p-2 text-sm text-theme-strong" style={{ zIndex: 100 }}>
                    <div className="space-y-1">
                        <div className="px-2 py-1 border-b border-theme-line mb-1 flex justify-between text-xs font-semibold text-theme-dim">
                            <span>Period</span>
                            <span>Revenue</span>
                        </div>
                        {history.map((item, idx) => (
                            <div key={idx} className="flex justify-between items-center px-2 py-1.5 hover:bg-theme-line/30 rounded-md transition-colors">
                                <span className="text-theme-dim text-xs">{item.label}</span>
                                <span className="font-medium text-xs">{formatCurrency(item.value)}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function ToolCard({ title, description, href, primaryHref, primaryLabel, secondaryHref, secondaryLabel }) {
    return (
        <div className="flex h-full flex-col rounded-lg border border-theme-line bg-theme-elevated p-5">
            <h3 className="text-base font-semibold text-theme-strong">{title}</h3>
            <p className="mt-2 flex-1 text-sm text-theme-dim">{description}</p>
            <div className="mt-4 flex flex-wrap gap-2">
                <Link
                    href={href}
                    className="btn-ai-primary !rounded-md !px-3 !py-2 !text-xs"
                >
                    {primaryLabel}
                </Link>
                {secondaryHref && (
                    <Link
                        href={secondaryHref}
                        className="btn-ai-primary !rounded-md !px-3 !py-2 !text-xs"
                    >
                        {secondaryLabel}
                    </Link>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({
    studentCount,
    questionCount,
    attemptCount,
    totalTokens,
    revenue = { 
        daily: { current: 0, history: [] }, 
        weekly: { current: 0, history: [] }, 
        monthly: { current: 0, history: [] }, 
        yearly: { current: 0, history: [] } 
    }
}) {
    const { auth } = usePage().props;
    const permissions = auth.admin_permissions || [];
    
    const hasPerm = (perm) => permissions.includes('*') || permissions.includes(perm);

    const canManageStudents = hasPerm('manage_students');
    const canManageQuestions = hasPerm('manage_questions');
    const canManageBilling = hasPerm('manage_billing');
    const canManageSettings = hasPerm('manage_settings');
    const canManageAdmins = hasPerm('manage_admins');

    return (
        <AdminLayout
            header={
                <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
                    Platform Overview
                </h2>
            }
        >
            <Head title="Admin Dashboard" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                    {canManageStudents && <StatCard label="Registered Users" value={studentCount} tone="sky" />}
                    {canManageQuestions && <StatCard label="AI Questions" value={questionCount} tone="violet" />}
                    {canManageStudents && <StatCard label="Practice Sessions" value={attemptCount} tone="emerald" />}
                    {canManageQuestions && <StatCard label="Tokens Available" value={totalTokens} tone="amber" />}
                </div>

                {canManageBilling && (
                    <div className="mb-8 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <RevenueCard label="Daily Revenue" current={revenue.daily.current} history={revenue.daily.history} tone="emerald" />
                        <RevenueCard label="Weekly Revenue" current={revenue.weekly.current} history={revenue.weekly.history} tone="emerald" />
                        <RevenueCard label="Monthly Revenue" current={revenue.monthly.current} history={revenue.monthly.history} tone="emerald" />
                        <RevenueCard label="Yearly Revenue" current={revenue.yearly.current} history={revenue.yearly.history} tone="emerald" />
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {canManageStudents && (
                        <ToolCard
                            title="All users"
                            description="View all registered users and their details."
                            href={route('admin.students')}
                            primaryLabel="View all users"
                        />
                    )}
                    {canManageQuestions && (
                        <ToolCard
                            title="Question Bank Catalog"
                            description="View the global pool of AI-generated questions."
                            href={route('admin.question-bank-catalog.index')}
                            primaryLabel="Browse Question Bank"
                        />
                    )}
                    {canManageBilling && (
                        <ToolCard
                            title="Billing settings"
                            description="Configure payment gateways (Paystack/Moolre) and token prices."
                            href={route('admin.billing-settings')}
                            primaryLabel="Configure billing"
                        />
                    )}
                    {canManageSettings && (
                        <ToolCard
                            title="Email settings"
                            description="Shared SMTP sender used when the platform sends system mail."
                            href={route('admin.email-settings')}
                            primaryLabel="Configure email"
                        />
                    )}
                    {canManageAdmins && (
                        <ToolCard
                            title="Support Admins"
                            description="Add and manage Support Admins and their specific permissions."
                            href={route('admin.admins.index')}
                            primaryLabel="Manage Support Admins"
                        />
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
