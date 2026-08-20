import { motion } from 'framer-motion';

const steps = [
    { n: '1', title: 'Create Account', body: 'Sign up and get instant access to your free practice questions tier.' },
    { n: '2', title: 'Practice & Test', body: 'Generate a practice test pulled from our extensive exam databank.' },
    { n: '3', title: 'Get AI Feedback', body: 'Submit your answers and let Assessiva analyze your mistakes step-by-step.' },
];

export default function HowItWorksSection() {
    return (
        <section id="how-it-works" className="relative z-10 px-4 py-32 sm:px-6 lg:px-8 bg-white dark:bg-slate-950">
            <div className="mx-auto max-w-7xl">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-80px' }}
                    className="mx-auto max-w-2xl text-center"
                >
                    <h2 className="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl dark:text-white">
                        Your path to exam success
                    </h2>
                    <p className="mt-4 text-lg text-slate-500 dark:text-slate-400">
                        Three simple steps to build your confidence and ace your next test.
                    </p>
                </motion.div>

                <div className="relative mt-24">
                    <div className="grid gap-16 lg:grid-cols-3 lg:gap-12">
                        {steps.map((s, i) => (
                            <motion.div
                                key={s.n}
                                initial={{ opacity: 0, y: 24 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: '-40px' }}
                                transition={{ delay: 0.1 * i, duration: 0.6 }}
                                className="relative flex flex-col items-center text-center lg:items-start lg:text-left"
                            >
                                <div className="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-2xl font-bold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    {s.n}
                                </div>
                                <h3 className="font-display text-2xl font-semibold text-slate-900 dark:text-white">{s.title}</h3>
                                <p className="mt-4 text-base leading-relaxed text-slate-500 dark:text-slate-400">{s.body}</p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
