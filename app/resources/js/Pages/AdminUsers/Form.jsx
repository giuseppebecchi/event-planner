import { Head, Link } from "@inertiajs/react";
import axios from "axios";
import { useMemo, useState } from "react";
import FormField from "../../Components/Common/Form/FormField";
import FormJsonField from "../../Components/Common/Form/FormJsonField";
import FormPanel from "../../Components/Common/Form/FormPanel";
import FormSelectField from "../../Components/Common/Form/FormSelectField";
import AdminLayout from "../../Layouts/AdminLayout";
import useAdminSession from "../../Hooks/useAdminSession";
import { withAuth } from "../../Shared/auth";
import { prettyJson } from "../../Shared/utils/form";

const JSON_FIELDS = {
    channel: "object",
    data: "object",
    subcompanies: "array",
    where: "array",
    roles: "array",
    permissions: "array",
};

export default function AdminUsersForm({
    company,
    role,
    mode = "create",
    record = null,
    roleOptions = [],
}) {
    const { token, user, booting, isAuthenticated, handleLogout } = useAdminSession({
        loginPath: "/admin/login",
    });

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [jsonErrors, setJsonErrors] = useState({});

    const [form, setForm] = useState({
        user: record?.user ?? "",
        email: record?.email ?? "",
        first_name: record?.first_name ?? "",
        last_name: record?.last_name ?? "",
        name: record?.name ?? "",
        company: record?.company ?? "",
        address_book: record?.address_book ?? "",
        iduniq: record?.iduniq ?? "",
        list: record?.list ?? "admin",
        password: "",
        status: record?.status === undefined || record?.status === null ? "1" : String(record.status),
        token: record?.token ?? "",
        role: record?.role ?? "Admin",
    });

    const [jsonValues, setJsonValues] = useState(() => {
        const entries = Object.entries(JSON_FIELDS).map(([field, type]) => [
            field,
            prettyJson(record?.[field], type),
        ]);
        return Object.fromEntries(entries);
    });

    const isEdit = useMemo(() => mode === "edit", [mode]);

    const parseJsonPayload = () => {
        const parsed = {};
        const errors = {};

        for (const [field, type] of Object.entries(JSON_FIELDS)) {
            const raw = jsonValues[field]?.trim() ?? "";
            if (!raw) {
                parsed[field] = type === "array" ? [] : {};
                continue;
            }

            try {
                const value = JSON.parse(raw);
                const expected = type === "array"
                    ? Array.isArray(value)
                    : value && typeof value === "object" && !Array.isArray(value);

                if (!expected) {
                    errors[field] = type === "array" ? "Atteso array JSON" : "Atteso object JSON";
                } else {
                    parsed[field] = value;
                }
            } catch (_err) {
                errors[field] = "JSON non valido";
            }
        }

        setJsonErrors(errors);
        return { parsed, errors };
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError("");

        const { parsed, errors } = parseJsonPayload();
        if (Object.keys(errors).length > 0) {
            setLoading(false);
            setError("Correggi i campi JSON evidenziati.");
            return;
        }

        const payload = {
            ...form,
            ...parsed,
            status: Number(form.status || 0),
        };

        if (isEdit && !payload.password) {
            delete payload.password;
        }

        try {
            if (isEdit && record?._id) {
                await axios.put(`/api/admin-users/${record._id}`, payload, withAuth(token));
            } else {
                await axios.post("/api/admin-users", payload, withAuth(token));
            }

            window.location.replace("/admin/admin-users");
        } catch (err) {
            setError(err?.response?.data?.message || "Salvataggio fallito.");
        } finally {
            setLoading(false);
        }
    };

    if (booting || !isAuthenticated) return null;

    return (
        <>
            <Head title={isEdit ? "Modifica Admin User" : "Nuovo Admin User"} />
            <AdminLayout
                title={isEdit ? "Modifica Admin User" : "Nuovo Admin User"}
                company={company}
                role={role}
                userName={user?.name}
                onLogout={handleLogout}
                activeNav="admin-users"
            >
                <form className="company-form-layout" onSubmit={handleSubmit}>
                    <FormPanel title="Anagrafica" subtitle="Dati principali utente amministratore">
                        <FormField label="User" required field="user" form={form} setForm={setForm} />
                        <FormField label="Email" required type="email" field="email" form={form} setForm={setForm} />
                        <FormField label="First name" field="first_name" form={form} setForm={setForm} />
                        <FormField label="Last name" field="last_name" form={form} setForm={setForm} />
                        <FormField label="Name" required field="name" form={form} setForm={setForm} />
                        <FormField label="Company" field="company" form={form} setForm={setForm} />
                        <FormField label="Address book" field="address_book" form={form} setForm={setForm} />
                        <FormField label="Iduniq" field="iduniq" form={form} setForm={setForm} />
                        <FormField label="List" field="list" form={form} setForm={setForm} />
                    </FormPanel>

                    <FormPanel title="Sicurezza e ruolo" subtitle="Credenziali, token e autorizzazione">
                        <FormField
                            label={isEdit ? "Password (lascia vuoto per non cambiarla)" : "Password"}
                            type="password"
                            required={!isEdit}
                            field="password"
                            form={form}
                            setForm={setForm}
                        />
                        <FormField label="Token" field="token" form={form} setForm={setForm} />
                        <FormSelectField
                            label="Role"
                            field="role"
                            form={form}
                            setForm={setForm}
                            options={(roleOptions.length ? roleOptions : ["SuperAdmin", "Admin", "Company Manager", "Doctor"]).map((value) => ({ value, label: value }))}
                        />
                        <FormSelectField
                            label="Status"
                            field="status"
                            form={form}
                            setForm={setForm}
                            options={[
                                { value: "1", label: "Attivo (1)" },
                                { value: "0", label: "Disattivo (0)" },
                            ]}
                        />
                    </FormPanel>

                    <FormPanel title="Dati estesi" subtitle="Campi complessi in formato JSON">
                        <FormJsonField label="Channel (JSON object)" field="channel" form={jsonValues} setForm={setJsonValues} error={jsonErrors.channel} />
                        <FormJsonField label="Data (JSON object)" field="data" form={jsonValues} setForm={setJsonValues} error={jsonErrors.data} />
                        <FormJsonField label="Subcompanies (JSON array)" field="subcompanies" form={jsonValues} setForm={setJsonValues} error={jsonErrors.subcompanies} />
                        <FormJsonField label="Where (JSON array)" field="where" form={jsonValues} setForm={setJsonValues} error={jsonErrors.where} />
                        <FormJsonField label="Roles (JSON array)" field="roles" form={jsonValues} setForm={setJsonValues} error={jsonErrors.roles} />
                        <FormJsonField label="Permissions (JSON array)" field="permissions" form={jsonValues} setForm={setJsonValues} error={jsonErrors.permissions} />
                    </FormPanel>

                    <footer className="company-sticky-footer">
                        <div className="company-sticky-inner">
                            <Link className="admin-btn secondary" href="/admin/admin-users">
                                Annulla
                            </Link>
                            <button type="submit" className="admin-btn" disabled={loading}>
                                {loading ? "Salvataggio..." : "Salva admin user"}
                            </button>
                        </div>
                    </footer>
                </form>

                {error ? <p className="admin-feedback admin-error">{error}</p> : null}
            </AdminLayout>
        </>
    );
}
