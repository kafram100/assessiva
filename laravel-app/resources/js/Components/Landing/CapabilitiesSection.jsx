import { motion } from 'framer-motion';

const card = {
    hidden: { opacity: 0, y: 24 },
    show: (i) => ({
        opacity: 1,
        y: 0,
        transition: { delay: 0.06 * i, duration: 0.55, ease: [0.22, 1, 0.36, 1] },
    }),
};

const items = [
    {
        title: 'Randomized Exams',
        desc: 'Take 20-question randomized quizzes mixed with theory, MCQs, and fill-ins to test your true knowledge.',
        icon: (
            <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M12 3c4.97 0 9 3.58 9 8s-4.03 8-9 8c-1.5 0-2.91-.36-4.12-1" strokeLinecap="round" />
                <path d="M12 21v-4M7 17H4a2 2 0 01-2-2V9a2 2 0 012-2h3" strokeLinecap="round" />
                <circle cx="9" cy="11" r="1.5" fill="currentColor" stroke="none" />
                <circle cx="15" cy="11" r="1.5" fill="currentColor" stroke="none" />
            </svg>
        ),
    },
    {
        title: 'Detailed AI Feedback',
        desc: 'Receive deep analysis and step-by-step explanations for every question you get wrong so you never make the same mistake twice.',
        icon: (
            <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M12 3l7 4v5c0 5-3.5 9-7 11-3.5-2-7-6-7-11V7l7-4z" strokeLinejoin="round" />
                <path d="M9 12l2 2 4-4" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
    {
        title: 'Vast Question Bank',
        desc: 'Never run out of practice material with a constantly updated database of challenging exam questions.',
        icon: (
            <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M4 19V5M4 19h16M8 15l3-3 3 2 4-5" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
    {
        title: 'Real-time Results',
        desc: 'Get your scores and comprehensive feedback delivered straight to your email the moment you finish your quiz.',
        icon: (
            <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M4 19h16M7 16l4-12 3 9 3-6 3 9" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
    {
        title: 'Pay-As-You-Go',
        desc: 'Enjoy a free tier of practice questions, then simply top-up your account securely to unlock unlimited access.',
        icon: (
            <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <rect x="4" y="4" width="16" height="16" rx="2" />
                <path d="M8 9h8M8 13h5M8 17h6" strokeLinecap="round" />
            </svg>
        ),
    },
    {
        title: 'Performance Tracking',
        desc: 'Monitor your progress over time, identify weak spots, and focus your revision where it matters most.',
        icon: (
            <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" strokeLinejoin="round" />
                <circle cx="12" cy="11" r="2" fill="currentColor" stroke="none" />
            </svg>
        ),
    },
];

export default function CapabilitiesSection() {
    return (
        <section id="capabilities" className="relative z-10 border-t border-theme-line bg-theme-canvas px-4 py-24 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-7xl">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-80px' }}
                    transition={{ duration: 0.6 }}
                    className="max-w-2xl"
                >
                    <p className="text-xs font-semibold uppercase tracking-[0.25em] text-ai-glow/90">Features</p>
                    <h2 className="mt-3 font-display text-3xl font-bold tracking-tight text-theme-strong sm:text-4xl">
                        Everything you need to ace your next exam
                    </h2>
                    <p className="mt-4 text-theme-muted">
                        A smarter way to practice. Get personalized feedback, tackle randomized questions, and track your progress all in one place.
                    </p>
                </motion.div>

                <div className="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {items.map((item, i) => (
                        <motion.article
                            key={item.title}
                            custom={i}
                            variants={card}
                            initial="hidden"
                            whileInView="show"
                            viewport={{ once: true, margin: '-40px' }}
                            whileHover={{
                                y: -6,
                                boxShadow: 'var(--shadow-card-hover)',
                            }}
                            transition={{ type: 'spring', stiffness: 260, damping: 22 }}
                            className="group relative overflow-hidden rounded-2xl border border-theme-line bg-theme-glass p-6 shadow-theme-card backdrop-blur-xl"
                        >
                            <div className="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-ai-accent/10 blur-2xl transition group-hover:bg-ai-accent/20" />
                            <div className="relative flex h-12 w-12 items-center justify-center rounded-xl border border-ai-accent/30 bg-ai-accent/10 text-ai-glow">
                                {item.icon}
                            </div>
                            <h3 className="relative mt-5 font-display text-lg font-semibold text-theme-strong">{item.title}</h3>
                            <p className="relative mt-2 text-sm leading-relaxed text-theme-muted">{item.desc}</p>
                        </motion.article>
                    ))}
                </div>
            </div>
        </section>
    );
}
