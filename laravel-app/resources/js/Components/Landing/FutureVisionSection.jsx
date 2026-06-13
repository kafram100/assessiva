import { motion, useScroll, useTransform } from 'framer-motion';
import { useRef } from 'react';

export default function FutureVisionSection() {
    const ref = useRef(null);
    const { scrollYProgress } = useScroll({
        target: ref,
        offset: ['start end', 'end start'],
    });
    const y = useTransform(scrollYProgress, [0, 1], [40, -40]);
    const opacity = useTransform(scrollYProgress, [0.15, 0.35, 0.75, 1], [0.2, 1, 1, 0.35]);

    return (
        <section
            ref={ref}
            className="relative z-10 overflow-hidden border-t border-theme-line bg-gradient-to-b from-theme-canvas via-[color:var(--mesh-bg)] to-[color:var(--mesh-bg)] px-4 py-32 sm:px-6 lg:px-8"
        >
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,163,255,0.12),transparent_55%)]" />
            <motion.div style={{ y, opacity }} className="relative mx-auto max-w-4xl text-center">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-ai-glow/80">Future of learning</p>
                <h2 className="mt-6 font-display text-4xl font-bold leading-tight tracking-tight text-theme-strong sm:text-5xl lg:text-6xl">
                    Building the AI nervous system of the future university
                </h2>
                <p className="mx-auto mt-8 max-w-2xl text-lg text-theme-muted">
                    Every assessment, practice session, and insight becomes a neuron in a living institutional brain, one
                    that learns as your students learn.
                </p>
                <div className="mx-auto mt-12 flex max-w-lg flex-wrap justify-center gap-3 text-xs text-theme-dim">
                    {['Neural academic graph', 'Continuous calibration', 'Human in the loop AI', 'Privacy by design'].map(
                        (t) => (
                            <span
                                key={t}
                                className="rounded-full border border-theme-line bg-theme-highlight px-4 py-2 text-theme-muted backdrop-blur-sm"
                            >
                                {t}
                            </span>
                        ),
                    )}
                </div>
            </motion.div>
        </section>
    );
}
