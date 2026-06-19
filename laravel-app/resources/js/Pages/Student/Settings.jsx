import React, { useRef, useState } from 'react';
import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Settings({ user }) {
    const fileInputRef = useRef(null);
    const [previewUrl, setPreviewUrl] = useState(user.profile_picture_url || null);

    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        name: user.name || '',
        level: user.level || '',
        profile_picture: null,
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('profile_picture', file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('student.settings.update'), {
            preserveScroll: true,
            onSuccess: () => {
                setData('current_password', '');
                setData('password', '');
                setData('password_confirmation', '');
            },
        });
    };

    return (
        <PracticeHubLayout>
            <Head title="Settings" />

            <div className="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-theme-content">Account Settings</h1>
                    <p className="text-theme-dim mt-1 text-sm">Update your profile information and password.</p>
                </div>

                <form onSubmit={submit} className="bg-theme-elevated border border-theme-line rounded-xl p-6 shadow-sm">
                    {recentlySuccessful && (
                        <div className="mb-6 p-4 bg-green-500/10 border border-green-500/20 text-green-600 rounded-xl text-sm font-medium">
                            Profile updated successfully.
                        </div>
                    )}

                    <div className="space-y-8">
                        {/* Profile Picture */}
                        <div>
                            <h3 className="text-lg font-medium text-theme-content mb-4">Profile Picture</h3>
                            <div className="flex items-center gap-6">
                                <div className="h-24 w-24 rounded-full bg-theme-layer-3 overflow-hidden border-2 border-theme-line flex items-center justify-center shrink-0">
                                    {previewUrl ? (
                                        <img src={previewUrl} alt="Profile" className="h-full w-full object-cover" />
                                    ) : (
                                        <svg className="h-12 w-12 text-theme-dim" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    )}
                                </div>
                                <div>
                                    <input
                                        type="file"
                                        className="hidden"
                                        ref={fileInputRef}
                                        onChange={handleFileChange}
                                        accept="image/*"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="rounded-lg bg-theme-layer-3 border border-theme-line px-4 py-2 text-sm font-medium text-theme-content hover:bg-theme-layer-4 transition"
                                    >
                                        Change Picture
                                    </button>
                                    <InputError message={errors.profile_picture} className="mt-2" />
                                </div>
                            </div>
                        </div>

                        <hr className="border-theme-line" />

                        {/* Basic Info */}
                        <div>
                            <h3 className="text-lg font-medium text-theme-content mb-4">Basic Information</h3>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="name" value="Name" />
                                    <TextInput
                                        id="name"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <InputError className="mt-2" message={errors.name} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        className="mt-1 block w-full bg-theme-layer-3 text-theme-muted cursor-not-allowed"
                                        value={user.email}
                                        readOnly
                                        disabled
                                    />
                                    <p className="mt-1 text-xs text-theme-dim">Email cannot be changed.</p>
                                </div>

                                <div>
                                    <InputLabel htmlFor="level" value="Current Level (Optional)" />
                                    <select
                                        id="level"
                                        className="mt-1 block w-full border-theme-line bg-theme-elevated text-theme-content focus:border-sky-500 focus:ring-sky-500 rounded-md shadow-sm sm:text-sm"
                                        value={data.level}
                                        onChange={(e) => setData('level', e.target.value)}
                                    >
                                        <option value="">Select Level</option>
                                        <option value="100">Level 100</option>
                                        <option value="200">Level 200</option>
                                        <option value="300">Level 300</option>
                                        <option value="400">Level 400</option>
                                        <option value="Graduate">Graduate</option>
                                    </select>
                                    <InputError className="mt-2" message={errors.level} />
                                </div>
                            </div>
                        </div>

                        <hr className="border-theme-line" />

                        {/* Password */}
                        <div>
                            <h3 className="text-lg font-medium text-theme-content mb-4">Update Password</h3>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="current_password" value="Current Password" />
                                    <TextInput
                                        id="current_password"
                                        type="password"
                                        className="mt-1 block w-full max-w-md"
                                        value={data.current_password}
                                        onChange={(e) => setData('current_password', e.target.value)}
                                    />
                                    <InputError className="mt-2" message={errors.current_password} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="password" value="New Password" />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        className="mt-1 block w-full"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    <InputError className="mt-2" message={errors.password} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="password_confirmation" value="Confirm New Password" />
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        className="mt-1 block w-full"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                    />
                                    <InputError className="mt-2" message={errors.password_confirmation} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 pt-6 border-t border-theme-line flex justify-end">
                        <PrimaryButton disabled={processing} className="px-6 py-3">
                            Save Changes
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </PracticeHubLayout>
    );
}
