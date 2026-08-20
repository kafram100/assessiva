import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import Modal from '@/Components/Modal';

export default function EditModal({ show, onClose, question, courses }) {
    const { data, setData, put, processing, errors, reset, clearErrors } = useForm({
        course_id: '',
        topic: '',
        level: '',
        question_text: '',
        question_type: 'mcq',
        difficulty: 'medium',
        correct_answer: '',
        model_answer: '',
        options: [
            { key: 'A', text: '', is_correct: false },
            { key: 'B', text: '', is_correct: false },
            { key: 'C', text: '', is_correct: false },
            { key: 'D', text: '', is_correct: false }
        ],
    });

    // Sync form data when the question prop changes
    useEffect(() => {
        if (question) {
            setData({
                course_id: question.course_id || '',
                topic: question.topic || '',
                level: question.level || '',
                question_text: question.question_text || '',
                question_type: question.question_type || 'mcq',
                difficulty: question.difficulty || 'medium',
                correct_answer: question.correct_answer || '',
                model_answer: question.model_answer || '',
                options: question.options && question.options.length > 0 
                    ? question.options.map(o => ({
                        key: o.option_key,
                        text: o.option_text,
                        is_correct: !!o.is_correct
                    }))
                    : [
                        { key: 'A', text: '', is_correct: false },
                        { key: 'B', text: '', is_correct: false },
                        { key: 'C', text: '', is_correct: false },
                        { key: 'D', text: '', is_correct: false }
                    ],
            });
        } else {
            reset();
            clearErrors();
        }
    }, [question]);

    const actualSubmit = (e) => {
        e.preventDefault();
        if (!question) return;
        
        put(route('admin.question-bank-catalog.update', question.id), {
            onSuccess: () => {
                onClose();
            },
            preserveScroll: true,
        });
    };

    const updateOption = (index, field, value) => {
        const newOptions = [...data.options];
        if (field === 'is_correct' && value === true) {
            newOptions.forEach(o => o.is_correct = false); // only one can be correct
            newOptions[index][field] = value;
            setData(prev => ({
                ...prev,
                options: newOptions,
                correct_answer: newOptions[index].key
            }));
        } else {
            newOptions[index][field] = value;
            setData('options', newOptions);
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
            <div className="p-6 sm:p-8">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong truncate">
                        {question ? `Edit Question #${question.id}` : 'Edit Question'}
                    </h2>
                    <button onClick={onClose} className="text-ai-muted hover:text-theme-strong">
                        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form onSubmit={actualSubmit} className="space-y-6">
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-theme-strong">Course</label>
                            <select
                                value={data.course_id}
                                onChange={e => setData('course_id', e.target.value)}
                                className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-2.5 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent"
                                required
                            >
                                <option value="">Select Course...</option>
                                {courses?.map(c => (
                                    <option key={c.id} value={c.id}>{c.code} - {c.name}</option>
                                ))}
                            </select>
                            {errors.course_id && <p className="mt-1 text-sm text-red-400">{errors.course_id}</p>}
                        </div>
                        
                        <div>
                            <label className="mb-1 block text-sm font-medium text-theme-strong">Topic</label>
                            <input
                                type="text"
                                value={data.topic}
                                onChange={e => setData('topic', e.target.value)}
                                className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-2.5 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent"
                                placeholder="e.g. Kinematics"
                            />
                            {errors.topic && <p className="mt-1 text-sm text-red-400">{errors.topic}</p>}
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-theme-strong">Question Type</label>
                            <select
                                value={data.question_type}
                                onChange={e => setData('question_type', e.target.value)}
                                className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-2.5 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent"
                            >
                                <option value="mcq">Multiple Choice</option>
                                <option value="fill_in">Fill in the blank</option>
                                <option value="short_answer">Short Answer</option>
                                <option value="theory">Theory</option>
                            </select>
                        </div>
                        
                        <div>
                            <label className="mb-1 block text-sm font-medium text-theme-strong">Difficulty</label>
                            <select
                                value={data.difficulty}
                                onChange={e => setData('difficulty', e.target.value)}
                                className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-2.5 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent"
                            >
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </div>
                        
                        <div>
                            <label className="mb-1 block text-sm font-medium text-theme-strong">Level</label>
                            <input
                                type="text"
                                value={data.level}
                                onChange={e => setData('level', e.target.value)}
                                className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-2.5 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent"
                                placeholder="e.g. 100"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-theme-strong">Question Text</label>
                        <textarea
                            value={data.question_text}
                            onChange={e => setData('question_text', e.target.value)}
                            className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-3 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent min-h-[120px]"
                            required
                        />
                        {errors.question_text && <p className="mt-1 text-sm text-red-400">{errors.question_text}</p>}
                    </div>

                    {data.question_type === 'mcq' && (
                        <div className="space-y-4 border-t border-theme-line pt-6">
                            <h3 className="text-sm font-semibold text-theme-strong">Options</h3>
                            
                            <div className="space-y-3">
                                {data.options.map((opt, idx) => (
                                    <div key={idx} className="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            name="modal_correct_option"
                                            checked={opt.is_correct}
                                            onChange={() => updateOption(idx, 'is_correct', true)}
                                            className="h-4 w-4 border-theme-line bg-ai-navy-900/50 text-ai-accent focus:ring-ai-accent cursor-pointer"
                                        />
                                        <input
                                            type="text"
                                            value={opt.key}
                                            onChange={e => updateOption(idx, 'key', e.target.value)}
                                            className="w-12 rounded-lg border border-theme-line bg-ai-navy-900/50 p-2 text-center text-sm font-bold text-theme-strong uppercase"
                                            placeholder="A"
                                            maxLength={1}
                                        />
                                        <input
                                            type="text"
                                            value={opt.text}
                                            onChange={e => updateOption(idx, 'text', e.target.value)}
                                            className={`w-full rounded-lg border bg-ai-navy-900/50 p-2.5 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent ${opt.is_correct ? 'border-ai-accent/50 bg-ai-accent/5' : 'border-theme-line'}`}
                                            placeholder="Option text..."
                                        />
                                    </div>
                                ))}
                            </div>
                            <p className="text-xs text-ai-soft mt-2">Select the radio button next to the correct answer.</p>
                        </div>
                    )}

                    {data.question_type !== 'mcq' && (
                        <div className="space-y-4 border-t border-theme-line pt-6">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-theme-strong">Suggested Answer / Correct Answer</label>
                                <textarea
                                    value={data.model_answer}
                                    onChange={e => {
                                        setData('model_answer', e.target.value);
                                        setData('correct_answer', e.target.value);
                                    }}
                                    className="w-full rounded-lg border border-theme-line bg-ai-navy-900/50 p-3 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent min-h-[100px]"
                                    placeholder="Enter the suggested or model answer here..."
                                />
                            </div>
                        </div>
                    )}

                    <div className="flex justify-end gap-3 pt-4 border-t border-theme-line mt-6">
                        <button
                            type="button"
                            onClick={onClose}
                            className="btn-ai-secondary"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-ai-primary"
                        >
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
