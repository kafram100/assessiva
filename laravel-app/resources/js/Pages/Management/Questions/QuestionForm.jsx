import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export const QUESTION_TYPES = [
    { value: 'mcq', label: 'Multiple choice (MCQ)' },
    { value: 'theory', label: 'Theory / essay' },
    { value: 'fill_in', label: 'Fill in the blank' },
    { value: 'file', label: 'File upload' },
];

export default function QuestionForm({ form, submitLabel, onSubmit }) {
    const { data, setData, processing, errors } = form;
    const isMcq = data.q_type === 'mcq';

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit();
            }}
            className="space-y-6"
        >
            <div>
                <InputLabel htmlFor="q_type" value="Question type" />
                <select
                    id="q_type"
                    className="mt-1 block w-full rounded-md border-theme-line shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value={data.q_type}
                    onChange={(e) => setData('q_type', e.target.value)}
                    required
                >
                    {QUESTION_TYPES.map((t) => (
                        <option key={t.value} value={t.value}>
                            {t.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.q_type} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="question_text" value="Question" />
                <textarea
                    id="question_text"
                    rows={5}
                    className="mt-1 block w-full rounded-md border-theme-line shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value={data.question_text}
                    onChange={(e) => setData('question_text', e.target.value)}
                    required
                />
                <InputError message={errors.question_text} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="marks" value="Marks" />
                <TextInput
                    id="marks"
                    type="number"
                    min="1"
                    className="mt-1 block w-full"
                    value={data.marks}
                    onChange={(e) =>
                        setData('marks', Number(e.target.value) || 1)
                    }
                    required
                />
                <InputError message={errors.marks} className="mt-2" />
            </div>

            {isMcq && (
                <div className="space-y-4 rounded-md border border-theme-line bg-theme-surface-soft/60 p-4">
                    <p className="text-sm text-theme-dim">
                        Options A–E (leave unused options empty). Correct answer
                        is a single letter A–E.
                    </p>
                    {['a', 'b', 'c', 'd', 'e'].map((letter) => (
                        <div key={letter}>
                            <InputLabel
                                htmlFor={`option_${letter}`}
                                value={`Option ${letter.toUpperCase()}`}
                            />
                            <TextInput
                                id={`option_${letter}`}
                                className="mt-1 block w-full"
                                value={data[`option_${letter}`] ?? ''}
                                onChange={(e) =>
                                    setData(`option_${letter}`, e.target.value)
                                }
                            />
                            <InputError
                                message={errors[`option_${letter}`]}
                                className="mt-2"
                            />
                        </div>
                    ))}
                </div>
            )}

            <div>
                <InputLabel
                    htmlFor="correct_option"
                    value={
                        isMcq
                            ? 'Correct option (A–E)'
                            : 'Model answer / expected response'
                    }
                />
                <TextInput
                    id="correct_option"
                    className="mt-1 block w-full"
                    value={data.correct_option}
                    onChange={(e) =>
                        setData('correct_option', e.target.value)
                    }
                />
                <InputError
                    message={errors.correct_option}
                    className="mt-2"
                />
            </div>

            <div className="flex justify-end">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
            </div>
        </form>
    );
}
