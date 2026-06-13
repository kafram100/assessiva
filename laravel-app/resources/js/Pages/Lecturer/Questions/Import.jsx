import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Import({ exam }) {
    const form = useForm({
        csv_file: null,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('lecturer.questions.import.store', exam.id), {
            forceFormData: true,
        });
    };

    const backHref = exam.practice_mode
        ? route('lecturer.practice-quizzes.edit', exam.id)
        : route('lecturer.exams.show', exam.id);
    const backLabel = exam.practice_mode ? 'Back to practice quiz' : 'Back to exam';

    return (
        <LecturerLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Import questions — {exam.title}
                    </h2>
                    <Link
                        href={backHref}
                        className="text-sm text-theme-dim underline hover:text-theme-strong"
                    >
                        {backLabel}
                    </Link>
                </div>
            }
        >
            <Head title="Import questions" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-md border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
                        <p className="font-medium">CSV format</p>
                        <p className="mt-1">
                            Columns: question_type, question_text, option_a,
                            option_b, option_c, option_d, option_e,
                            correct_answer, marks. First row is a header and
                            will be skipped.
                        </p>
                        <a
                            href={route('lecturer.questions.template')}
                            className="mt-2 inline-block font-medium text-indigo-700 underline"
                        >
                            Download template CSV
                        </a>
                    </div>

                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel
                                    htmlFor="csv_file"
                                    value="CSV file (.csv or .txt)"
                                />
                                <input
                                    id="csv_file"
                                    type="file"
                                    accept=".csv,.txt"
                                    className="mt-1 block w-full text-sm text-theme-dim"
                                    onChange={(e) =>
                                        form.setData(
                                            'csv_file',
                                            e.target.files[0] ?? null,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.csv_file}
                                    className="mt-2"
                                />
                            </div>
                            <div className="flex justify-end">
                                <PrimaryButton
                                    disabled={form.processing}
                                >
                                    Import
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
