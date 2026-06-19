import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Import({ courses }) {
    const form = useForm({
        course_name: '',
        level: '',
        csv_file: null,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.question-bank-catalog.import.store'), {
            forceFormData: true,
        });
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Import Questions to Bank
                    </h2>
                    <Link
                        href={route('admin.question-bank-catalog.index')}
                        className="text-sm text-theme-dim underline hover:text-theme-strong"
                    >
                        Back to catalog
                    </Link>
                </div>
            }
        >
            <Head title="Import questions" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-md border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
                        <p className="font-medium">CSV format</p>
                        <p className="mt-1">
                            Columns: question_type, question_text, option_a,
                            option_b, option_c, option_d, option_e,
                            correct_answer, marks. First row is a header and
                            will be skipped.
                        </p>
                    </div>

                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="course_name" value="Course Name" />
                                <input
                                    id="course_name"
                                    list="course_list"
                                    className="mt-1 block w-full rounded-md border border-theme-line bg-white/[0.04] p-2 text-sm text-theme-strong"
                                    value={form.data.course_name}
                                    onChange={(e) =>
                                        form.setData('course_name', e.target.value)
                                    }
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

                            <div>
                                <InputLabel htmlFor="level" value="Level" />
                                <select
                                    id="level"
                                    className="mt-1 block w-full rounded-md border border-theme-line bg-white/[0.04] p-2 text-theme-strong"
                                    value={form.data.level}
                                    onChange={(e) =>
                                        form.setData('level', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Select a level</option>
                                    <option value="Year 1">Year 1</option>
                                    <option value="Year 2">Year 2</option>
                                    <option value="Year 3">Year 3</option>
                                    <option value="Year 4">Year 4</option>
                                </select>
                                <InputError message={form.errors.level} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="csv_file" value="CSV file (.csv or .txt)" />
                                <input
                                    id="csv_file"
                                    type="file"
                                    accept=".csv,.txt"
                                    className="mt-1 block w-full text-sm text-theme-dim"
                                    onChange={(e) =>
                                        form.setData('csv_file', e.target.files[0] ?? null)
                                    }
                                    required
                                />
                                <InputError message={form.errors.csv_file} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    className="btn-ai-primary"
                                    disabled={form.processing}
                                >
                                    {form.processing ? 'Importing...' : 'Import Questions'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
