import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link, usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;

    return (
        <div className="ai-app-shell">
            <nav className="sticky top-0 z-50 border-b border-theme-line backdrop-blur-xl bg-[color:var(--topbar-bg)]">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-ai-accent" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Dashboard
                                </NavLink>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <ThemeToggle />
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-full sm:rounded-lg sm:border sm:border-theme-line sm:bg-theme-highlight sm:px-3 sm:py-2 text-sm font-medium leading-4 text-theme-muted transition duration-150 ease-out hover:border-ai-accent/30 hover:text-theme-strong focus:outline-none"
                                            >
                                                {user.profile_picture_url && (
                                                    <img src={user.profile_picture_url} alt={user.name} className="sm:mr-2 h-8 w-8 sm:h-6 sm:w-6 shrink-0 aspect-square rounded-full object-cover" />
                                                )}
                                                <span className="hidden sm:inline-flex">{user.name}</span>

                                                <svg
                                                    className="hidden sm:inline-flex -me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <div className="block border-b border-theme-line mb-1 sm:hidden">
                                            <Dropdown.Link
                                                href={route('dashboard')}
                                            >
                                                Dashboard
                                            </Dropdown.Link>
                                        </div>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-theme-line backdrop-blur-md bg-[color:var(--main-canvas)]">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 [&_h2]:font-display [&_h2]:text-theme-strong">
                        {header}
                    </div>
                </header>
            )}

            <main className="text-theme-muted">{children}</main>
        </div>
    );
}
