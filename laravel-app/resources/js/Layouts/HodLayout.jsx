import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const SIDEBAR_COLLAPSED_KEY = 'hod_sidebar_collapsed';

function IconDashboard(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75A2.25 2.25 0 0115.75 18H18a2.25 2.25 0 002.25-2.25V15.75A2.25 2.25 0 0018 13.5h-2.25a2.25 2.25 0 00-2.25 2.25V18zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25z"
            />
        </svg>
    );
}

function IconDiagram(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"
            />
        </svg>
    );
}

function IconJournal(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
            />
        </svg>
    );
}

function IconPeople(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
            />
        </svg>
    );
}

function IconShield(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
            />
        </svg>
    );
}

function IconSparkles(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.847a4.5 4.5 0 003.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"
            />
        </svg>
    );
}

function IconClipboardList(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"
            />
        </svg>
    );
}

function IconLibrary(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
            />
        </svg>
    );
}

function IconUserCircle(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.925 0M12 6.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z"
            />
        </svg>
    );
}

function IconMenu(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
            />
        </svg>
    );
}

function IconLogout(props) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            aria-hidden
            {...props}
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
            />
        </svg>
    );
}

function SidebarLink({ href, label, icon: Icon, active, collapsed, onNavigate }) {
    return (
        <Link
            href={href}
            title={collapsed ? label : undefined}
            onClick={onNavigate}
            className={`ai-sidebar-link ${active ? 'ai-sidebar-link-active' : ''} ${
                collapsed ? 'justify-center' : ''
            }`}
        >
            <span className="flex h-[22px] w-[22px] shrink-0 items-center justify-center [&>svg]:h-[1.1rem] [&>svg]:w-[1.1rem]">
                <Icon />
            </span>
            <span
                className={`min-w-0 truncate transition-opacity duration-200 ${
                    collapsed ? 'sr-only' : ''
                }`}
            >
                {label}
            </span>
        </Link>
    );
}

export default function HodLayout({ header, children }) {
    const { auth, hodContext } = usePage().props;
    const user = auth.user;
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [viewportLg, setViewportLg] = useState(() => {
        if (typeof window === 'undefined') {
            return false;
        }
        return window.matchMedia('(min-width: 1024px)').matches;
    });

    useEffect(() => {
        try {
            if (localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1') {
                setCollapsed(true);
            }
        } catch {
            /* ignore */
        }
    }, []);

    useEffect(() => {
        const mq = window.matchMedia('(min-width: 1024px)');
        const sync = () => {
            const lg = mq.matches;
            setViewportLg(lg);
            if (lg) {
                setMobileOpen(false);
            }
        };
        sync();
        mq.addEventListener('change', sync);
        return () => mq.removeEventListener('change', sync);
    }, []);

    const toggleSidebar = () => {
        if (viewportLg) {
            setCollapsed((c) => {
                const next = !c;
                try {
                    localStorage.setItem(
                        SIDEBAR_COLLAPSED_KEY,
                        next ? '1' : '0',
                    );
                } catch {
                    /* ignore */
                }
                return next;
            });
        } else {
            setMobileOpen((o) => !o);
        }
    };

    const closeMobile = () => setMobileOpen(false);

    const navActive = (pattern) => route().current(pattern);

    const current = route().current() ?? '';
    const intelligenceActive =
        current === 'hod.academic-intelligence' ||
        current.startsWith('hod.intelligence.');
    const aiReviewsActive = current.startsWith('hod.ai-question-reviews');
    const aiBankSettingsActive = current.startsWith(
        'hod.ai-question-bank-settings',
    );

    const sidebarWidth = viewportLg ? (collapsed ? 70 : 260) : 260;
    const mainMarginLeft = viewportLg ? sidebarWidth : 0;
    const effectiveCollapsed = viewportLg && collapsed;

    return (
        <div className="ai-app-shell">
            <button
                type="button"
                aria-label="Close menu"
                className={`fixed inset-0 z-[1039] bg-black/45 transition-opacity lg:hidden ${
                    mobileOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
                }`}
                onClick={closeMobile}
            />

            <aside
                className={`ai-sidebar fixed left-0 top-0 z-[1040] flex h-screen flex-col overflow-hidden transition-[width,transform] duration-200 ease-out ${
                    mobileOpen
                        ? 'max-lg:translate-x-0'
                        : 'max-lg:-translate-x-full'
                } lg:translate-x-0`}
                style={{ width: sidebarWidth }}
            >
                <Link
                    href={route('hod.dashboard')}
                    onClick={closeMobile}
                    className="flex min-h-[58px] shrink-0 items-center gap-3 border-b border-theme-line px-5 py-[18px] no-underline"
                >
                    <span className="flex shrink-0 text-indigo-400">
                        <ApplicationLogo className="block h-8 w-auto max-w-[2rem] fill-current" />
                    </span>
                    <span
                        className={`flex min-w-0 flex-col transition-opacity duration-200 ${
                            effectiveCollapsed ? 'sr-only' : ''
                        }`}
                    >
                        <span className="truncate font-display text-lg font-bold text-theme-strong">
                            HoD
                        </span>
                        {hodContext?.label ? (
                            <span className="truncate text-[0.65rem] font-medium leading-tight text-theme-dim">
                                {hodContext.label}
                            </span>
                        ) : null}
                    </span>
                </Link>

                <nav className="ai-sidebar-scroll flex flex-1 flex-col overflow-y-auto overflow-x-hidden py-3">
                    <div
                        className={`ai-sidebar-section-label ${
                            effectiveCollapsed
                                ? 'sr-only h-0 overflow-hidden p-0 opacity-0'
                                : ''
                        }`}
                    >
                        Department
                    </div>
                    <SidebarLink
                        href={route('hod.dashboard')}
                        label="Overview"
                        icon={IconDashboard}
                        active={navActive('hod.dashboard')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('hod.lecturers.index')}
                        label="Lecturers"
                        icon={IconPeople}
                        active={navActive('hod.lecturers.index')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('hod.exams.index')}
                        label="Department exams"
                        icon={IconJournal}
                        active={navActive('hod.exams.index')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />

                    <div
                        className={`ai-sidebar-section-label ${
                            effectiveCollapsed
                                ? 'sr-only h-0 overflow-hidden p-0 opacity-0'
                                : ''
                        }`}
                    >
                        Intelligence
                    </div>
                    <SidebarLink
                        href={route('hod.academic-intelligence')}
                        label="Academic intelligence"
                        icon={IconDiagram}
                        active={intelligenceActive}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />

                    <div
                        className={`ai-sidebar-section-label ${
                            effectiveCollapsed
                                ? 'sr-only h-0 overflow-hidden p-0 opacity-0'
                                : ''
                        }`}
                    >
                        Question bank
                    </div>
                    <SidebarLink
                        href={route(
                            'admin.official-question-reviews.index',
                        )}
                        label="Official question reviews"
                        icon={IconShield}
                        active={navActive('admin.official-question-reviews*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('hod.ai-question-reviews.index')}
                        label="AI question reviews"
                        icon={IconSparkles}
                        active={aiReviewsActive}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('hod.ai-question-bank-settings.edit')}
                        label="AI bank settings"
                        icon={IconClipboardList}
                        active={aiBankSettingsActive}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.question-bank-catalog.index')}
                        label="Question bank catalog"
                        icon={IconLibrary}
                        active={navActive('admin.question-bank-catalog*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />

                    <div className="mx-2 mt-4 border-t border-theme-line pt-4">
                        <SidebarLink
                            href={route('profile.edit')}
                            label="Profile"
                            icon={IconUserCircle}
                            active={navActive('profile.edit')}
                            collapsed={effectiveCollapsed}
                            onNavigate={closeMobile}
                        />
                    </div>
                </nav>

                <div className="flex shrink-0 items-center gap-2.5 border-t border-theme-line px-4 py-3.5">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 text-sm font-bold text-white shadow-sm">
                        {(user?.username ?? '?').slice(0, 1).toUpperCase()}
                    </div>
                    <div
                        className={`min-w-0 flex-1 overflow-hidden ${
                            effectiveCollapsed ? 'sr-only' : ''
                        }`}
                    >
                        <div className="truncate text-sm font-semibold text-theme-strong">
                            {user?.username}
                        </div>
                        <div className="text-[0.72rem] text-theme-dim">
                            Head of department
                        </div>
                    </div>
                    <button
                        type="button"
                        title="Log out"
                        onClick={() => router.post(route('logout'))}
                        className="shrink-0 rounded p-1 text-theme-dim transition-colors hover:text-red-500"
                    >
                        <IconLogout className="h-5 w-5" />
                    </button>
                </div>
            </aside>

            <div
                className="flex min-h-screen flex-col transition-[margin-left] duration-200 ease-out"
                style={{ marginLeft: mainMarginLeft }}
            >
                <div className="ai-topbar">
                    <button
                        type="button"
                        aria-label="Toggle sidebar"
                        onClick={toggleSidebar}
                        className="rounded-lg p-1.5 text-theme-dim transition hover:bg-theme-highlight hover:text-theme-strong"
                    >
                        <IconMenu className="h-6 w-6" />
                    </button>
                    <div className="ai-topbar-title">{header}</div>
                    <ThemeToggle />
                </div>

                <FlashBanner />

                <main className="ai-main-canvas flex-1 px-6 py-6 max-lg:px-4">
                    {children}
                </main>
            </div>
        </div>
    );
}
