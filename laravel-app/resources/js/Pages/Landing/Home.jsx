import HeroSection from '@/Components/Landing/HeroSection';
import HowItWorksSection from '@/Components/Landing/HowItWorksSection';
import LandingFooter from '@/Components/Landing/LandingFooter';
import LandingHeader from '@/Components/Landing/LandingHeader';
import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <div className="relative min-h-screen bg-white font-sans text-slate-600 antialiased selection:bg-indigo-100 selection:text-indigo-900 dark:bg-slate-950 dark:text-slate-400">
            <Head title="Assessiva" />

            <LandingHeader />

            <main className="relative">
                <HeroSection />
                <HowItWorksSection />
            </main>

            <LandingFooter />
        </div>
    );
}
