import { Head, Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import ColumnFiltersBar from "../../Components/Common/List/ColumnFiltersBar";
import PaginationControls from "../../Components/Common/List/PaginationControls";
import RowActions from "../../Components/Common/List/RowActions";
import useAdminListPage from "../../Hooks/useAdminListPage";

const PER_PAGE = 20;
const MIN_FILTER_CHARS = 3;
const FILTER_FIELDS = [
    { key: "user", label: "User", placeholder: "Filtra per user..." },
    { key: "email", label: "Email", placeholder: "Filtra per email..." },
    { key: "name", label: "Name", placeholder: "Filtra per nome..." },
    { key: "company", label: "Company", placeholder: "Filtra per company..." },
    { key: "role", label: "Role", placeholder: "Filtra per ruolo..." },
];

const DEFAULT_FILTERS = {
    user: "",
    email: "",
    name: "",
    company: "",
    role: "",
};

export default function AdminUsersIndex({ company, role }) {
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
        endpoint: "/api/admin-users",
        loginPath: "/admin/login",
        perPage: PER_PAGE,
        defaultFilters: DEFAULT_FILTERS,
        minFilterChars: MIN_FILTER_CHARS,
        deleteErrorMessage: "Eliminazione admin user fallita.",
        fetchErrorMessage: "Errore caricamento admin users.",
    });

    if (booting || !isAuthenticated) return null;

    return (
        <>
            <Head title="Admin Users" />
            <AdminLayout
                title="Admin Users"
                company={company}
                role={role}
                userName={user?.name}
                onLogout={handleLogout}
                activeNav="admin-users"
                headerActions={(
                    <Link className="admin-cta-link fancy-create" href="/admin/admin-users/create">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M11 4h2v16h-2zM4 11h16v2H4z" />
                        </svg>
                        Nuovo admin user
                    </Link>
                )}
            >
                <article className="admin-card admin-table-card">
                    <div className="admin-list-head">
                        <h2>Elenco admin users</h2>
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
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item) => (
                                    <tr key={resolveItemId(item)}>
                                        <td>{item.user}</td>
                                        <td>{item.email}</td>
                                        <td>{item.name}</td>
                                        <td>{item.company}</td>
                                        <td>{item.role || "Admin"}</td>
                                        <td>{String(item.status ?? "")}</td>
                                        <td>
                                            <RowActions
                                                editHref={`/admin/admin-users/${encodeURIComponent(resolveItemId(item))}/edit`}
                                                editAriaLabel={`Modifica ${item.user || item.email || "admin user"}`}
                                                deleteAriaLabel={`Elimina ${item.user || item.email || "admin user"}`}
                                                deleteTargetLabel={item.user || item.email || resolveItemId(item)}
                                                deleteMessage="Vuoi eliminare l'admin user"
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
