import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const currentPath = window.location.pathname;

    let loginRoute = 'login';
    if (currentPath.startsWith('/agent/')) {
        loginRoute = 'agent.login';
    } else if (currentPath.startsWith('/dealer/')) {
        loginRoute = 'dealer.login';
    }

    const submit = (e) => {
        e.preventDefault();

        post(route(loginRoute), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Agent / Dealer Login - AirLinks" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
                    {status}
                </div>
            )}

            <div className="text-center mb-2">
                <h2 className="text-xl font-bold text-neutral-900 dark:text-white">Agent / Dealer Login</h2>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Sign in to your agent or dealer dashboard</p>
            </div>
            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email, Username, or Phone" />

                    <TextInput
                        id="email"
                        type="text"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <div className="relative mt-1">
                        <TextInput
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className="block w-full pr-10"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                            minLength={6}
                            maxLength={12}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition"
                            tabIndex={-1}
                        >
                            {showPassword ? (
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            ) : (
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            )}
                        </button>
                    </div>

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-neutral-600 dark:text-neutral-400">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-6 flex items-center justify-between">
                    <Link
                        href={route('register')}
                        className="text-sm text-primary-600 font-medium hover:text-primary-700 underline"
                    >
                        Create Account
                    </Link>

                    <div className="flex items-center gap-3">
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="rounded-md text-sm text-neutral-600 dark:text-neutral-400 underline hover:text-neutral-900 dark:hover:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                Forgot password?
                            </Link>
                        )}

                        <PrimaryButton className="ms-4" disabled={processing}>
                            Login
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
