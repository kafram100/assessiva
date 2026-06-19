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
                <div>
                    <h1 className="text-lg font-semibold text-theme-strong">
                        Student results
                    </h1>
                    <p className="mt-1 text-sm text-theme-dim">
                        Enter your exam code and index number. After your lecturer
                        releases results, you can open your AI revision plan from
                        the next screen.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <InputLabel value="Exam code" htmlFor="exam_code" />
                        <TextInput
                            id="exam_code"
                            className="mt-1 block w-full"
                            value={form.data.exam_code}
                            onChange={(e) =>
                                form.setData(
                                    'exam_code',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            autoComplete="off"
                            required
                        />
                        <InputError
                            message={form.errors.exam_code}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel value="Index number" htmlFor="student_index" />
                        <TextInput
                            id="student_index"
                            className="mt-1 block w-full"
                            value={form.data.student_index}
                            onChange={(e) =>
                                form.setData('student_index', e.target.value)
                            }
                            autoComplete="off"
                            required
                        />
                        <InputError
                            message={form.errors.student_index}
                            className="mt-2"
                        />
                    </div>
                    <PrimaryButton disabled={form.processing}>
                        Look up results
                    </PrimaryButton>
                </form>

                <p className="text-center text-sm text-theme-dim">
                    <a
                        href={studentEntryHref()}
                        className="font-medium text-sky-400 underline-offset-2 hover:text-sky-300 hover:underline"
                    >
                        Back to exam entry
                    </a>
                    <span className="mx-2 text-theme-dim">·</span>
                    <a
                        href={`${studentEntryHref()}?action=results`}
                        className="text-sky-400/90 underline-offset-2 hover:text-sky-300 hover:underline"
                    >
                        Classic results entry (same exam)
                    </a>
                </p>
            </div>
        </GuestLayout>
    );
}
