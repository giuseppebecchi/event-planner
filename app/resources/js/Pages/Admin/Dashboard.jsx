import { Head, Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import useAdminSession from "../../Hooks/useAdminSession";

export default function Dashboard({ company, role }) {
    const { user, booting, isAuthenticated, handleLogout } = useAdminSession({
        loginPath: "/admin/login",
    });

    if (booting || !isAuthenticated) return null;

    const fakeTotals = [
        { label: "Pagamento in sede", count: 4806, amount: "303.279,10" },
        { label: "Pagamento online", count: 437, amount: "26.936,49" },
        { label: "Cancellate", count: 996, amount: "62.829,74" },
    ];

    const fakeLastBookings = [
        { id: 41006, date: "07/08/2020 18:57", client: "Sabrina Grassellini", patient: "Sabrina G. (F)", detail: "RETE PAS - SAN DONNINO" },
        { id: 40988, date: "07/08/2020 16:20", client: "Martina Velluti", patient: "Giacomo V. (M)", detail: "Visita specialistica" },
        { id: 40971, date: "07/08/2020 15:10", client: "Laura Taddei", patient: "Laura T. (F)", detail: "Ecografia addome" },
    ];

    return (
        <>
            <Head title="Admin Dashboard" />
            <AdminLayout
                title="Home"
                company={company}
                role={role}
                userName={user?.name}
                onLogout={handleLogout}
                activeNav="dashboard"
            >
                <section className="dash-grid">
                    <article className="admin-card">
                        <h2>Totale prenotazioni (euro)</h2>
                        <ul className="dash-kpi-list">
                            {fakeTotals.map((row) => (
                                <li key={row.label}>
                                    <span>{row.label}</span>
                                    <strong>
                                        {row.count} <small>per euro</small> {row.amount}
                                    </strong>
                                </li>
                            ))}
                        </ul>
                    </article>

                    <article className="admin-card">
                        <h2>Prenotazioni ultimi 30 giorni</h2>
                        <svg viewBox="0 0 560 220" className="dash-chart" aria-hidden="true">
                            <defs>
                                <linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stopColor="#44b28d" stopOpacity="0.25" />
                                    <stop offset="100%" stopColor="#44b28d" stopOpacity="0.02" />
                                </linearGradient>
                            </defs>
                            <path d="M0 180 L20 150 L40 190 L60 120 L80 105 L100 130 L120 160 L140 95 L160 110 L180 80 L200 130 L220 95 L240 140 L260 70 L280 100 L300 75 L320 115 L340 90 L360 55 L380 86 L400 62 L420 78 L440 42 L460 55 L480 30 L500 48 L520 25 L540 38 L560 18 L560 220 L0 220 Z" fill="url(#chartFill)" />
                            <path d="M0 180 L20 150 L40 190 L60 120 L80 105 L100 130 L120 160 L140 95 L160 110 L180 80 L200 130 L220 95 L240 140 L260 70 L280 100 L300 75 L320 115 L340 90 L360 55 L380 86 L400 62 L420 78 L440 42 L460 55 L480 30 L500 48 L520 25 L540 38 L560 18" fill="none" stroke="#42ad88" strokeWidth="4" />
                        </svg>
                    </article>
                </section>

                <article className="admin-card admin-table-card">
                    <h2>Distribuzione canali</h2>
                    <div className="dash-bars">
                        <div>
                            <label>Pagamento in sede</label>
                            <div className="dash-bar"><span style={{ width: "100%" }} /></div>
                        </div>
                        <div>
                            <label>Pagamento online</label>
                            <div className="dash-bar"><span style={{ width: "9%" }} /></div>
                        </div>
                        <div>
                            <label>Cancellate</label>
                            <div className="dash-bar"><span style={{ width: "21%" }} /></div>
                        </div>
                    </div>
                    <Link className="admin-cta-link" href="/admin/companies">Vai a Aziende</Link>
                </article>

                <article className="admin-card admin-table-card">
                    <h2>Ultime prenotazioni (fake)</h2>
                    <div className="admin-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Paziente</th>
                                    <th>Dettaglio</th>
                                </tr>
                            </thead>
                            <tbody>
                                {fakeLastBookings.map((row) => (
                                    <tr key={row.id}>
                                        <td>{row.id}</td>
                                        <td>{row.date}</td>
                                        <td>{row.client}</td>
                                        <td>{row.patient}</td>
                                        <td>{row.detail}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </article>
            </AdminLayout>
        </>
    );
}
