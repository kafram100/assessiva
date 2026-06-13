import Checkbox from '@/Components/Checkbox';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

const secondaryBtn =
    'inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted shadow-sm transition duration-150 ease-in-out hover:bg-theme-highlight/80 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-[#0d1117]';

export default function Show({ course, rosterPreview }) {
    const rosterForm = useForm({
        indexes_text: '',
        indexes_file: null,
        replace: false,
        clear_all: false,
    });

    const submitRoster = (e) => {
        e.preventDefault();
        rosterForm.post(route('lecturer.courses.roster.store', course.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                rosterForm.reset('indexes_text', 'indexes_file');
                rosterForm.setData('replace', false);
                rosterForm.setData('clear_all', false);
            },
        });
    };

    const destroyCourse = () => {
        if (
            !confirm(
                'Delete this course? You cannot delete it if exams are still linked.',
            )
        ) {
            return;
        }
        router.delete(route('lecturer.courses.destroy', course.id));
    };

    const preview = rosterPreview ?? [];
    const total = course.allowed_indexes_count ?? 0;

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            {course.name}
                        </h2>
                        <p className="text-sm text-theme-dim">
                            Code{' '}
                            <span className="font-mono font-semibold text-theme-strong">
                                {course.code}
                            </span>
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('lecturer.courses.edit', course.id)}
                            className={secondaryBtn}
                        >
                            Edit details
                        </Link>
                        <a
                            href={route(
                                'lecturer.courses.roster.export',
                                course.id,
                            )}
                            className={secondaryBtn}
                        >
                            Export roster CSV
                        </a>
                        <Link
                            href={route('lecturer.courses.index')}
                            className={secondaryBtn}
                        >
                            All courses
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={course.code} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <h3 className="text-lg font-medium text-theme-strong">
                            Class roster
                        </h3>
                        <p className="mt-1 text-sm text-theme-dim">
                            Allowed student index numbers for this course. One
                            index per line, or upload a CSV with a{' '}
                            <span className="font-mono text-theme-muted">
                                student_index
                            </span>{' '}
                            column (see template).
                        </p>
                        <p className="mt-2 text-sm text-theme-muted">
                            Total on roster:{' '}
                            <strong>{total}</strong>
                        </p>

                        <form
                            onSubmit={submitRoster}
                            className="mt-6 space-y-4"
                            encType="multipart/form-data"
                        >
                            <div>
                                <InputLabel
                                    htmlFor="indexes_text"
                                    value="Index numbers (paste or type)"
                                />
                                <textarea
                                    id="indexes_text"
                                    rows={6}
                                    className="mt-1 block w-full rounded-md border-theme-line bg-theme-elevated font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={rosterForm.data.indexes_text}
                                    onChange={(e) =>
                                        rosterForm.setData(
                                            'indexes_text',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={rosterForm.errors.indexes_text}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="indexes_file"
                                    value="CSV / text file (optional)"
                                />
                                <input
                                    id="indexes_file"
                                    type="file"
                                    accept=".csv,.txt,text/csv,text/plain"
                                    className="mt-1 block w-full text-sm text-theme-dim"
                                    onChange={(e) =>
                                        rosterForm.setData(
                                            'indexes_file',
                                            e.target.files[0] ?? null,
                                        )
                                    }
                                />
                                <InputError
                                    message={rosterForm.errors.indexes_file}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <label className="flex items-center gap-2 text-sm text-theme-muted">
                                    <Checkbox
                                        checked={rosterForm.data.replace}
                                        onChange={(e) =>
                                            rosterForm.setData(
                                                'replace',
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Replace entire roster (clear existing
                                    first)
                                </label>
                                <label className="flex items-center gap-2 text-sm text-theme-muted">
                                    <Checkbox
                                        checked={rosterForm.data.clear_all}
                                        onChange={(e) =>
                                            rosterForm.setData(
                                                'clear_all',
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Clear all index numbers
                                </label>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <PrimaryButton disabled={rosterForm.processing}>
                                    Apply roster
                                </PrimaryButton>
                                <a
                                    href={route(
                                        'lecturer.courses.roster.template',
                                    )}
                                    className={secondaryBtn}
                                >
                                    Download CSV template
                                </a>
                            </div>
                        </form>

                        {preview.length > 0 && (
                            <div className="mt-6">
                                <h4 className="text-sm font-semibold text-theme-muted">
                                    Preview (first {preview.length}
                                    {total > preview.length
                                        ? ` of ${total}`
                                        : ''}
                                    )
                                </h4>
                                <ul className="mt-2 max-h-40 overflow-y-auto font-mono text-xs text-theme-dim">
                                    {preview.map((idx) => (
                                        <li key={idx}>{idx}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>

                    <div className="rounded-lg border border-red-900/50 bg-red-950/20 p-4">
                        <DangerButton type="button" onClick={destroyCourse}>
                            Delete course
                        </DangerButton>
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
