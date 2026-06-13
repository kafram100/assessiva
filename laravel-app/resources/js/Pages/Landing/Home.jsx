import AnimatedMeshBackground from '@/Components/Landing/AnimatedMeshBackground';
import CapabilitiesSection from '@/Components/Landing/CapabilitiesSection';
import DashboardPreviewSection from '@/Components/Landing/DashboardPreviewSection';
import FutureVisionSection from '@/Components/Landing/FutureVisionSection';
import HeroSection from '@/Components/Landing/HeroSection';
import HowItWorksSection from '@/Components/Landing/HowItWorksSection';
import LandingFooter from '@/Components/Landing/LandingFooter';
import LandingHeader from '@/Components/Landing/LandingHeader';
import PracticeHubTeaserSection from '@/Components/Landing/PracticeHubTeaserSection';
import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <div className="relative min-h-screen bg-[color:var(--mesh-bg)] font-sans text-theme-muted antialiased selection:bg-ai-accent/30 selection:text-theme-strong">
            <Head title="Novagrade.ai" />

            <AnimatedMeshBackground />
            <LandingHeader />

            <main className="relative">
                <HeroSection />
                <CapabilitiesSection />
                <DashboardPreviewSection />
                <HowItWorksSection />
                <FutureVisionSection />
                <PracticeHubTeaserSection />
            </main>

            <LandingFooter />
        </div>
    );
}
