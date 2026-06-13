export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-theme-line text-ai-accent shadow-sm focus:ring-ai-accent focus:ring-offset-2 focus:ring-offset-theme ' +
                className
            }
        />
    );
}
