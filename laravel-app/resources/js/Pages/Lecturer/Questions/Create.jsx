import LecturerLayout from '@/Layouts/LecturerLayout';
import QuestionForm from '@/Pages/Lecturer/Questions/QuestionForm';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ exam }) {
    const form = useForm({
        q_type: 'mcq',
        question_text: '',
        marks: 1,
        correct_option: '',
        option_a: '',
        option_b: '',
        option_c: '',
        option_d: '',
        option_e: '',
    });

    const submit = () => {
        form.post(route('lecturer.questions.store', exam.id));
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
                        Add question — {exam.title}
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
            <Head title="Add question" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <QuestionForm
                            form={form}
                            submitLabel="Save question"
                            onSubmit={submit}
                        />
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
