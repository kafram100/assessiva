import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';

export default function QuizPlayer({ practiceSession, questions, onSubmit, processing }) {
    const [data, setData] = useState({ responses: {} });
    const [currentIndex, setCurrentIndex] = useState(0);

    const question = questions[currentIndex];

    const handleResponse = (val) => {
        setData(prev => ({
            ...prev,
            responses: { ...prev.responses, [question.id]: val }
        }));
    };

    const [showSubmitModal, setShowSubmitModal] = useState(false);

    const submitPractice = () => {
        setShowSubmitModal(true);
    };

    const confirmSubmit = () => {
        setShowSubmitModal(false);
        onSubmit(data.responses);
    };

    return (
        <div 
            className="mx-auto w-full select-none"
            onCopy={(e) => e.preventDefault()}
            onCut={(e) => e.preventDefault()}
            onContextMenu={(e) => e.preventDefault()}
            onKeyDown={(e) => {
                if (e.ctrlKey && ['c', 'v', 'p', 's', 'x'].includes(e.key.toLowerCase())) {
                    e.preventDefault();
                }
            }}
        >
            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h1 className="text-lg sm:text-xl font-bold text-theme-strong">Practice Session {practiceSession?.id}</h1>
                <div className="text-sm font-medium text-theme-dim">
                    Question {currentIndex + 1} of {questions.length}
                </div>
            </div>

            {question && (
                <div className="rounded-2xl border border-theme-line bg-theme-elevated p-4 sm:p-6 shadow-sm">
                    <div className="mb-4 text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        {question.q_type.replace('_', ' ')}
                    </div>
                    <div
                        className="prose prose-sm prose-invert mb-6 sm:mb-8 max-w-none text-theme-strong"
                        dangerouslySetInnerHTML={{ __html: question.question_text }}
                    />

                    {question.q_type === 'mcq' && (
                        <div className="space-y-2 sm:space-y-3">
                            {['a', 'b', 'c', 'd', 'e'].map((opt) => {
                                const text = question[`option_${opt}`];
                                if (!text) return null;
                                const isSelected = data.responses[question.id] === opt.toUpperCase();
                                
                                return (
                                    <button
                                        key={opt}
                                        onClick={() => handleResponse(opt.toUpperCase())}
                                        className={`w-full rounded-xl border p-3 sm:p-4 text-left transition ${
                                            isSelected
                                                ? 'border-indigo-500 bg-indigo-500/10 text-indigo-700 dark:text-indigo-200'
                                                : 'border-theme-line bg-theme-surface hover:border-theme-dim'
                                        }`}
                                    >
                                        <div className="flex gap-3 sm:gap-4">
                                            <div className="font-bold uppercase text-theme-dim shrink-0">{opt}</div>
                                            <div className="text-theme-strong text-sm sm:text-base">{text}</div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    {(question.q_type === 'theory' || question.q_type === 'short_answer') && (
                        <div>
                            <textarea
                                className="h-32 w-full rounded-xl border border-theme-line bg-theme-surface p-4 text-theme-strong focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                placeholder="Type your answer here..."
                                value={data.responses[question.id] || ''}
                                onChange={(e) => handleResponse(e.target.value)}
                            />
                        </div>
                    )}

                    {question.q_type === 'fill_in' && (
                        <div>
                            <input
                                type="text"
                                className="w-full rounded-xl border border-theme-line bg-theme-surface p-4 text-theme-strong focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                placeholder="Type your brief answer..."
                                value={data.responses[question.id] || ''}
                                onChange={(e) => handleResponse(e.target.value)}
                            />
                        </div>
                    )}
                </div>
            )}

            <div className="mt-6 sm:mt-8 flex justify-between gap-2">
                <button
                    onClick={() => setCurrentIndex((c) => Math.max(0, c - 1))}
                    disabled={currentIndex === 0}
                    className="rounded-xl border border-theme-line px-4 sm:px-6 py-2 font-medium text-theme-muted hover:bg-white/[0.04] disabled:opacity-50 text-sm sm:text-base"
                >
                    Previous
                </button>
                
                {currentIndex === questions.length - 1 ? (
                    <button
                        onClick={submitPractice}
                        disabled={processing}
                        className="rounded-xl bg-indigo-600 px-4 sm:px-8 py-2 font-semibold text-white hover:bg-indigo-700 shadow-md shadow-indigo-600/20 disabled:opacity-50 flex items-center justify-center min-w-[120px] sm:min-w-[150px] text-sm sm:text-base"
                    >
                        {processing ? (
                            <div className="flex items-center gap-2">
                                <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Submitting...</span>
                            </div>
                        ) : 'Submit Practice'}
                    </button>
                ) : (
                    <button
                        onClick={() => setCurrentIndex((c) => Math.min(questions.length - 1, c + 1))}
                        className="rounded-xl bg-theme-surface px-4 sm:px-6 py-2 font-medium text-theme-strong hover:bg-theme-line text-sm sm:text-base"
                    >
                        Next
                    </button>
                )}
            </div>
            
            {/* Progress Indicators */}
            <div className="mt-8 sm:mt-12 flex flex-wrap gap-1.5 sm:gap-2">
                {questions.map((q, i) => {
                    const hasAnswered = !!data.responses[q.id];
                    const isCurrent = i === currentIndex;
                    return (
                        <button
                            key={q.id}
                            onClick={() => setCurrentIndex(i)}
                            className={`flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg text-xs font-semibold transition ${
                                isCurrent
                                    ? 'border-2 border-indigo-500 bg-theme-elevated text-indigo-600 dark:text-indigo-400'
                                    : hasAnswered
                                    ? 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300'
                                    : 'bg-theme-surface text-theme-dim hover:bg-theme-line'
                            }`}
                        >
                            {i + 1}
                        </button>
                    );
                })}
            </div>

            <Modal show={showSubmitModal} onClose={() => setShowSubmitModal(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-theme-strong">
                        Submit Practice Session?
                    </h2>
                    <p className="mt-1 text-sm text-theme-dim">
                        Are you sure you want to submit your responses? You won't be able to change them afterwards.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setShowSubmitModal(false)}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton onClick={confirmSubmit} disabled={processing}>
                            {processing ? 'Submitting...' : 'Yes, Submit'}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
