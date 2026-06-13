import Checkbox from '@/Components/Checkbox';
import CourseSelectFields from '@/Components/Lecturer/CourseSelectFields';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

const selectClassName =
    'mt-1 block w-full rounded-md border-theme-line bg-theme-glass text-theme-strong shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export const EXAM_TYPES = [
    'Exam',
    'Quiz',
    'Assignment',
    'Mid-semester',
    'Class test',
];

export default function ExamForm({
    form,
    submitLabel,
    onSubmit,
    linkableCourses = [],
    /** When set (edit exam), show link to per-exam proctoring penalty rules. */
    examId = null,
}) {
    const { data, setData, processing, errors } = form;

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit();
            }}
            className="space-y-6"
        >
            <div>
                <InputLabel htmlFor="title" value="Title" />
                <TextInput
                    id="title"
                    className="mt-1 block w-full"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>

            <CourseSelectFields
                data={data}
                setData={setData}
                errors={errors}
                linkableCourses={linkableCourses}
                selectClassName={selectClassName}
                hintWhenList="Chooses the course label for this exam and links it to your course roster (for roster copy/sync). Options respect your role (admin: all courses; lecturer: yours; HoD: your department and any you own)."
                hintWhenEmpty="No courses are visible for your account under Courses &amp; rosters. Create one or ask an administrator."
            />

            <div>
                <InputLabel htmlFor="exam_type" value="Exam type" />
                <select
                    id="exam_type"
                    className={selectClassName}
                    value={data.exam_type}
                    onChange={(e) => setData('exam_type', e.target.value)}
                    required
                >
                    <option value="" disabled>
                        Select type
                    </option>
                    {EXAM_TYPES.map((t) => (
                        <option key={t} value={t}>
                            {t}
                        </option>
                    ))}
                </select>
                <InputError message={errors.exam_type} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor="proctoring_mode"
                    value="Proctoring (webcam, tab & fullscreen checks)"
                />
                <select
                    id="proctoring_mode"
                    className={selectClassName}
                    value={data.proctoring_mode ?? 'auto'}
                    onChange={(e) => setData('proctoring_mode', e.target.value)}
                >
                    <option value="auto">
                        Automatic — on for Exam / Quiz / Mid-semester; off for
                        other types
                    </option>
                    <option value="enabled">Always require proctoring</option>
                    <option value="disabled">Never require proctoring</option>
                </select>
                <p className="mt-1 text-xs text-theme-dim">
                    When required, students must allow camera and microphone and
                    complete the security steps before the paper appears.
                </p>
                <InputError message={errors.proctoring_mode} className="mt-2" />
            </div>

            <div className="rounded-lg border border-amber-500/35 bg-amber-950/25 p-4 text-sm text-theme-muted shadow-sm">
                <p className="font-semibold text-theme-strong">
                    Proctoring penalty rules (per exam)
                </p>
                <p className="mt-2 text-xs leading-relaxed text-theme-dim">
                    Set how many marks to deduct for each type of suspicious
                    activity (tab switch, face issues, etc.). Penalties apply
                    only after you review events — they are not auto-labelled as
                    cheating.
                </p>
                {examId ? (
                    <div className="mt-3">
                        <Link
                            href={route(
                                'lecturer.exams.proctoring.penalties.index',
                                examId,
                            )}
                            className="inline-flex items-center rounded-md border border-amber-600/50 bg-amber-900/30 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-amber-100 hover:bg-amber-900/50"
                        >
                            Configure penalty rules
                        </Link>
                    </div>
                ) : (
                    <p className="mt-3 text-xs text-amber-900/90 dark:text-amber-200/90">
                        Save this exam first. Then open{' '}
                        <strong>Edit exam</strong> for this paper — the penalty
                        settings link appears here and you can tune marks per
                        event type.
                    </p>
                )}
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="duration" value="Duration (minutes)" />
                    <TextInput
                        id="duration"
                        type="number"
                        min="0"
                        className="mt-1 block w-full"
                        value={data.duration}
                        onChange={(e) =>
                            setData('duration', Number(e.target.value))
                        }
                        required
                    />
                    <InputError message={errors.duration} className="mt-2" />
                </div>
                <div>
                    <InputLabel
                        htmlFor="attempts_allowed"
                        value="Attempts allowed"
                    />
                    <TextInput
                        id="attempts_allowed"
                        type="number"
                        min="1"
                        className="mt-1 block w-full"
                        value={data.attempts_allowed}
                        onChange={(e) =>
                            setData('attempts_allowed', Number(e.target.value))
                        }
                        required
                    />
                    <InputError
                        message={errors.attempts_allowed}
                        className="mt-2"
                    />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel
                        htmlFor="start_datetime"
                        value="Start (optional)"
                    />
                    <TextInput
                        id="start_datetime"
                        type="datetime-local"
                        className="mt-1 block w-full"
                        value={data.start_datetime}
                        onChange={(e) =>
                            setData('start_datetime', e.target.value)
                        }
                    />
                    <InputError
                        message={errors.start_datetime}
                        className="mt-2"
                    />
                </div>
                <div>
                    <InputLabel htmlFor="end_datetime" value="End (optional)" />
                    <TextInput
                        id="end_datetime"
                        type="datetime-local"
                        className="mt-1 block w-full"
                        value={data.end_datetime}
                        onChange={(e) =>
                            setData('end_datetime', e.target.value)
                        }
                    />
                    <InputError message={errors.end_datetime} className="mt-2" />
                </div>
            </div>

            <div>
                <InputLabel htmlFor="instructions" value="Instructions" />
                <textarea
                    id="instructions"
                    className="mt-1 block w-full rounded-md border-theme-line shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={4}
                    value={data.instructions}
                    onChange={(e) => setData('instructions', e.target.value)}
                />
                <InputError message={errors.instructions} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor="exam_code"
                    value="Exam code (leave blank to auto-generate on create)"
                />
                <TextInput
                    id="exam_code"
                    className="mt-1 block w-full"
                    value={data.exam_code}
                    onChange={(e) => setData('exam_code', e.target.value)}
                />
                <InputError message={errors.exam_code} className="mt-2" />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="shuffle_questions"
                    name="shuffle_questions"
                    checked={data.shuffle_questions}
                    onChange={(e) =>
                        setData('shuffle_questions', e.target.checked)
                    }
                />
                <InputLabel
                    htmlFor="shuffle_questions"
                    value="Shuffle questions for each attempt"
                />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="submission_password_enabled"
                    name="submission_password_enabled"
                    checked={data.submission_password_enabled}
                    onChange={(e) =>
                        setData(
                            'submission_password_enabled',
                            e.target.checked,
                        )
                    }
                />
                <InputLabel
                    htmlFor="submission_password_enabled"
                    value="Require submission password"
                />
            </div>

            {data.submission_password_enabled && (
                <div>
                    <InputLabel
                        htmlFor="submission_password"
                        value="Submission password"
                    />
                    <TextInput
                        id="submission_password"
                        type="password"
                        className="mt-1 block w-full"
                        value={data.submission_password}
                        onChange={(e) =>
                            setData('submission_password', e.target.value)
                        }
                    />
                    <InputError
                        message={errors.submission_password}
                        className="mt-2"
                    />
                </div>
            )}

            <div>
                <InputLabel
                    htmlFor="assessment_file"
                    value="Assessment file (PDF / Word, optional)"
                />
                <input
                    id="assessment_file"
                    type="file"
                    accept=".pdf,.doc,.docx"
                    className="mt-1 block w-full text-sm text-theme-dim"
                    onChange={(e) =>
                        setData('assessment_file', e.target.files[0] ?? null)
                    }
                />
                <InputError message={errors.assessment_file} className="mt-2" />
            </div>

            <div className="rounded-lg border border-sky-500/30 bg-sky-950/20 p-4 text-sm text-theme-muted shadow-sm">
                <p className="font-semibold text-theme-strong">
                    Student results (after release)
                </p>
                <p className="mt-2 text-xs leading-relaxed text-theme-dim">
                    When you release results, students who look up their attempt
                    can see the options below. Turn off any item you do not want
                    shared (for example, hiding model answers while keeping scores).
                </p>
                <div className="mt-4 space-y-3">
                    <div className="flex items-start gap-2">
                        <Checkbox
                            id="show_student_ai_feedback"
                            name="show_student_ai_feedback"
                            checked={!!data.show_student_ai_feedback}
                            onChange={(e) =>
                                setData('show_student_ai_feedback', e.target.checked)
                            }
                        />
                        <div>
                            <InputLabel
                                htmlFor="show_student_ai_feedback"
                                value="Show AI evaluation and concept feedback"
                            />
                            <p className="mt-0.5 text-xs text-theme-dim">
                                Includes explanations, confidence, detected and
                                missing concepts.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-start gap-2">
                        <Checkbox
                            id="show_student_expected_answers"
                            name="show_student_expected_answers"
                            checked={!!data.show_student_expected_answers}
                            onChange={(e) =>
                                setData(
                                    'show_student_expected_answers',
                                    e.target.checked,
                                )
                            }
                        />
                        <div>
                            <InputLabel
                                htmlFor="show_student_expected_answers"
                                value="Show expected / correct answers"
                            />
                            <p className="mt-0.5 text-xs text-theme-dim">
                                MCQ correct option, fill-in accepted wording, and
                                reference answers where stored.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-start gap-2">
                        <Checkbox
                            id="show_student_revision_suggestions"
                            name="show_student_revision_suggestions"
                            checked={!!data.show_student_revision_suggestions}
                            onChange={(e) =>
                                setData(
                                    'show_student_revision_suggestions',
                                    e.target.checked,
                                )
                            }
                        />
                        <div>
                            <InputLabel
                                htmlFor="show_student_revision_suggestions"
                                value="Show revision suggestions and AI revision plan"
                            />
                            <p className="mt-0.5 text-xs text-theme-dim">
                                Aggregated weak areas and access to the
                                personalized revision plan page.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex justify-end">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
            </div>
        </form>
    );
}
