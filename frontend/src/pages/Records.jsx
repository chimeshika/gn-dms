import { useState } from "react";
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

function Editor({ resource, record, readOnly, onClose, onSaved }) {
  const config = resources[resource];
  const { user } = useAuth();
  const [values, setValues] = useState(() => {
    const initial = {
      is_active: true,
      gender: "male",
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
      <form onSubmit={submit}>
        <fieldset disabled={busy || readOnly}>
          <div className="form-grid">
            {config.fields.map(([name, title, type = "text", required]) => {
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
              if (readOnly && ["password", "file"].includes(type)) return null;
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
              ].includes(type);
              return (
                <Field
                  key={name}
                  {...{ name, title, type }}
                  required={
                    required ||
                    (name === "password" && !record?.id) ||
                    (resource === "documents" && name === "file" && !record?.id)
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
          <button disabled={busy}>{busy ? "Saving…" : "Save record"}</button>
        )}
      </form>
    </section>
  );
}
export default function Records({ resource }) {
  const { user } = useAuth();
  const config = resources[resource];
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
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
    `/records/${resource}?page=${page}&search=${encodeURIComponent(search)}`,
    revision,
  );
  const admin = user.role !== "officer";
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
    key === "officer" ? (
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
        {admin && <button onClick={() => setRecord({})}>+ Add record</button>}
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
