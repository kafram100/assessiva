import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

/** Full document navigation - Blade student routes are not Inertia pages. */
const studentEntryHref = () => route('student.entry');

export default function Lookup({ prefill = { exam_code: '', student_index: '' } }) {
    const form = useForm({
        exam_code: prefill.exam_code || '',
        student_index: prefill.student_index || '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('student.results.lookup'));
    };

    return (
        <GuestLayout>
            <Head title="Look up results" />
            <div className="space-y-6">
                <div className="text-center">
                    <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                        Student Results
                    </h1>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Enter your exam code and index number. After your lecturer
                        releases results, you can open your AI revision plan from
                        the next screen.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <InputLabel value="Exam code" htmlFor="exam_code" className="text-xs font-medium" />
                        <TextInput
                            id="exam_code"
                            className="mt-1 block w-full text-sm"
                            value={form.data.exam_code}
                            onChange={(e) =>
                                form.setData(
                                    'exam_code',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            placeholder="e.g. CS101"
                            autoComplete="off"
                            required
                        />
                        <InputError
                            message={form.errors.exam_code}
                            className="mt-1 text-xs"
                        />
                    </div>
                    <div>
                        <InputLabel value="Index number" htmlFor="student_index" className="text-xs font-medium" />
                        <TextInput
                            id="student_index"
                            className="mt-1 block w-full text-sm"
                            value={form.data.student_index}
                            onChange={(e) =>
                                form.setData('student_index', e.target.value)
                            }
                            placeholder="e.g. 10293847"
                            autoComplete="off"
                            required
                        />
                        <InputError
                            message={form.errors.student_index}
                            className="mt-1 text-xs"
                        />
                    </div>
                    <div className="pt-2">
                        <PrimaryButton
                            className="w-full justify-center py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all"
                            disabled={form.processing}
                        >
                            {form.processing ? 'Looking up...' : 'Look up results'}
                        </PrimaryButton>
                    </div>
                </form>

                <p className="text-center text-xs text-slate-500 dark:text-slate-400">
                    <a
                        href={studentEntryHref()}
                        className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 underline underline-offset-2 transition-colors"
                    >
                        Back to exam entry
                    </a>
                    <span className="mx-2 text-slate-400 dark:text-slate-600">·</span>
                    <a
                        href={`${studentEntryHref()}?action=results`}
                        className="text-indigo-600/90 hover:text-indigo-500 dark:text-indigo-400/90 underline underline-offset-2 transition-colors"
                    >
                        Classic results entry
                    </a>
                </p>
            </div>
        </GuestLayout>
    );
}
