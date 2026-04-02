import { Link } from "@inertiajs/react";

const icons = {
    dashboard: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z" /></svg>
    ),
    companies: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16v-2H4v2zM6 17h4V7H6v10zm8 0h4V3h-4v14zM11 17h2V9h-2v8z" /></svg>
    ),
    doctors: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.42 0-8 2-8 4.5V21h16v-2.5C20 16 16.42 14 12 14z" /></svg>
    ),
    services: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3h-4l-1-1h-4L9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-6 14h-2v-4H7v-2h4V7h2v4h4v2h-4z" /></svg>
    ),
    agenda: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 15H5V10h14v9z" /></svg>
    ),
    payments: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 15h-2v-2H9v-2h2V7h2v2h2v2h-2z" /></svg>
    ),
    adminUsers: (
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm-9 9v-2.5C3 16 7 14 12 14s9 2 9 4.5V21h-2v-2.5c0-1.2-3-2.5-7-2.5s-7 1.3-7 2.5V21z" /></svg>
    ),
};

export default function AdminLayout({
    title,
    company,
    role,
    userName,
    onLogout,
    activeNav = "dashboard",
    headerActions = null,
    children,
}) {
    const navigationSections = [
        {
            label: "Navigazione azienda",
            items: [
                { key: "dashboard", label: "Dashboard", href: "/admin", enabled: true },
                { key: "companies", label: "Aziende", href: "/admin/companies", enabled: true },
                { key: "doctors", label: "Dottori", href: "#", enabled: false },
                { key: "services", label: "Prestazioni", href: "#", enabled: false },
                { key: "agenda", label: "Agenda", href: "#", enabled: false },
                { key: "payments", label: "Incassi", href: "#", enabled: false },
            ],
        },
        {
            label: "Amministrazione",
            items: [
                { key: "admin-users", iconKey: "adminUsers", label: "Admin Users", href: "/admin/admin-users", enabled: true },
            ],
        },
    ];

    return (
        <div className="admin-shell">
            <header className="admin-topbar">
                <div className="admin-topbar-left">
                    <div className="admin-brand">
                        <img className="admin-brand-full" src="/images/logo.png" alt="Cup Solidale" />
                    </div>
                    <button className="admin-menu-toggle" type="button" aria-label="Apri menu">
                        <span />
                        <span />
                        <span />
                    </button>
                </div>
                {userName ? (
                    <button className="admin-user" type="button" onClick={onLogout} title="Logout">
                        <span>{userName}</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 10l5 5 5-5z" />
                        </svg>
                    </button>
                ) : (
                    <span className="admin-user">Admin</span>
                )}
            </header>

            <div className="admin-main">
                <aside className="admin-sidebar">
                    <div className="admin-company">
                        <p className="admin-company-label">
                            <span>AZIENDA:</span> {company}
                        </p>
                        <div className="admin-company-select-wrap">
                            <select disabled defaultValue="">
                                <option value="">Seleziona una azienda</option>
                            </select>
                        </div>
                    </div>
                    {navigationSections.map((section) => (
                        <div key={section.label} className="admin-nav-block">
                            <p className="admin-nav-section">{section.label}</p>
                            <nav className="admin-nav">
                                {section.items.map((item) => (
                                    item.enabled ? (
                                        <Link
                                            key={item.key}
                                            className={`admin-nav-item ${activeNav === item.key ? "is-active" : ""}`}
                                            href={item.href}
                                        >
                                            <span className="admin-nav-icon">{icons[item.iconKey || item.key]}</span>
                                            {item.label}
                                        </Link>
                                    ) : (
                                        <span key={item.key} className="admin-nav-item is-disabled">
                                            <span className="admin-nav-icon">{icons[item.iconKey || item.key]}</span>
                                            {item.label}
                                        </span>
                                    )
                                ))}
                            </nav>
                        </div>
                    ))}
                </aside>

                <section className="admin-content">
                    <div className="admin-page-header">
                        <div className="admin-page-header-top">
                            <h1>{title}</h1>
                            {headerActions}
                        </div>
                        <p>{company} <span>{role}</span></p>
                    </div>
                    {children}
                </section>
            </div>
        </div>
    );
}
