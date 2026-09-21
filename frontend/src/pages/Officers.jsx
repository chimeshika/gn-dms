import { canManage } from "../lib/permissions";
import { useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { useApi, date, label } from "../lib/hooks";
import { useAuth } from "../lib/auth";
import { Alert, Heading, Loading, Badge } from "../components/UI";
import { Editor } from "./Records";

export function OfficerForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { data, loading, error } = useApi(id ? `/officers/${id}` : "/metadata");
  if (!canManage(user))
    return (
      <Alert
        error={new Error("You do not have permission to edit officers.")}
      />
    );
  if (loading) return <Loading />;
  if (error) return <Alert error={error} />;
  return (
    <>
      <Heading
        title={id ? "Edit Officer" : "Add New Officer"}
        description="Maintain accurate personal and service records."
      />
      <Editor
        key={id || "new"}
        resource="officers"
        record={id ? data : {}}
        stepped
        onClose={() => navigate("/officers")}
        onSaved={() => navigate(id ? `/officers/${id}` : "/officers")}
      />
    </>
  );
}

function Information({ title, items }) {
  return (
    <section className="panel">
      <h2>{title}</h2>
      <dl>
        {items.map(([key, value]) => (
          <div key={key}>
            <dt>{key}</dt>
            <dd>{value || "Not recorded"}</dd>
          </div>
        ))}
      </dl>
    </section>
  );
}
export function OfficerProfile() {
  const { id } = useParams();
  const { user } = useAuth();
  const { data: o, loading, error } = useApi(`/officers/${id}`);
  const [tab, setTab] = useState("Overview");
  if (loading) return <Loading />;
  if (error) return <Alert error={error} />;
  if (!o) return null;
  const admin = canManage(user);
  const history = (
    <section className="panel">
      <h2>Service History</h2>
      <div className="table-scroll">
        <table>
          <thead>
            <tr>
              {[
                "Effective Date",
                "Event",
                "Reference",
                "Previous Value",
                "New Value",
                "Remarks",
              ].map((v) => (
                <th key={v}>{v}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {[...(o.service_histories || [])]
              .sort((a, b) =>
                String(b.effective_date).localeCompare(
                  String(a.effective_date),
                ),
              )
              .map((h) => (
                <tr key={h.id}>
                  <td>{date(h.effective_date)}</td>
                  <td>{label(h.event_type)}</td>
                  <td>{h.ref_no || "—"}</td>
                  <td>{h.old_value || "—"}</td>
                  <td>{h.new_value || "—"}</td>
                  <td>{h.description || "—"}</td>
                </tr>
              ))}
          </tbody>
        </table>
        {!o.service_histories?.length && (
          <p className="empty">No service history recorded.</p>
        )}
      </div>
    </section>
  );
  return (
    <>
      <Heading title="Officer Profile" description="Officers / Officer Profile">
        <Link to="/officers">Back to Officers</Link>
        {admin && (
          <>
            <Link className="button" to={`/officers/${id}/edit`}>
              Edit Profile
            </Link>
            <Link
              className="button teal"
              to={`/documents/upload?officer_id=${id}`}
            >
              Upload Document
            </Link>
            <Link
              className="button secondary"
              to={`/letters/generate?officer_id=${id}`}
            >
              Generate Letter
            </Link>
          </>
        )}
      </Heading>
      <section className="panel profile-summary">
        <span className="avatar">{o.full_name_en.slice(0, 1)}</span>
        <div>
          <h1>
            {o.full_name_en} <Badge value={o.service_status} />
          </h1>
          <p>Officer ID: OFF-{o.id}</p>
          <div className="profile-grid">
            {[
              ["NIC", o.nic_no],
              ["Designation", o.designation],
              ["Grade", label(o.current_grade)],
              ["DS Division", o.ds_division?.name_en],
              ["GN Division", o.gn_division?.name_en],
            ].map(([k, v]) => (
              <div key={k}>
                <small>{k}</small>
                {v || "Not recorded"}
              </div>
            ))}
          </div>
        </div>
      </section>
      <div className="tabs" role="tablist" aria-label="Officer sections">
        {[
          "Overview",
          "Service History",
          ...(user.role === "ministry_head" ? [] : ["Documents", "Letters"]),
          "Emergency Contacts",
          "Dependents",
        ].map((t) => (
          <button
            role="tab"
            aria-selected={t === tab}
            key={t}
            className={t === tab ? "active" : ""}
            onClick={() => setTab(t)}
          >
            {t}
          </button>
        ))}
      </div>
      {tab === "Overview" && (
        <>
          <div className="info-grid">
            <Information
              title="Personal Information"
              items={[
                ["Full Name", o.full_name_en],
                ["Sinhala", o.full_name_si],
                ["Tamil", o.full_name_ta],
                ["Date of Birth", date(o.dob)],
                ["Gender", label(o.gender)],
                ["NIC", o.nic_no],
              ]}
            />
            <Information
              title="Contact Information"
              items={[
                ["Mobile", o.mobile_phone || o.user?.phone],
                ["Email", o.contact_email || o.user?.email],
                [
                  "Address",
                  [o.address_line1, o.address_line2, o.address_line3]
                    .filter(Boolean)
                    .join(", "),
                ],
              ]}
            />
            <Information
              title="Service Information"
              items={[
                ["Designation", o.designation],
                ["Grade", label(o.current_grade)],
                ["District", o.district?.name_en],
                ["DS Division", o.ds_division?.name_en],
                ["GN Division", o.gn_division?.name_en],
                ["First Appointment", date(o.first_appointment_date)],
              ]}
            />
          </div>
          {history}
        </>
      )}
      {tab === "Service History" && (
        <>
          {history}
          {admin && (
            <Link className="button secondary" to="/service-histories">
              Manage service events
            </Link>
          )}
        </>
      )}
      {tab === "Documents" && (
        <section className="panel">
          <h2>Officer Documents</h2>
          {o.documents?.map((d) => (
            <p key={d.id}>
              <a
                href={`/api/documents/${d.id}/download`}
                target="_blank"
                rel="noreferrer"
              >
                {label(d.document_type)} · {d.ref_no || date(d.issue_date)}
              </a>
            </p>
          ))}
          {!o.documents?.length && (
            <p className="empty">No documents recorded.</p>
          )}
        </section>
      )}
      {tab === "Letters" && (
        <section className="panel">
          <Link className="button secondary" to={`/letters?officer_id=${id}`}>
            View officer letters
          </Link>
        </section>
      )}
      {tab === "Emergency Contacts" && (
        <div className="info-grid">
          <Information
            title="Emergency Contact"
            items={[
              ["Name", o.emergency_contact_name],
              ["Relationship", o.emergency_contact_relationship],
              ["Phone", o.emergency_contact_phone],
            ]}
          />
        </div>
      )}
      {tab === "Dependents" && (
        <section className="panel">
          <h2>Dependents</h2>
          <p className="muted">
            Previously recorded count: {o.dependants_count ?? "Not recorded"}
          </p>
          {o.dependents?.map((d, i) => (
            <p key={i}>
              {d.name} · {d.relationship} · {date(d.dob)}
            </p>
          ))}
          {!o.dependents?.length && (
            <p className="empty">No individual dependent records.</p>
          )}
        </section>
      )}
    </>
  );
}
