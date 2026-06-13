import { motion } from 'framer-motion';

export default function AnimatedMeshBackground() {
    return (
        <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden bg-[color:var(--mesh-bg)]">
            <div
                className="absolute inset-0"
                style={{
                    backgroundImage: 'var(--mesh-radial-image)',
                    opacity: 'var(--mesh-radial-opacity)',
                }}
            />
            <div
                className="absolute inset-0"
                style={{
                    backgroundImage: 'var(--mesh-conic-image)',
                    opacity: 'var(--mesh-conic-opacity)',
                }}
            />

            <div
                className="absolute inset-0 animate-grid-drift"
                style={{
                    opacity: 'var(--mesh-drift-opacity)',
                    backgroundImage: `
                        linear-gradient(var(--mesh-grid-line) 1px, transparent 1px),
                        linear-gradient(90deg, var(--mesh-grid-line) 1px, transparent 1px)
                    `,
                    backgroundSize: '120px 120px',
                }}
            />

            <motion.div
                className="absolute -left-32 top-1/4 h-96 w-96 rounded-full blur-[100px]"
                style={{ backgroundColor: 'var(--mesh-blob-1)' }}
                animate={{ scale: [1, 1.15, 1], opacity: [0.25, 0.45, 0.25] }}
                transition={{ duration: 10, repeat: Infinity, ease: 'easeInOut' }}
            />
            <motion.div
                className="absolute -right-24 bottom-1/4 h-[28rem] w-[28rem] rounded-full blur-[120px]"
                style={{ backgroundColor: 'var(--mesh-blob-2)' }}
                animate={{ scale: [1.08, 1, 1.08], opacity: [0.2, 0.4, 0.2] }}
                transition={{ duration: 12, repeat: Infinity, ease: 'easeInOut' }}
            />

            {[...Array(18)].map((_, i) => (
                <motion.span
                    key={i}
                    className="absolute h-1 w-1 rounded-full"
                    style={{
                        left: `${(i * 17 + 7) % 100}%`,
                        top: `${(i * 23 + 11) % 100}%`,
                        backgroundColor: 'var(--mesh-particle)',
                        boxShadow: '0 0 12px var(--mesh-particle-glow)',
                    }}
                    animate={{
                        y: [0, -30, 0],
                        opacity: [0.2, 0.9, 0.2],
                    }}
                    transition={{
                        duration: 4 + (i % 5),
                        repeat: Infinity,
                        delay: i * 0.2,
                        ease: 'easeInOut',
                    }}
                />
            ))}
        </div>
    );
}
