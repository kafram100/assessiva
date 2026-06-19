import AdminLayout from '@/Layouts/AdminLayout';
import ExamForm from '@/Pages/Management/Exams/ExamForm';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ linkableCourses }) {
    const form = useForm({
        title: '',
        course_name: '',
        course_code: '',
        course_id: '',
        exam_type: 'Exam',
        proctoring_mode: 'auto',
        duration: 60,
        attempts_allowed: 1,
        instructions: '',
        start_datetime: '',
        end_datetime: '',
        exam_code: '',
        shuffle_questions: false,
        submission_password_enabled: false,
        submission_password: '',
        show_student_ai_feedback: true,
        show_student_expected_answers: true,
        show_student_revision_suggestions: true,
        assessment_file: null,
    });

    const submit = () => {
        form.post(route('admin.management.exams.store'), {
            forceFormData: !!form.data.assessment_file,
        });
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Create exam
                    </h2>
                    <Link
                        href={route('admin.management.exams.index')}
                        className="text-sm text-theme-dim underline hover:text-theme-strong"
                    >
                        Back to exams
                    </Link>
                </div>
            }
        >
            <Head title="Create exam" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="border border-theme-line bg-theme-glass p-6 shadow sm:rounded-lg">
                        <ExamForm
                            form={form}
                            submitLabel="Create exam"
                            onSubmit={submit}
                            linkableCourses={linkableCourses}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
