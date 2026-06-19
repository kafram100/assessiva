import AdminLayout from '@/Layouts/AdminLayout';
import QuestionForm from '@/Pages/Management/Questions/QuestionForm';
import { Head, Link, useForm } from '@inertiajs/react';

function qTypeValue(q) {
    if (typeof q === 'string') {
        return q;
    }
    if (q && typeof q === 'object' && 'value' in q) {
        return q.value;
    }
    return 'mcq';
}

export default function Edit({ exam, question }) {
    const form = useForm({
        q_type: qTypeValue(question.q_type),
        question_text: question.question_text ?? '',
        marks: question.marks ?? 1,
        correct_option: question.correct_option ?? '',
        option_a: question.option_a ?? '',
        option_b: question.option_b ?? '',
        option_c: question.option_c ?? '',
        option_d: question.option_d ?? '',
        option_e: question.option_e ?? '',
    });

    const submit = () => {
        form.put(route('admin.management.questions.update', question.id));
    };

    const backHref = exam.practice_mode
        ? route('admin.management.practice-quizzes.edit', exam.id)
        : route('admin.management.exams.show', exam.id);
    const backLabel = exam.practice_mode ? 'Back to practice quiz' : 'Back to exam';

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Edit question - {exam.title}
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
            <Head title="Edit question" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <QuestionForm
                            form={form}
                            submitLabel="Update question"
                            onSubmit={submit}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
