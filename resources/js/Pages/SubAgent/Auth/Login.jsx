import { Head, useForm, Link } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
    });
    const [showPassword, setShowPassword] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('Sub-Agent.login'));
    };

    return (
        <GuestLayout>
            <Head title="Sub-Agent Login" />

            <div className="text-center mb-8">
                <div className="w-16 h-16 bg-gradient-to-br from-primary-500 to-tertiary-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-primary-500/20 rotate-3 transition-transform hover:rotate-6">
                    <span className="text-white font-extrabold text-2xl tracking-tighter">SA</span>
                </div>
                <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">Sub-Agent Login</h1>
                <p className="text-sm text-neutral-500 mt-1 dark:text-neutral-400">Welcome back, partner.</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-5">
                <div>
                    <InputLabel htmlFor="username" value="Username or Email" />
                    <TextInput
                        id="username"
                        type="text"
                        value={data.username}
                        onChange={e => setData('username', e.target.value)}
                        className="mt-1 block w-full"
                        placeholder="Enter username or email"
                        autoFocus
                        required
                    />
                    <InputError message={errors.username} className="mt-2" />
                </div>

                <div className="relative">
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type={showPassword ? 'text' : 'password'}
                        value={data.password}
                        onChange={e => setData('password', e.target.value)}
                        className="mt-1 block w-full pr-10"
                        placeholder="Enter password"
                        required
                        minLength={6}
                        maxLength={12}
                    />
                    <button type="button" onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-[38px] text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors">
                        {showPassword ? (
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        ) : (
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        )}
                    </button>
                    <InputError message={errors.password} className="mt-2" />
                </div>

                {errors.error && (
                    <div className="flex items-center gap-2 text-red-600 text-sm font-medium bg-red-50 border border-red-100 rounded-xl px-4 py-3 dark:text-red-400 dark:bg-red-900/20 dark:border-red-900/30 animate-in shake">
                        <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {errors.error}
                    </div>
                )}

                <div className="pt-2">
                    <PrimaryButton className="w-full" disabled={processing}>
                        {processing ? 'Signing in...' : 'Sign In'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
