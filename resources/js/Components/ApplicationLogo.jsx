export default function ApplicationLogo(props) {
    return (
        <div {...props} className={`flex items-center justify-center ${props.className || ''}`}>
            <div className="w-16 h-16 bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-2xl flex items-center justify-center shadow-lg">
                <span className="text-white font-extrabold text-2xl">AL</span>
            </div>
        </div>
    );
}
