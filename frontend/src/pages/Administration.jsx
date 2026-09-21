import { useState } from "react";
import { Link } from "react-router-dom";
import { useApi, label } from "../lib/hooks";
import { useAuth } from "../lib/auth";
import { LanguageSelect } from "../lib/i18n";
import { Alert, Heading, Loading, Field, Pagination } from "../components/UI";
export function AuditLogs() {
  const { user } = useAuth();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState(null);
  const { data, loading, error } = useApi(
    `/audit-logs?page=${page}&search=${encodeURIComponent(search)}`,
  );
  if (user.role !== "main_admin")
    return (
      <Alert
        error={
          new Error(
            "Audit records are available only to the system administrator.",
          )
        }
      />
    );
  return (
    <>
      <Heading
        title="Audit Logs"
        description="Read-only history of recorded system actions."
      />
      <Alert error={error} />
      <section className="panel">
        <Field
          name="search"
          title="Search action, user, entity or IP address"
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
        />
        {loading ? (
          <Loading />
        ) : (
          <div className="table-scroll">
            <table>
              <thead>
                <tr>
                  {[
                    "Date & Time",
                    "User",
                    "Action",
                    "Entity",
                    "IP Address",
                    "Details",
                  ].map((v) => (
                    <th key={v}>{v}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {data?.data.map((row) => (
                  <tr key={row.id}>
                    <td>{new Date(row.created_at).toLocaleString()}</td>
                    <td>{row.user?.name || "System"}</td>
                    <td>{row.action}</td>
                    <td>
                      {row.auditable_type?.split("\\").pop()} #
                      {row.auditable_id}
                    </td>
                    <td>{row.ip_address}</td>
                    <td>
                      <button
                        className="link-button"
                        onClick={() => setSelected(row)}
                      >
                        View details
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {!data?.data.length && (
              <p className="empty">No audit records found.</p>
            )}
          </div>
        )}
        <Pagination data={data} onPage={setPage} />
      </section>
      {selected && (
        <section className="panel">
          <div className="section-heading">
            <h2>
              {selected.action} · #{selected.auditable_id}
            </h2>
            <button className="secondary" onClick={() => setSelected(null)}>
              Close
            </button>
          </div>
          <div className="chart-grid">
            {[
              ["Previous values", selected.old_values],
              ["New values", selected.new_values],
            ].map(([title, values]) => (
              <div key={title}>
                <h3>{title}</h3>
                <pre className="audit-values">
                  {JSON.stringify(values, null, 2) || "No values recorded"}
                </pre>
              </div>
            ))}
          </div>
        </section>
      )}
    </>
  );
}
export function Settings() {
  const { user } = useAuth();
  const { data: meta, error } = useApi("/metadata");
  return (
    <>
      <Heading
        title="Settings"
        description="System configuration and account preferences."
      />
      <Alert error={error} />
      <div className="settings-list">
        <section className="panel">
          <h2>General</h2>
          <p>GN-POMS</p>
          <p className="muted">
            Grama Niladhari & Public Officers Management System
          </p>
        </section>
        <section className="panel">
          <h2>Languages</h2>
          <LanguageSelect />
          <p className="muted">
            Navigation is available in English, Sinhala and Tamil. Full page
            translations are being prepared.
          </p>
        </section>
        <section className="panel">
          <h2>Security</h2>
          <p>{user.name}</p>
          <p className="muted">{label(user.role)} · Session authentication</p>
          <p className="muted">
            MFA and self-service password recovery require additional backend
            support.
          </p>
        </section>
        {[
          ["Grades", "grades"],
          ["Document Types", "document_types"],
          ["Service Statuses", "service_statuses"],
        ].map(([title, key]) => (
          <section className="panel" key={key}>
            <h2>{title}</h2>
            {meta?.[key]?.map((o) => (
              <p key={o.value}>{o.label}</p>
            ))}
            <small>Configured centrally in Laravel.</small>
          </section>
        ))}
        <section className="panel">
          <h2>Letter Templates</h2>
          <p className="muted">
            Templates are managed through letter batch settings.
          </p>
          {user.role !== "officer" && (
            <Link to="/letters/batches">Manage letter batches</Link>
          )}
        </section>
        {["main_admin", "district_admin"].includes(user.role) && (
          <section className="panel">
            <h2>Signatories</h2>
            <p className="muted">Authorized signatories and signatures.</p>
            <Link to="/signatories">Manage signatories</Link>
          </section>
        )}
        <section className="panel">
          <h2>Locations & Designations</h2>
          <p className="muted">
            Districts and divisions use the existing location directory.
            Designations are recorded on officer profiles.
          </p>
          <Link to="/officers">Open officer directory</Link>
        </section>
      </div>
    </>
  );
}
