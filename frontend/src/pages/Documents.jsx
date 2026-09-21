import { canManage } from "../lib/permissions";
import { useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useApi, date, label } from "../lib/hooks";
import { useAuth } from "../lib/auth";
import { api, formPayload } from "../lib/api";
import {
  Alert,
  Heading,
  Loading,
  Field,
  Pagination,
  Badge,
} from "../components/UI";
import OfficerSelect from "../components/OfficerSelect";
export function UploadDocument() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const { user } = useAuth();
  const [values, setValues] = useState({
    officer_id: params.get("officer_id") || "",
    issue_date: new Date().toISOString().slice(0, 10),
  });
  const [file, setFile] = useState(null);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const { data: meta, error: metaError } = useApi("/metadata");
  const set = (key, v) => setValues({ ...values, [key]: v });
  function accept(f) {
    if (!f) return;
    if (
      !["application/pdf", "image/png", "image/jpeg"].includes(f.type) ||
      f.size > 10 * 1024 * 1024
    ) {
      setError(
        new Error("Choose a PDF, JPEG or PNG file no larger than 10 MB."),
      );
      setFile(null);
      return;
    }
    setError(null);
    setFile(f);
  }
  async function submit(e) {
    e.preventDefault();
    if (!file) return setError(new Error("Choose a document file."));
    setBusy(true);
    try {
      await api("/records/documents", {
        method: "POST",
        body: formPayload({ ...values, file }),
      });
      navigate("/documents");
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  }
  if (!canManage(user))
    return (
      <Alert
        error={new Error("Document uploads require administrator access.")}
      />
    );
  return (
    <>
      <Heading
        title="Upload Document"
        description="Add an official document to an officer record."
      />
      <section className="panel">
        <Alert error={error || metaError} />
        <form onSubmit={submit}>
          <fieldset disabled={busy}>
            <OfficerSelect
              value={values.officer_id}
              onChange={(v) => set("officer_id", v)}
            />
            <div className="form-grid">
              <Field
                name="document_type"
                title="Document Type"
                required
                options={meta?.document_types || []}
                value={values.document_type}
                onChange={(v) => set("document_type", v)}
              />
              {[
                ["ref_no", "Reference Number", "text"],
                ["issue_date", "Issued Date", "date"],
                ["issuing_authority", "Issuing Authority", "text"],
              ].map(([name, title, type]) => (
                <Field
                  key={name}
                  name={name}
                  title={title}
                  type={type}
                  required={name === "issue_date"}
                  value={values[name]}
                  onChange={(v) => set(name, v)}
                />
              ))}
            </div>
            <div
              className="drop-zone"
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                e.preventDefault();
                accept(e.dataTransfer.files[0]);
              }}
            >
              <h2>Drop your document here</h2>
              <p className="muted">PDF, JPEG or PNG · Maximum 10 MB</p>
              <Field
                name="file"
                title="Choose document"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                onChange={accept}
              />
              {file && (
                <p>
                  {file.name} ({Math.ceil(file.size / 1024)} KB)
                </p>
              )}
            </div>
          </fieldset>
          <div className="form-footer">
            <Link className="button secondary" to="/documents">
              Cancel
            </Link>
            <button disabled={busy || !file}>
              {busy ? "Uploading…" : "Upload Document"}
            </button>
          </div>
        </form>
      </section>
    </>
  );
}
export default function Documents() {
  const { user } = useAuth();
  const [filters, setFilters] = useState({});
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState(null);
  const [revision, setRevision] = useState(0);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const {
    data,
    loading,
    error: loadError,
  } = useApi(
    `/records/documents?page=${page}&${new URLSearchParams(filters)}`,
    revision,
  );
  const { data: meta } = useApi("/metadata");
  const set = (key, v) => {
    setFilters({ ...filters, [key]: v });
    setPage(1);
  };
  async function remove() {
    if (!window.confirm("Delete this document record?")) return;
    setBusy(true);
    try {
      await api(`/records/documents/${selected.id}`, { method: "DELETE" });
      setSelected(null);
      setRevision(revision + 1);
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  }
  return (
    <>
      <Heading
        title="Documents"
        description="Search, filter and manage official documents, certificates and correspondence."
      />
      <Alert error={error || loadError} />
      <section className="panel">
        <div className="filters-grid">
          <Field
            name="search"
            title="Search Documents"
            value={filters.search}
            onChange={(v) => set("search", v)}
          />
          <Field
            name="document_type"
            title="Document Type"
            options={meta?.document_types || []}
            value={filters.document_type}
            onChange={(v) => set("document_type", v)}
          />
          {[
            ["ref_no", "Reference Number", "text"],
            ["issuing_authority", "Issuing Authority", "text"],
            ["from", "From Date", "date"],
            ["to", "To Date", "date"],
          ].map(([name, title, type]) => (
            <Field
              key={name}
              name={name}
              title={title}
              type={type}
              value={filters[name]}
              onChange={(v) => set(name, v)}
            />
          ))}
          <Field
            name="format"
            title="File Format"
            options={["pdf", "png", "jpg", "jpeg"].map((v) => ({
              value: v,
              label: v.toUpperCase(),
            }))}
            value={filters.format}
            onChange={(v) => set("format", v)}
          />
          <button
            className="secondary"
            onClick={() => {
              setFilters({});
              setPage(1);
            }}
          >
            Reset Filters
          </button>
        </div>
        <details>
          <summary>Filter by officer</summary>
          <OfficerSelect
            value={filters.officer_id}
            onChange={(v) => set("officer_id", v)}
          />
        </details>
      </section>
      <div className="quick-actions">
        <a
          className="button secondary"
          href={`/api/records/documents?${new URLSearchParams({ ...filters, export: "xlsx" })}`}
        >
          Bulk Export (Excel)
        </a>
        <div>
          <h2>Quick Actions</h2>
          <p className="muted">{data?.total ?? 0} authorized documents</p>
        </div>
        {canManage(user) && (
          <Link className="button teal" to="/documents/upload">
            Upload Document
          </Link>
        )}
      </div>
      <div className={selected ? "document-layout" : ""}>
        <section className="panel">
          <h2>Documents ({data?.total ?? 0})</h2>
          {loading ? (
            <Loading />
          ) : (
            <div className="table-scroll">
              <table>
                <thead>
                  <tr>
                    {[
                      "Document Type",
                      "Officer",
                      "Reference No",
                      "Issued Date",
                      "Issuing Authority",
                      "File Type",
                      "Actions",
                    ].map((v) => (
                      <th key={v}>{v}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {data?.data.map((d) => (
                    <tr key={d.id}>
                      <td>{label(d.document_type)}</td>
                      <td>{d.officer?.full_name_en}</td>
                      <td>{d.ref_no || "—"}</td>
                      <td>{date(d.issue_date)}</td>
                      <td>{d.issuing_authority || "Not recorded"}</td>
                      <td>
                        <Badge value={d.file_path?.split(".").pop()} />
                      </td>
                      <td>
                        <button
                          className="link-button"
                          onClick={() => setSelected(d)}
                        >
                          View
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {!data?.data.length && <p className="empty">No records found.</p>}
            </div>
          )}
          <Pagination data={data} onPage={setPage} />
        </section>
        {selected && (
          <section className="panel">
            <div className="section-heading">
              <h2>{label(selected.document_type)}</h2>
              <button
                className="link-button"
                aria-label="Close document preview"
                onClick={() => setSelected(null)}
              >
                ×
              </button>
            </div>
            <p>{selected.ref_no}</p>
            <iframe
              className="document-preview"
              title="Document preview"
              src={`/api/documents/${selected.id}/download?inline=1`}
            />
            <div className="actions">
              <a
                className="button"
                href={`/api/documents/${selected.id}/download`}
                target="_blank"
                rel="noreferrer"
              >
                Download
              </a>
              {user.role === "main_admin" && (
                <button
                  className="secondary danger"
                  disabled={busy}
                  onClick={remove}
                >
                  Delete
                </button>
              )}
            </div>
            <h3>Document Details</h3>
            <p className="muted">
              Officer: {selected.officer?.full_name_en}
              <br />
              Issued: {date(selected.issue_date)}
              <br />
              Authority: {selected.issuing_authority || "Not recorded"}
            </p>
          </section>
        )}
      </div>
    </>
  );
}
