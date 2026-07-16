export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-neutral-300 dark:border-neutral-600 text-primary-600 shadow-sm focus:ring-primary-500 ' +
                className
            }
        />
    );
}
