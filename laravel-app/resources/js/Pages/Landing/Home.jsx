import AnimatedMeshBackground from '@/Components/Landing/AnimatedMeshBackground';
import HeroSection from '@/Components/Landing/HeroSection';
import HowItWorksSection from '@/Components/Landing/HowItWorksSection';
import LandingFooter from '@/Components/Landing/LandingFooter';
import LandingHeader from '@/Components/Landing/LandingHeader';
import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <div className="relative min-h-screen bg-[color:var(--mesh-bg)] font-sans text-theme-muted antialiased selection:bg-ai-accent/30 selection:text-theme-strong">
            <Head title="NovaGrade" />

            <AnimatedMeshBackground />
            <LandingHeader />

            <main className="relative">
                <HeroSection />
                <HowItWorksSection />
            </main>

            <LandingFooter />
        </div>
    );
}
