import GuestLayout from '@/Layouts/GuestLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Legacy route: /register redirects server-side to lecturer registration.
 * This page handles any direct Inertia visits to the old Register component.
 */
export default function Register() {
    useEffect(() => {
        router.replace(route('lecturer.register'));
    }, []);

    return (
        <GuestLayout>
            <Head title="Register" />
            <p className="text-center text-sm text-theme-dim">
                Redirecting to lecturer registration…
            </p>
        </GuestLayout>
    );
}
