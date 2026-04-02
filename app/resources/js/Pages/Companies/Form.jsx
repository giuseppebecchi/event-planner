import { Head, Link } from "@inertiajs/react";
import axios from "axios";
import { useMemo, useState } from "react";
import AdminLayout from "../../Layouts/AdminLayout";
import useAdminSession from "../../Hooks/useAdminSession";
import { withAuth } from "../../Shared/auth";
import FormField from "../../Components/Common/Form/FormField";
import FormJsonField from "../../Components/Common/Form/FormJsonField";
import FormPanel from "../../Components/Common/Form/FormPanel";
import FormSelectField from "../../Components/Common/Form/FormSelectField";
import FormTextAreaField from "../../Components/Common/Form/FormTextAreaField";
import FormToggleField from "../../Components/Common/Form/FormToggleField";
import { formatDateTimeLocal, prettyJson } from "../../Shared/utils/form";

const JSON_FIELDS = {
    logo: "object",
    contacts: "array",
    payments: "array",
    mandatory_payment: "array",
    mandatory_payment_who: "array",
    mandatory_payment_offline: "array",
    mandatory_payment_offline_who: "array",
    mandatory_payment_coupon: "array",
    mandatory_payment_coupon_who: "array",
    preavviso: "object",
    base_commissions: "object",
    base_commissions_comipa: "object",
    time_confirmation: "object",
    visibility: "array",
    seed: "array",
    webhook_data: "object",
};

export default function CompanyFormPage({ company, role, mode = "create", record = null }) {
    const { token, user, booting, isAuthenticated, handleLogout } = useAdminSession({
        loginPath: "/admin/login",
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [jsonErrors, setJsonErrors] = useState({});

    const [form, setForm] = useState({
        company: record?.company ?? "",
        name: record?.name ?? "",
        company_name: record?.company_name ?? "",
        vat: record?.vat ?? "",
        tax_code: record?.tax_code ?? "",
        sdi: record?.sdi ?? "",
        pec: record?.pec ?? "",
        iban: record?.iban ?? "",
        url: record?.url ?? "",
        client_code: record?.client_code ?? "",
        payment_code: record?.payment_code ?? "",
        api_key: record?.api_key ?? "",
        comipa_code: record?.comipa_code ?? "",
        old_comipa_code: record?.old_comipa_code ?? "",
        commercial_agent_code: record?.commercial_agent_code ?? "",
        address_1: record?.address_1 ?? "",
        address_2: record?.address_2 ?? "",
        zipcode: record?.zipcode ?? "",
        city: record?.city ?? "",
        province: record?.province ?? "",
        blood_sampling_price:
            record?.blood_sampling_price === null || record?.blood_sampling_price === undefined
                ? ""
                : String(record.blood_sampling_price),
        skip_realtime_check:
            record?.skip_realtime_check === null || record?.skip_realtime_check === undefined
                ? ""
                : String(Boolean(record.skip_realtime_check)),
        visibility_old: record?.visibility_old ?? "",
        status: record?.status ?? "published",
        integrated: Boolean(record?.integrated),
        onboard: record?.onboard ?? "completed",
        group: record?.group ?? "",
        company_type: record?.company_type ?? "company",
        category_type: record?.category_type ?? "",
        reliability_type: record?.reliability_type ?? "risk",
        googleads: Boolean(record?.googleads),
        googlereservewith: Boolean(record?.googlereservewith),
        company_without_stamp_duty: Boolean(record?.company_without_stamp_duty),
        video_old: record?.video_old ?? "no",
        notes: record?.notes ?? "",
        max_office: record?.max_office ?? "",
        invoices_notes_xml: record?.invoices_notes_xml ?? "",
        prima_prenotazione: formatDateTimeLocal(record?.prima_prenotazione),
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
                const expected = type === "array" ? Array.isArray(value) : value && typeof value === "object" && !Array.isArray(value);
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
            blood_sampling_price: form.blood_sampling_price === "" ? null : Number(form.blood_sampling_price),
            skip_realtime_check:
                form.skip_realtime_check === ""
                    ? null
                    : form.skip_realtime_check === "true",
            prima_prenotazione: form.prima_prenotazione
                ? new Date(form.prima_prenotazione).toISOString()
                : null,
        };

        try {
            if (isEdit && record?._id) {
                await axios.put(`/api/companies/${record._id}`, payload, withAuth(token));
            } else {
                await axios.post("/api/companies", payload, withAuth(token));
            }

            window.location.replace("/admin/companies");
        } catch (err) {
            setError(err?.response?.data?.message || "Salvataggio fallito.");
        } finally {
            setLoading(false);
        }
    };

    if (booting || !isAuthenticated) return null;

    return (
        <>
            <Head title={isEdit ? "Modifica Azienda" : "Nuova Azienda"} />
            <AdminLayout
                title={isEdit ? "Modifica Azienda" : "Nuova Azienda"}
                company={company}
                role={role}
                userName={user?.name}
                onLogout={handleLogout}
                activeNav="companies"
            >
                <form className="company-form-layout" onSubmit={handleSubmit}>
                    <FormPanel title="Identità azienda" subtitle="Dati principali e identificativi">
                        <FormField
                            label="ID azienda"
                            required
                            field="company" form={form} setForm={setForm}
                            placeholder="centro-odontoiatrico"
                        />
                        <FormField
                            label="Ragione sociale (company_name)"
                            required
                            field="company_name" form={form} setForm={setForm}
                        />
                        <FormField
                            label="Nome visualizzato"
                            field="name" form={form} setForm={setForm}
                            required
                        />
                        <FormField
                            label="Categoria"
                            field="category_type" form={form} setForm={setForm}
                        />
                        <FormField
                            label="Gruppo"
                            field="group" form={form} setForm={setForm}
                        />
                        <FormSelectField
                            label="Tipo azienda"
                            field="company_type" form={form} setForm={setForm}
                            options={[
                                { value: "company", label: "company" },
                                { value: "partner", label: "partner" },
                                { value: "network", label: "network" },
                            ]}
                        />
                    </FormPanel>

                    <FormPanel title="Fiscale e contatti" subtitle="Partita IVA, PEC, codici e riferimenti">
                        <FormField label="VAT" field="vat" form={form} setForm={setForm} />
                        <FormField label="Tax Code" field="tax_code" form={form} setForm={setForm} />
                        <FormField label="SDI" field="sdi" form={form} setForm={setForm} />
                        <FormField label="PEC" type="email" field="pec" form={form} setForm={setForm} />
                        <FormField label="IBAN" field="iban" form={form} setForm={setForm} />
                        <FormField label="URL" type="url" field="url" form={form} setForm={setForm} />
                        <FormField label="Client code" field="client_code" form={form} setForm={setForm} />
                        <FormField label="Payment code" field="payment_code" form={form} setForm={setForm} />
                        <FormField label="API key" field="api_key" form={form} setForm={setForm} />
                        <FormField label="Comipa code" field="comipa_code" form={form} setForm={setForm} />
                        <FormField label="Old comipa code" field="old_comipa_code" form={form} setForm={setForm} />
                        <FormField label="Commercial agent code" field="commercial_agent_code" form={form} setForm={setForm} />
                        <FormJsonField
                            label="Logo (JSON object)"
                            field="logo" form={jsonValues} setForm={setJsonValues}
                            error={jsonErrors.logo}
                        />
                        <FormJsonField
                            label="Contatti (JSON array)"
                            field="contacts" form={jsonValues} setForm={setJsonValues}
                            error={jsonErrors.contacts}
                        />
                    </FormPanel>

                    <FormPanel title="Indirizzo" subtitle="Informazioni geografiche e recapiti sede">
                        <FormField label="Address 1" field="address_1" form={form} setForm={setForm} />
                        <FormField label="Address 2" field="address_2" form={form} setForm={setForm} />
                        <FormField label="CAP" field="zipcode" form={form} setForm={setForm} />
                        <FormField
                            label="City"
                            required
                            field="city" form={form} setForm={setForm}
                        />
                        <FormField label="Provincia" field="province" form={form} setForm={setForm} />
                    </FormPanel>

                    <FormPanel title="Pagamenti e prenotazioni" subtitle="Regole di pagamento e preavviso">
                        <FormJsonField label="Payments (JSON array)" field="payments" form={jsonValues} setForm={setJsonValues} error={jsonErrors.payments} />
                        <FormJsonField label="Mandatory payment (JSON array)" field="mandatory_payment" form={jsonValues} setForm={setJsonValues} error={jsonErrors.mandatory_payment} />
                        <FormJsonField label="Mandatory payment who (JSON array)" field="mandatory_payment_who" form={jsonValues} setForm={setJsonValues} error={jsonErrors.mandatory_payment_who} />
                        <FormJsonField label="Mandatory payment offline (JSON array)" field="mandatory_payment_offline" form={jsonValues} setForm={setJsonValues} error={jsonErrors.mandatory_payment_offline} />
                        <FormJsonField label="Mandatory payment offline who (JSON array)" field="mandatory_payment_offline_who" form={jsonValues} setForm={setJsonValues} error={jsonErrors.mandatory_payment_offline_who} />
                        <FormJsonField label="Mandatory payment coupon (JSON array)" field="mandatory_payment_coupon" form={jsonValues} setForm={setJsonValues} error={jsonErrors.mandatory_payment_coupon} />
                        <FormJsonField label="Mandatory payment coupon who (JSON array)" field="mandatory_payment_coupon_who" form={jsonValues} setForm={setJsonValues} error={jsonErrors.mandatory_payment_coupon_who} />
                        <FormJsonField label="Preavviso (JSON object)" field="preavviso" form={jsonValues} setForm={setJsonValues} error={jsonErrors.preavviso} />
                        <FormField label="Blood sampling price" type="number" step="0.01" field="blood_sampling_price" form={form} setForm={setForm} />
                        <FormField label="Max office" field="max_office" form={form} setForm={setForm} />
                        <FormSelectField
                            label="Skip realtime check"
                            field="skip_realtime_check" form={form} setForm={setForm}
                            options={[
                                { value: "", label: "null" },
                                { value: "true", label: "true" },
                                { value: "false", label: "false" },
                            ]}
                        />
                    </FormPanel>

                    <FormPanel title="Visibilità e stato" subtitle="Pubblicazione e classificazioni">
                        <FormSelectField
                            label="Status"
                            field="status" form={form} setForm={setForm}
                            options={[
                                { value: "published", label: "published" },
                                { value: "unpublished", label: "unpublished" },
                                { value: "draft", label: "draft" },
                            ]}
                        />
                        <FormSelectField
                            label="Onboard"
                            field="onboard" form={form} setForm={setForm}
                            options={[
                                { value: "completed", label: "completed" },
                                { value: "pending", label: "pending" },
                                { value: "in_progress", label: "in_progress" },
                            ]}
                        />
                        <FormSelectField
                            label="Reliability type"
                            field="reliability_type" form={form} setForm={setForm}
                            options={[
                                { value: "risk", label: "risk" },
                                { value: "standard", label: "standard" },
                                { value: "high", label: "high" },
                            ]}
                        />
                        <FormField label="Visibility old" field="visibility_old" form={form} setForm={setForm} />
                        <FormJsonField label="Visibility (JSON array)" field="visibility" form={jsonValues} setForm={setJsonValues} error={jsonErrors.visibility} />
                        <FormJsonField label="Seed (JSON array)" field="seed" form={jsonValues} setForm={setJsonValues} error={jsonErrors.seed} />
                        <FormToggleField label="Integrated" field="integrated" form={form} setForm={setForm} />
                        <FormToggleField label="Google Ads" field="googleads" form={form} setForm={setForm} />
                        <FormToggleField label="Google Reserve With" field="googlereservewith" form={form} setForm={setForm} />
                        <FormToggleField label="Company without stamp duty" field="company_without_stamp_duty" form={form} setForm={setForm} />
                        <FormSelectField
                            label="Video old"
                            field="video_old" form={form} setForm={setForm}
                            options={[
                                { value: "no", label: "no" },
                                { value: "yes", label: "yes" },
                            ]}
                        />
                    </FormPanel>

                    <FormPanel title="Commissioni e integrazione" subtitle="Configurazioni avanzate">
                        <FormJsonField label="Base commissions (JSON object)" field="base_commissions" form={jsonValues} setForm={setJsonValues} error={jsonErrors.base_commissions} />
                        <FormJsonField label="Base commissions comipa (JSON object)" field="base_commissions_comipa" form={jsonValues} setForm={setJsonValues} error={jsonErrors.base_commissions_comipa} />
                        <FormJsonField label="Time confirmation (JSON object)" field="time_confirmation" form={jsonValues} setForm={setJsonValues} error={jsonErrors.time_confirmation} />
                        <FormJsonField label="Webhook data (JSON object)" field="webhook_data" form={jsonValues} setForm={setJsonValues} error={jsonErrors.webhook_data} />
                    </FormPanel>

                    <FormPanel title="Note e metadati" subtitle="Annotazioni e dati accessori">
                        <FormTextAreaField label="Notes" field="notes" form={form} setForm={setForm} />
                        <FormTextAreaField label="Invoices notes xml" field="invoices_notes_xml" form={form} setForm={setForm} />
                        <FormField label="Prima prenotazione" type="datetime-local" field="prima_prenotazione" form={form} setForm={setForm} />
                    </FormPanel>

                    <footer className="company-sticky-footer">
                        <div className="company-sticky-inner">
                            <Link className="admin-btn secondary" href="/admin/companies">
                                Annulla
                            </Link>
                            <button type="submit" className="admin-btn" disabled={loading}>
                                {loading ? "Salvataggio..." : "Salva azienda"}
                            </button>
                        </div>
                    </footer>
                </form>

                {error ? <p className="admin-feedback admin-error">{error}</p> : null}
            </AdminLayout>
        </>
    );
}
