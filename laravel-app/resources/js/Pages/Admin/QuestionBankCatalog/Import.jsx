import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const LEVEL_OPTIONS = {
    JHS: ['JHS 1', 'JHS 2', 'JHS 3'],
    SHS: ['SHS 1', 'SHS 2', 'SHS 3'],
    Tertiary: ['Level 100', 'Level 200', 'Level 300', 'Level 400', 'Graduate'],
};

const EDU_LABELS = {
    JHS: 'Junior High School',
    SHS: 'Senior High School',
    Tertiary: 'University / College',
};

export default function Import({ courses }) {
    const form = useForm({
        course_name: '',
        level: '',
        topic: '',
        csv_file: null,
    });

    const [eduLevel, setEduLevel] = useState('');

    const handleEduLevel = (cat) => {
        setEduLevel(cat);
        form.setData('level', '');
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.question-bank-catalog.import.store'), {
            forceFormData: true,
        });
    };

    return (
        <AdminLayout
            header={
                <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
                    Import Questions to Bank
                </h2>
            }
        >
            <Head title="Import questions" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

                    {/* Back link */}
                    <Link
                        href={route('admin.question-bank-catalog.index')}
                        className="inline-flex items-center gap-1.5 text-sm text-theme-dim hover:text-theme-strong transition-colors"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to catalog
                    </Link>

                    {/* Info banner */}
                    <div className="rounded-md border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="font-medium">CSV format</p>
                                <p className="mt-1">
                                    Columns: question_type, question_text, option_a,
                                    option_b, option_c, option_d, option_e,
                                    correct_answer, marks. First row is a header and
                                    will be skipped.
                                </p>
                            </div>
                            <a
                                href={route('admin.question-bank-catalog.import.template')}
                                className="inline-flex shrink-0 items-center gap-2 rounded-md border border-indigo-200 bg-white px-3 py-2 text-sm font-medium text-indigo-700 shadow-sm hover:bg-indigo-50"
                                download
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Template
                            </a>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">

                            {/* Course Name */}
                            <div>
                                <InputLabel htmlFor="course_name" value="Course Name" />
                                <input
                                    id="course_name"
                                    list="course_list"
                                    className="mt-1 block w-full rounded-md border border-theme-line bg-white/[0.04] p-2 text-sm text-theme-strong"
                                    value={form.data.course_name}
                                    onChange={(e) => form.setData('course_name', e.target.value)}
                                    placeholder="Enter or select a course"
                                    required
                                />
                                <datalist id="course_list">
                                    {courses.map((c) => (
                                        <option key={c.id} value={c.name} />
                                    ))}
                                </datalist>
                                <InputError message={form.errors.course_name} className="mt-2" />
                            </div>

                            {/* Educational Level — two-tier selector */}
                            <div>
                                <InputLabel value="Educational Level" />

                                {/* Step 1: category cards */}
                                <div className="mt-2 grid grid-cols-3 gap-2">
                                    {Object.keys(LEVEL_OPTIONS).map((cat) => (
                                        <button
                                            key={cat}
                                            type="button"
                                            onClick={() => handleEduLevel(cat)}
                                            className={`rounded-xl border-2 px-2 py-3 text-center transition-all ${
                                                eduLevel === cat
                                                    ? 'border-ai-accent bg-ai-accent/10 font-semibold text-ai-accent shadow-sm'
                                                    : 'border-theme-line bg-white/[0.04] text-theme-muted hover:border-ai-accent/40 hover:text-theme-strong'
                                            }`}
                                        >
                                            <div className="text-sm font-bold">{cat}</div>
                                            <div className="mt-0.5 text-[10px] leading-tight opacity-70">
                                                {EDU_LABELS[cat]}
                                            </div>
                                        </button>
                                    ))}
                                </div>

                                {/* Step 2: specific level buttons */}
                                {eduLevel && (
                                    <div className="mt-3">
                                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-ai-muted">
                                            Select specific {eduLevel} level
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {LEVEL_OPTIONS[eduLevel].map((lv) => (
                                                <button
                                                    key={lv}
                                                    type="button"
                                                    onClick={() => form.setData('level', lv)}
                                                    className={`rounded-lg border px-4 py-2 text-sm font-medium transition-all ${
                                                        form.data.level === lv
                                                            ? 'border-ai-accent bg-ai-accent text-white shadow-md'
                                                            : 'border-theme-line bg-white/[0.04] text-theme-muted hover:border-ai-accent/40 hover:text-theme-strong'
                                                    }`}
                                                >
                                                    {lv}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Selected confirmation */}
                                {form.data.level && (
                                    <p className="mt-2 text-xs text-ai-muted">
                                        Selected:{' '}
                                        <span className="font-semibold text-ai-accent">{form.data.level}</span>
                                    </p>
                                )}

                                <InputError message={form.errors.level} className="mt-2" />
                            </div>

                            {/* Topic */}
                            <div>
                                <InputLabel htmlFor="topic" value="Topic (Optional)" />
                                <input
                                    id="topic"
                                    type="text"
                                    className="mt-1 block w-full rounded-md border border-theme-line bg-white/[0.04] p-2 text-sm text-theme-strong"
                                    value={form.data.topic}
                                    onChange={(e) => form.setData('topic', e.target.value)}
                                    placeholder="E.g. Cell Biology"
                                />
                                <p className="mt-1 text-xs text-theme-dim">
                                    All questions in this CSV will be assigned to this topic.
                                </p>
                                <InputError message={form.errors.topic} className="mt-2" />
                            </div>

                            {/* CSV File */}
                            <div>
                                <InputLabel htmlFor="csv_file" value="CSV file (.csv or .txt)" />
                                <input
                                    id="csv_file"
                                    type="file"
                                    accept=".csv,.txt"
                                    className="mt-1 block w-full text-sm text-theme-dim"
                                    onChange={(e) => form.setData('csv_file', e.target.files[0] ?? null)}
                                    required
                                />
                                <InputError message={form.errors.csv_file} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    className="btn-ai-primary w-full sm:w-auto"
                                    disabled={form.processing}
                                >
                                    {form.processing ? 'Importing…' : 'Import Questions'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
