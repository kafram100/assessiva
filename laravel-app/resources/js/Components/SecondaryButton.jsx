export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={`btn-ai-secondary ${disabled ? 'pointer-events-none opacity-30' : ''} ${className}`.trim()}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
