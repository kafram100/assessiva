export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={`btn-ai-primary ${disabled ? 'pointer-events-none opacity-30' : ''} ${className}`.trim()}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
