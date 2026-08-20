export default function ApplicationLogo(props) {
    return (
        <img
            {...props}
            src="/logo.png"
            alt="Assessiva Logo"
            className={`object-cover rounded-xl ${props.className || ''}`}
        />
    );
}
