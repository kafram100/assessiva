export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `relative inline-flex items-center justify-center overflow-hidden rounded-xl border border-transparent bg-primary-600 px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-white shadow-md shadow-primary-600/30 transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-500 hover:shadow-lg hover:shadow-primary-600/40 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800 dark:focus:ring-offset-neutral-900 group ${
                    disabled && 'opacity-50 cursor-not-allowed hover:translate-y-0 hover:shadow-none'
                } ` + className
            }
            disabled={disabled}
        >
            <div className="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 pointer-events-none"></div>
            <span className="relative z-10">{children}</span>
        </button>
    );
}
