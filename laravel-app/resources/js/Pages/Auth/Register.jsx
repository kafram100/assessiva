import { useEffect, useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const [step, setStep] = useState(1);
    const [showPasswords, setShowPasswords] = useState(false);
    const [localErrors, setLocalErrors] = useState({});
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        educational_level: '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        return () => {
            reset('password', 'password_confirmation');
        };
    }, []);

    // If backend validation returns errors for fields on step 1, jump back so the user can see them.
    useEffect(() => {
        if (errors.name || errors.email || errors.phone || errors.educational_level) {
            setStep(1);
        }
    }, [errors]);

    const validateField = (field, value) => {
        let error = '';
        switch (field) {
            case 'name':
                if (value && !/^[a-zA-Z\s]+$/.test(value)) error = 'Name can only contain letters.';
                else if (!value.trim()) error = 'Full name is required.';
                break;
            case 'email':
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) error = 'Please enter a valid email address.';
                else if (!value) error = 'Email is required.';
                break;
            case 'phone':
                if (value && !/^0[0-9]{9}$/.test(value)) error = 'Phone number must be exactly 10 digits and start with 0.';
                else if (!value) error = 'Phone number is required.';
                break;
            case 'educational_level':
                if (!value) error = 'Educational level is required.';
                break;
            case 'password':
                if (value && value.length < 8) error = 'Password must be at least 8 characters.';
                else if (!value) error = 'Password is required.';
                break;
            case 'password_confirmation':
                if (value && value !== data.password) error = 'Passwords do not match.';
                break;
            default:
                break;
        }
        setLocalErrors(prev => ({ ...prev, [field]: error }));
    };

    const handleChange = (field, value) => {
        setData(field, value);
        validateField(field, value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (step === 1) {
            // Check if there are any current local errors before proceeding
            if (!data.name || !data.email || !data.phone || !data.educational_level || localErrors.name || localErrors.email || localErrors.phone || localErrors.educational_level) {
                // Trigger validation for empty fields
                validateField('name', data.name);
                validateField('email', data.email);
                validateField('phone', data.phone);
                validateField('educational_level', data.educational_level);
                return;
            }
            setStep(2);
        } else {
            if (!data.password || localErrors.password || localErrors.password_confirmation) {
                validateField('password', data.password);
                validateField('password_confirmation', data.password_confirmation);
                return;
            }
            post(route('register'));
        }
    };

    return (
        <GuestLayout>
            <Head title="Register" />

            <div className="max-w-md mx-auto py-8">
                <h2 className="text-2xl font-bold text-center text-theme-content mb-2">Create Account</h2>
                <p className="text-center text-sm text-theme-dim mb-6 font-medium">Step {step} of 2</p>
                
                <form onSubmit={handleSubmit} className="bg-theme-layer-2 p-4 sm:p-8 rounded-2xl shadow-xl shadow-theme-primary/5 ring-1 ring-theme-layer-3">
                    
                    {step === 1 && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <InputLabel htmlFor="name" value="Full Name" />

                                <TextInput
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    className="mt-1 block w-full bg-theme-layer-1 border-theme-layer-3 text-theme-content placeholder-theme-dim/50 rounded-xl focus:border-theme-primary focus:ring-theme-primary transition-colors"
                                    autoComplete="name"
                                    isFocused={true}
                                    autoFocus
                                    onChange={(e) => handleChange('name', e.target.value.replace(/[^a-zA-Z\s]/g, ''))}
                                    aria-invalid={!!(localErrors.name || errors.name)}
                                    aria-describedby="name-error"
                                    required
                                />

                                <InputError id="name-error" message={localErrors.name || errors.name} className="mt-2" />
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="email" value="Email Address" />

                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full bg-theme-layer-1 border-theme-layer-3 text-theme-content placeholder-theme-dim/50 rounded-xl focus:border-theme-primary focus:ring-theme-primary transition-colors"
                                    autoComplete="username"
                                    onChange={(e) => handleChange('email', e.target.value)}
                                    aria-invalid={!!(localErrors.email || errors.email)}
                                    aria-describedby="email-error"
                                    required
                                />

                                <InputError id="email-error" message={localErrors.email || errors.email} className="mt-2" />
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="phone" value="Phone Number" />

                                <TextInput
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value={data.phone}
                                    className="mt-1 block w-full bg-theme-layer-1 border-theme-layer-3 text-theme-content placeholder-theme-dim/50 rounded-xl focus:border-theme-primary focus:ring-theme-primary transition-colors"
                                    autoComplete="tel"
                                    maxLength="10"
                                    onChange={(e) => handleChange('phone', e.target.value.replace(/[^0-9]/g, ''))}
                                    aria-invalid={!!(localErrors.phone || errors.phone)}
                                    aria-describedby="phone-error"
                                    required
                                />

                                <InputError id="phone-error" message={localErrors.phone || errors.phone} className="mt-2" />
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="educational_level" value="Educational Level" />

                                <select
                                    id="educational_level"
                                    name="educational_level"
                                    value={data.educational_level}
                                    className="mt-1 block w-full bg-theme-layer-1 border-theme-layer-3 text-theme-content placeholder-theme-dim/50 rounded-xl focus:border-theme-primary focus:ring-theme-primary transition-colors py-2 px-3"
                                    onChange={(e) => handleChange('educational_level', e.target.value)}
                                    aria-invalid={!!(localErrors.educational_level || errors.educational_level)}
                                    aria-describedby="educational_level-error"
                                    required
                                >
                                    <option value="" disabled>Select your educational level</option>
                                    <option value="JHS">JHS</option>
                                    <option value="SHS">SHS</option>
                                    <option value="Tertiary">Tertiary</option>
                                </select>

                                <InputError id="educational_level-error" message={localErrors.educational_level || errors.educational_level} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-between mt-8">
                                <div>
                                    <span className="text-sm text-theme-dim">Already have an account? </span>
                                    <Link
                                        href={route('login')}
                                        className="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors rounded-lg focus:outline-none underline decoration-blue-600/40 dark:decoration-blue-400/40 underline-offset-2"
                                    >
                                        Log in
                                    </Link>
                                </div>

                                <PrimaryButton className="ml-4 px-6 py-2.5 rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-all" disabled={processing}>
                                    Next
                                </PrimaryButton>
                            </div>
                        </div>
                    )}

                    {step === 2 && (
                        <div className="animate-in fade-in slide-in-from-right-4 duration-300">
                            <div>
                                <InputLabel htmlFor="password" value="Password" />

                                <div className="relative mt-1">
                                    <TextInput
                                        id="password"
                                        type={showPasswords ? "text" : "password"}
                                        name="password"
                                        value={data.password}
                                        className="block w-full pr-10 bg-theme-layer-1 border-theme-layer-3 text-theme-content placeholder-theme-dim/50 rounded-xl focus:border-theme-primary focus:ring-theme-primary transition-colors"
                                        autoComplete="new-password"
                                        isFocused={true}
                                        onChange={(e) => handleChange('password', e.target.value)}
                                        aria-invalid={!!(localErrors.password || errors.password)}
                                        aria-describedby="password-error"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPasswords(!showPasswords)}
                                        className="absolute inset-y-0 right-0 flex items-center pr-3 text-theme-dim hover:text-theme-strong focus:outline-none"
                                        title={showPasswords ? "Hide password" : "Show password"}
                                    >
                                        {showPasswords ? (
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        ) : (
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        )}
                                    </button>
                                </div>

                                <InputError id="password-error" message={localErrors.password || errors.password} className="mt-2" />
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="password_confirmation" value="Confirm Password" />

                                <div className="relative mt-1">
                                    <TextInput
                                        id="password_confirmation"
                                        type={showPasswords ? "text" : "password"}
                                        name="password_confirmation"
                                        value={data.password_confirmation}
                                        className="block w-full pr-10 bg-theme-layer-1 border-theme-layer-3 text-theme-content placeholder-theme-dim/50 rounded-xl focus:border-theme-primary focus:ring-theme-primary transition-colors"
                                        autoComplete="new-password"
                                        onChange={(e) => handleChange('password_confirmation', e.target.value)}
                                        aria-invalid={!!(localErrors.password_confirmation || errors.password_confirmation)}
                                        aria-describedby="password_confirmation-error"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPasswords(!showPasswords)}
                                        className="absolute inset-y-0 right-0 flex items-center pr-3 text-theme-dim hover:text-theme-strong focus:outline-none"
                                        title={showPasswords ? "Hide password" : "Show password"}
                                    >
                                        {showPasswords ? (
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        ) : (
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        )}
                                    </button>
                                </div>

                                <InputError id="password_confirmation-error" message={localErrors.password_confirmation || errors.password_confirmation} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-between mt-8">
                                <button
                                    type="button"
                                    onClick={() => setStep(1)}
                                    className="text-sm font-medium text-theme-dim hover:text-theme-strong transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-theme-primary"
                                >
                                    &larr; Back
                                </button>

                                <PrimaryButton className="ml-4 px-6 py-2.5 rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-all" disabled={processing}>
                                    Create Account
                                </PrimaryButton>
                            </div>
                        </div>
                    )}
                </form>
            </div>
        </GuestLayout>
    );
}
