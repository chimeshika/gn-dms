import { canManage } from "../lib/permissions";
import { Link, useSearchParams } from "react-router-dom";
import { useState, useEffect } from "react";
import { api, formPayload } from "../lib/api";
import { useApi, label, date } from "../lib/hooks";
import { useAuth } from "../lib/auth";
import { resources, staticOptions } from "../lib/resources";
import {
  Alert,
  Badge,
  Field,
  Heading,
  Loading,
  Pagination,
} from "../components/UI";
import OfficerSelect from "../components/OfficerSelect";

export function Editor({
  resource,
  record,
  readOnly,
  onClose,
  onSaved,
  stepped = false,
}) {
  const [step, setStep] = useState(0);
  const config = resources[resource];
  const { user } = useAuth();
  const [values, setValues] = useState(() => {
    const initial = {
      is_active: true,
      gender: "male",
      dependents: [],
      medium: "si",
      current_grade: "grade_iii",
      service_status: "appointed",
      confirmation_status: "pending",
      status: "pending_verification",
      role: "officer",
      category: "other",
      district_id: user.district_id || "",
      current_district_id: user.district_id || "",
      ds_division_id: user.ds_division_id || "",
      current_ds_division_id: user.ds_division_id || "",
    };
    config.fields.forEach(([name, , type]) => {
      if (record?.[name] != null)
        initial[name] = type === "date" ? date(record[name]) : record[name];
    });
    initial.gender = String(initial.gender).toLowerCase();
    initial.medium =
      { Sinhala: "si", Tamil: "ta", English: "en" }[initial.medium] ||
      initial.medium;
    return initial;
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const { data: meta, error: metaError } = useApi("/metadata");
  const prefix = resource === "officers" ? "current_" : "";
  const { data: locations, error: locationsError } = useApi(
    `/locations?district_id=${values[prefix + "district_id"] || ""}&ds_division_id=${values[prefix + "ds_division_id"] || ""}`,
  );
  const set = (name, value) =>
    setValues((current) => ({
      ...current,
      [name]: value,
      ...(name === prefix + "district_id"
        ? { [prefix + "ds_division_id"]: "", [prefix + "gn_division_id"]: "" }
        : name === prefix + "ds_division_id"
          ? { [prefix + "gn_division_id"]: "" }
          : {}),
    }));
  const submit = async (e) => {
    e.preventDefault();
    if (stepped && step < 3) {
      setStep(step + 1);
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const allowed = Object.fromEntries(
        config.fields.map(([name]) => [name, values[name] ?? null]),
      );
      const body = formPayload(allowed);
      let method = record?.id ? "PUT" : "POST";
      if (body instanceof FormData && method === "PUT") {
        body.append("_method", "PUT");
        method = "POST";
      }
      await api(`/records/${resource}${record?.id ? `/${record.id}` : ""}`, {
        method,
        body,
      });
      onSaved();
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };
  return (
    <section className="panel editor">
      <div className="section-heading">
        <h2>
          {readOnly
            ? "View record"
            : record?.id
              ? "Edit record"
              : "Create record"}
        </h2>
        <button type="button" className="secondary" onClick={onClose}>
          Close
        </button>
      </div>
      <Alert error={error || metaError || locationsError} />
      {stepped && (
        <div className="stepper">
          {[
            "Personal Information",
            "Service Information",
            "Emergency Contact",
            "Dependents",
          ].map((title, i) => (
            <button
              type="button"
              key={title}
              className={step === i ? "active" : ""}
              onClick={() => {
                if (i < step) setStep(i);
              }}
            >
              <span className="step-number">{i + 1}</span>
              {title}
            </button>
          ))}
        </div>
      )}
      <form onSubmit={submit}>
        <fieldset disabled={busy || readOnly}>
          <div className="form-grid">
            {config.fields
              .filter(
                ([name]) =>
                  !stepped ||
                  (step === 0
                    ? [
                        "full_name_en",
                        "full_name_si",
                        "full_name_ta",
                        "nic_no",
                        "dob",
                        "gender",
                        "medium",
                        "contact_email",
                        "mobile_phone",
                        "address_line1",
                        "address_line2",
                        "address_line3",
                      ].includes(name)
                    : step === 1
                      ? [
                          "designation",
                          "current_grade",
                          "service_status",
                          "confirmation_status",
                          "current_district_id",
                          "current_ds_division_id",
                          "current_gn_division_id",
                          "first_appointment_date",
                          "appointment_date",
                          "confirmation_date",
                        ].includes(name)
                      : step === 2
                        ? name.startsWith("emergency_")
                        : [
                            "spouse_name",
                            "dependants_count",
                            "dependents",
                          ].includes(name)),
              )
              .map(([name, title, type = "text", required]) => {
                if (type === "dependents")
                  return (
                    <div className="wide" key={name}>
                      <h2>Dependents</h2>
                      {(values.dependents || []).map((person, i) => (
                        <div className="form-grid" key={i}>
                          {["name", "relationship", "dob"].map((key) => (
                            <Field
                              key={key}
                              name={`dependent-${i}-${key}`}
                              title={label(key)}
                              type={key === "dob" ? "date" : "text"}
                              required={key !== "dob"}
                              value={person[key]}
                              onChange={(v) =>
                                set(
                                  "dependents",
                                  values.dependents.map((p, j) =>
                                    j === i ? { ...p, [key]: v } : p,
                                  ),
                                )
                              }
                            />
                          ))}
                          <button
                            type="button"
                            className="link-button danger"
                            onClick={() =>
                              set(
                                "dependents",
                                values.dependents.filter((_, j) => j !== i),
                              )
                            }
                          >
                            Remove dependent
                          </button>
                        </div>
                      ))}
                      <button
                        type="button"
                        className="secondary"
                        onClick={() =>
                          set("dependents", [
                            ...(values.dependents || []),
                            { name: "", relationship: "", dob: "" },
                          ])
                        }
                      >
                        Add dependent
                      </button>
                    </div>
                  );

                if (type === "officers")
                  return readOnly ? (
                    <p key={name}>{record?.officer?.full_name_en}</p>
                  ) : (
                    <OfficerSelect
                      key={name}
                      value={values[name]}
                      selectedOfficer={record?.officer}
                      onChange={(v) => set(name, v)}
                    />
                  );
                if (readOnly && ["password", "file"].includes(type))
                  return null;
                const options =
                  staticOptions[type] || meta?.[type] || locations?.[type];
                const isSelect = ![
                  "text",
                  "date",
                  "password",
                  "email",
                  "file",
                  "checkbox",
                  "textarea",
                  "number",
                ].includes(type);
                return (
                  <Field
                    key={name}
                    {...{ name, title, type }}
                    required={
                      required ||
                      (name === "password" && !record?.id) ||
                      (resource === "documents" &&
                        name === "file" &&
                        !record?.id)
                    }
                    options={isSelect ? options || [] : undefined}
                    value={values[name]}
                    onChange={(v) => set(name, v)}
                    {...(type === "file"
                      ? {
                          accept:
                            resource === "documents"
                              ? ".pdf,.png,.jpg,.jpeg"
                              : ".png,.jpg,.jpeg",
                        }
                      : {})}
                  />
                );
              })}
          </div>
        </fieldset>
        {!readOnly && (
          <div className="form-footer">
            {stepped && step > 0 && (
              <button
                type="button"
                className="secondary"
                onClick={() => setStep(step - 1)}
              >
                Previous
              </button>
            )}
            <button disabled={busy}>
              {busy
                ? "Saving..."
                : stepped
                  ? step < 3
                    ? "Next"
                    : record?.id
                      ? "Save Officer"
                      : "Create Officer"
                  : "Save record"}
            </button>
          </div>
        )}
      </form>
    </section>
  );
}
export default function Records({ resource }) {
  const { user } = useAuth();
  const config = resources[resource];
  const [page, setPage] = useState(1);
  const [params] = useSearchParams();
  const [search, setSearch] = useState(params.get("search") || "");
  const [filters, setFilters] = useState({});
  useEffect(() => {
    setSearch(params.get("search") || "");
    setPage(1);
  }, [params]);
  const { data: meta } = useApi("/metadata");
  const { data: locations } = useApi(
    `/locations?district_id=${filters.current_district_id || ""}&ds_division_id=${filters.current_ds_division_id || ""}`,
  );
  const [revision, setRevision] = useState(0);
  const [record, setRecord] = useState(null);
  const [error, setError] = useState(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const {
    data,
    loading,
    error: loadError,
  } = useApi(
    `/records/${resource}?page=${page}&search=${encodeURIComponent(search)}&${new URLSearchParams(filters)}`,
    revision,
  );
  const admin = canManage(user);
  const action = async (path, method) => {
    setBusy(true);
    setError(null);
    try {
      const result = await api(path, { method });
      setMessage(result.message);
      setRevision((v) => v + 1);
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };
  const cell = (row, key) =>
    ["ds_division", "gn_division"].includes(key) ? (
      row[key]?.name_en || "Not recorded"
    ) : key === "officer" ? (
      row.officer?.full_name_en || "—"
    ) : key === "is_active" ? (
      <Badge value={row[key] ? "active" : "inactive"} />
    ) : key.endsWith("date") ? (
      date(row[key])
    ) : [
        "status",
        "service_status",
        "role",
        "current_grade",
        "event_type",
        "document_type",
        "category",
      ].includes(key) ? (
      <Badge value={row[key]} />
    ) : (
      row[key] || "—"
    );
  return (
    <>
      <Heading title={config.title} description={config.description}>
        {admin &&
          (resource === "officers" ? (
            <Link className="button" to="/officers/create">
              + Add Officer
            </Link>
          ) : resource === "documents" ? (
            <Link className="button teal" to="/documents/upload">
              Upload Document
            </Link>
          ) : (
            <button onClick={() => setRecord({})}>+ Add record</button>
          ))}
        {resource === "officers" &&
          ["xlsx", "pdf"].map((format) => (
            <a
              key={format}
              className={`button ${format === "xlsx" ? "teal" : "secondary"}`}
              href={`/api/records/officers?${new URLSearchParams({ ...filters, search, export: format })}`}
            >
              Export {format === "xlsx" ? "Excel" : "PDF"}
            </a>
          ))}
      </Heading>
      <Alert error={error || loadError} message={message} />
      {record && (
        <Editor
          key={record.id || "new"}
          {...{ resource, record }}
          readOnly={!admin}
          onClose={() => setRecord(null)}
          onSaved={() => {
            setRecord(null);
            setMessage("Record saved.");
            setRevision((v) => v + 1);
          }}
        />
      )}
      {resource === "officers" && (
        <section className="panel">
          <div className="section-heading">
            <h2>Search & Filters</h2>
            <button
              className="link-button"
              onClick={() => {
                setFilters({});
                setSearch("");
                setPage(1);
              }}
            >
              Clear Filters
            </button>
          </div>
          <div className="filters-grid">
            <Field
              name="officer-name"
              title="Officer Name"
              value={search}
              onChange={(v) => {
                setSearch(v);
                setPage(1);
              }}
            />
            {[
              ["nic_no", "NIC"],
              ["current_district_id", "District", locations?.districts],
              [
                "current_ds_division_id",
                "DS Division",
                locations?.ds_divisions,
              ],
              [
                "current_gn_division_id",
                "GN Division",
                locations?.gn_divisions,
              ],
              ["designation", "Designation"],
              ["current_grade", "Grade", meta?.grades],
              ["service_status", "Status", meta?.service_statuses],
            ].map(([name, title, options]) => (
              <Field
                key={name}
                name={name}
                title={title}
                options={options}
                value={filters[name] || ""}
                onChange={(v) => {
                  setFilters({
                    ...filters,
                    [name]: v,
                    ...(name === "current_district_id"
                      ? {
                          current_ds_division_id: "",
                          current_gn_division_id: "",
                        }
                      : name === "current_ds_division_id"
                        ? { current_gn_division_id: "" }
                        : {}),
                  });
                  setPage(1);
                }}
              />
            ))}
          </div>
        </section>
      )}
      <section className="panel">
        <div className="table-toolbar">
          <Field
            name="search"
            title="Search records"
            value={search}
            onChange={(v) => {
              setSearch(v);
              setPage(1);
            }}
          />
          <span className="muted">{data?.total ?? 0} records</span>
        </div>
        {loading ? (
          <Loading />
        ) : (
          <div className="table-scroll">
            <table>
              <thead>
                <tr>
                  {config.columns.map((key) => (
                    <th key={key}>
                      {key === "full_name_en" ? "Officer name" : label(key)}
                    </th>
                  ))}
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {data?.data.map((row) => (
                  <tr key={row.id}>
                    {config.columns.map((key) => (
                      <td key={key}>{cell(row, key)}</td>
                    ))}
                    <td>
                      <div className="actions">
                        {resource === "officers" && (
                          <Link to={`/officers/${row.id}`}>Profile</Link>
                        )}
                        <button
                          className="link-button"
                          onClick={() => setRecord(row)}
                        >
                          {admin ? "Edit" : "View"}
                        </button>
                        {resource === "documents" && (
                          <a
                            href={`/api/documents/${row.id}/download`}
                            target="_blank"
                            rel="noreferrer"
                          >
                            Download
                          </a>
                        )}
                        {admin &&
                          resource === "officers" &&
                          row.user?.status === "pending_verification" && (
                            <button
                              disabled={busy}
                              className="link-button"
                              onClick={() =>
                                action(`/officers/${row.id}/verify`, "POST")
                              }
                            >
                              Approve
                            </button>
                          )}
                        {user.role === "main_admin" && (
                          <button
                            className="link-button danger"
                            disabled={busy}
                            onClick={() => {
                              if (
                                window.confirm(
                                  "Delete this record? This cannot be undone.",
                                )
                              )
                                action(
                                  `/records/${resource}/${row.id}`,
                                  "DELETE",
                                );
                            }}
                          >
                            Delete
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
                {!data?.data.length && (
                  <tr>
                    <td className="empty" colSpan={config.columns.length + 1}>
                      No records found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
        <Pagination data={data} onPage={setPage} />
      </section>
    </>
  );
}
