import { Head, Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import ColumnFiltersBar from "../../Components/Common/List/ColumnFiltersBar";
import PaginationControls from "../../Components/Common/List/PaginationControls";
import RowActions from "../../Components/Common/List/RowActions";
import useAdminListPage from "../../Hooks/useAdminListPage";

const PER_PAGE = 20;
const MIN_FILTER_CHARS = 3;
const FILTER_FIELDS = [
    { key: "name", label: "Name", placeholder: "Filtra per nome..." },
    { key: "city", label: "City", placeholder: "Filtra per citta..." },
    { key: "province", label: "Province", placeholder: "Filtra per provincia..." },
    //{ key: "status", label: "Status", placeholder: "Filtra per status..." },
];
const DEFAULT_FILTERS = {
    name: "",
    city: "",
    province: "",
    status: "",
};

export default function CompanyIndex({ company, role }) {
    const {
        user,
        booting,
        isAuthenticated,
        handleLogout,
        items,
        meta,
        filters,
        loading,
        error,
        onFilterChange,
        resolveItemId,
        deleteItem,
        pagination,
    } = useAdminListPage({
        endpoint: "/api/companies",
        loginPath: "/admin/login",
        perPage: PER_PAGE,
        defaultFilters: DEFAULT_FILTERS,
        minFilterChars: MIN_FILTER_CHARS,
        deleteErrorMessage: "Eliminazione fallita.",
        fetchErrorMessage: "Errore caricamento aziende.",
    });

    if (booting || !isAuthenticated) return null;

    return (
        <>
            <Head title="Aziende" />
            <AdminLayout
                title="Aziende"
                company={company}
                role={role}
                userName={user?.name}
                onLogout={handleLogout}
                activeNav="companies"
                headerActions={(
                    <Link className="admin-cta-link fancy-create" href="/admin/companies/create">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M11 4h2v16h-2zM4 11h16v2H4z" />
                        </svg>
                        Nuova azienda
                    </Link>
                )}
            >
                <article className="admin-card admin-table-card">
                    <div className="admin-list-head">
                        <h2>Elenco aziende</h2>
                    </div>

                    <ColumnFiltersBar
                        fields={FILTER_FIELDS}
                        values={filters}
                        onFilterChange={onFilterChange}
                        minChars={MIN_FILTER_CHARS}
                    />

                    <div className="admin-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>Province</th>
                                    <th>Status</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item) => (
                                    <tr key={resolveItemId(item)}>
                                        <td>{item.name}</td>
                                        <td>{item.city}</td>
                                        <td>{item.province}</td>
                                        <td>
                                            <span
                                                className={`status-pill ${
                                                    String(item.status).toLowerCase() === "published"
                                                        ? "is-published"
                                                        : "is-unpublished"
                                                }`}
                                            >
                                                {item.status || "unpublished"}
                                            </span>
                                        </td>
                                        <td>
                                            <RowActions
                                                editHref={`/admin/companies/${encodeURIComponent(resolveItemId(item))}/edit`}
                                                editAriaLabel={`Modifica ${item.company || item.name || "azienda"}`}
                                                deleteAriaLabel={`Elimina ${item.company || item.name || "azienda"}`}
                                                deleteTargetLabel={item.company || item.name || resolveItemId(item)}
                                                deleteMessage="Vuoi eliminare l'azienda"
                                                onDeleteConfirm={() => deleteItem(item)}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <PaginationControls
                        meta={meta}
                        loading={loading}
                        onPrev={pagination.onPrev}
                        onNext={pagination.onNext}
                    />
                </article>

                {error ? <p className="admin-feedback admin-error">{error}</p> : null}
            </AdminLayout>
        </>
    );
}
