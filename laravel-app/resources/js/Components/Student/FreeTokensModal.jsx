import React from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import { SparklesIcon, InformationCircleIcon } from '@heroicons/react/24/outline';

export default function FreeTokensModal({ show, onClose, freeBalance }) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="relative overflow-hidden bg-theme-base/50 p-4 sm:p-8 backdrop-blur-xl">
                {/* Decorative background elements */}
                <div className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-ai-accent/20 blur-3xl"></div>
                <div className="pointer-events-none absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-amber-400/20 blur-3xl"></div>

                <div className="relative text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-amber-600 shadow-lg shadow-amber-500/30">
                        <SparklesIcon className="h-8 w-8 text-white" />
                    </div>

                    <h2 className="mb-2 text-2xl font-bold tracking-tight text-theme-strong">
                        Welcome to Assessiva!
                    </h2>
                    
                    <p className="mb-6 text-sm text-theme-dim">
                        We're excited to have you on board. To help you get started, we've loaded your account with free questions!
                    </p>

                    <div className="mb-8 overflow-hidden rounded-2xl border border-theme-line bg-theme-highlight/50 p-4">
                        <div className="flex items-center justify-center gap-3">
                            <span className="text-4xl font-black text-amber-500">{freeBalance}</span>
                            <div className="text-left">
                                <div className="font-bold text-theme-strong">Free Questions</div>
                                <div className="text-xs text-theme-muted">Available right now</div>
                            </div>
                        </div>
                    </div>

                    <div className="mb-8 text-left text-sm text-theme-dim">
                        <h3 className="mb-2 flex items-center gap-1.5 font-semibold text-theme-strong">
                            <InformationCircleIcon className="h-4 w-4 text-ai-accent" />
                            How it works:
                        </h3>
                        <ul className="space-y-2">
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                                <span>Generate unlimited practice quizzes on any topic.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                                <span>Each question you generate uses 1 free token.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                                <span>After your free questions run out, you can top up tokens from your Wallet to keep practicing!</span>
                            </li>
                        </ul>
                    </div>

                    <div className="mt-6">
                        <PrimaryButton 
                            onClick={onClose}
                            className="w-full justify-center bg-gradient-to-r from-ai-accent to-ai-accent-deep py-3 text-base shadow-lg shadow-ai-accent/20 hover:shadow-ai-accent/40"
                        >
                            Got it, let's practice!
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
