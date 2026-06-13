export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={`block text-sm font-medium text-theme-muted ${className}`.trim()}
        >
            {value ? value : children}
        </label>
    );
}
