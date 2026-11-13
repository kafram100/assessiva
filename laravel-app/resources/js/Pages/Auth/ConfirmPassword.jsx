import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Confirm Password" />

            <div className="mb-6 text-center">
                <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                    Confirm Password
                </h2>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    This is a secure area of the application. Please confirm your password before continuing.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="password" value="Password" className="text-xs font-medium" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full text-sm"
                        placeholder="••••••••"
                        isFocused={true}
                        required
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-1 text-xs" />
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all"
                        disabled={processing}
                    >
                        {processing ? 'Confirming...' : 'Confirm'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
