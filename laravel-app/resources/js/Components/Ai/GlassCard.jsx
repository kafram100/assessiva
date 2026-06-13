/**
 * Premium glass surface — matches global `.ai-glass-card` tokens.
 */
export default function GlassCard({
    as: Tag = 'div',
    className = '',
    hover = false,
    children,
    ...rest
}) {
    return (
        <Tag
            className={`ai-glass-card p-6 ${hover ? 'ai-glass-card-hover' : ''} ${className}`.trim()}
            {...rest}
        >
            {children}
        </Tag>
    );
}
