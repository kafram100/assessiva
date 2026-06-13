import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const SIDEBAR_COLLAPSED_KEY = 'admin_sidebar_collapsed';

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

function IconBuilding(props) {
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
                d="M2.25 21h19.5M3.75 21V6.75A2.25 2.25 0 016 4.5h4.5A2.25 2.25 0 0112.75 6.75V21M12.75 21h3.75M9 10.5h.008v.008H9V10.5zm0 3h.008v.008H9V13.5zm0 3h.008v.008H9V16.5z"
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

function IconPersonGear(props) {
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
                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
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

function IconJournalCheck(props) {
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
                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
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

function IconEnvelope(props) {
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
                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
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

export default function AdminLayout({ header, children }) {
    const user = usePage().props.auth.user;
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
                    href={route('admin.dashboard')}
                    onClick={closeMobile}
                    className="flex min-h-[58px] shrink-0 items-center gap-3 border-b border-theme-line px-5 py-[18px] no-underline"
                >
                    <span className="flex shrink-0 text-ai-accent">
                        <ApplicationLogo className="block h-8 w-auto max-w-[2rem] fill-current" />
                    </span>
                    <span
                        className={`truncate font-display text-lg font-bold text-theme-strong transition-opacity duration-200 ${
                            effectiveCollapsed ? 'sr-only' : ''
                        }`}
                    >
                        Admin
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
                        Admin
                    </div>

                    <SidebarLink
                        href={route('admin.dashboard')}
                        label="Admin dashboard"
                        icon={IconDashboard}
                        active={navActive('admin.dashboard')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.institutional-intelligence')}
                        label="Institutional intelligence"
                        icon={IconDiagram}
                        active={navActive(
                            'admin.institutional-intelligence',
                        )}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.schools')}
                        label="Schools"
                        icon={IconBuilding}
                        active={navActive('admin.schools*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.departments')}
                        label="Departments"
                        icon={IconDiagram}
                        active={navActive('admin.departments*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.lecturers.pending')}
                        label="Pending lecturers"
                        icon={IconPersonGear}
                        active={navActive('admin.lecturers.pending')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.lecturers')}
                        label="Academic staff"
                        icon={IconPersonGear}
                        active={
                            navActive('admin.lecturers*') &&
                            !navActive('admin.lecturers.pending')
                        }
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.hods.index')}
                        label="HOD management"
                        icon={IconPeople}
                        active={navActive('admin.hods.*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.exams')}
                        label="All exams"
                        icon={IconJournal}
                        active={navActive('admin.exams*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.quizzes')}
                        label="All quizzes"
                        icon={IconJournalCheck}
                        active={navActive('admin.quizzes')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.frequent-cheaters')}
                        label="Proctoring incidents"
                        icon={IconShield}
                        active={navActive('admin.frequent-cheaters')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.students')}
                        label="All students"
                        icon={IconPeople}
                        active={navActive('admin.students')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.email-settings')}
                        label="Email settings"
                        icon={IconEnvelope}
                        active={navActive('admin.email-settings*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.official-question-reviews.index')}
                        label="Official question reviews"
                        icon={IconShield}
                        active={navActive(
                            'admin.official-question-reviews.index',
                        )}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />
                    <SidebarLink
                        href={route('admin.question-bank-catalog.index')}
                        label="Question bank catalog"
                        icon={IconJournal}
                        active={navActive('admin.question-bank-catalog*')}
                        collapsed={effectiveCollapsed}
                        onNavigate={closeMobile}
                    />

                    <div className="mx-2 mt-4 border-t border-theme-line pt-4">
                        <SidebarLink
                            href={route('lecturer.exams.index')}
                            label="Lecturer exams"
                            icon={IconJournal}
                            active={navActive('lecturer.exams*')}
                            collapsed={effectiveCollapsed}
                            onNavigate={closeMobile}
                        />
                        <SidebarLink
                            href={route('lecturer.courses.index')}
                            label="Courses & rosters"
                            icon={IconClipboardList}
                            active={navActive('lecturer.courses*')}
                            collapsed={effectiveCollapsed}
                            onNavigate={closeMobile}
                        />
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
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-ai-accent to-ai-accent-deep text-sm font-bold text-white shadow-sm">
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
                        <div className="text-[0.72rem] text-theme-dim">Admin</div>
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
