export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={`btn-ai-danger ${disabled ? 'pointer-events-none opacity-30' : ''} ${className}`.trim()}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
