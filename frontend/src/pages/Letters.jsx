import { useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { api } from "../lib/api";
import { useApi, date, label } from "../lib/hooks";
import {
  Alert,
  Badge,
  Field,
  Heading,
  Loading,
  Pagination,
} from "../components/UI";
import OfficerSelect from "../components/OfficerSelect";

function BatchForm({ batch, onSaved, onClose }) {
  const [values, setValues] = useState(() => ({
    name: "",
    document_type: "appointment",
    letter_date: new Date().toISOString().slice(0, 10),
    ...Object.fromEntries(
      Object.entries(batch || {}).map(([key, value]) => [
        key,
        key.endsWith("date") && value ? date(value) : value,
      ]),
    ),
  }));
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const { data: meta, error: metaError } = useApi("/metadata");
  const { data: locations, error: locationsError } = useApi(
    `/locations?district_id=${values.district_id || ""}`,
  );
  const set = (name, v) =>
    setValues((current) => ({
      ...current,
      [name]: v,
      ...(name === "district_id" ? { ds_division_id: "" } : {}),
    }));
  const submit = async (e) => {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const data = await api(`/batches${batch ? "/" + batch.id : ""}`, {
        method: batch ? "PUT" : "POST",
        body: values,
      });
      onSaved(data);
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };
  return (
    <section className="panel">
      <div className="section-heading">
        <h2>{batch ? "Batch settings" : "New letter batch"}</h2>
        {onClose && (
          <button className="secondary" onClick={onClose}>
            Close
          </button>
        )}
      </div>
      <Alert error={error || metaError || locationsError} />
      <form onSubmit={submit}>
        <div className="form-grid">
          <Field
            name="name"
            title="Batch name"
            required
            value={values.name}
            onChange={(v) => set("name", v)}
          />
          <Field
            name="document_type"
            title="Document type"
            required
            options={meta?.document_types || []}
            value={values.document_type}
            onChange={(v) => set("document_type", v)}
          />
          {[
            ["my_ref_no", "Reference number", "text"],
            ["letter_date", "Letter date", "date"],
            ["cabinet_app_no", "Cabinet approval number", "text"],
            ["cabinet_app_date", "Cabinet approval date", "date"],
            ["exam_date", "Exam date", "date"],
            ["probation_effective_date", "Effective date", "date"],
            ["training_complete_date", "Training completion date", "date"],
          ].map(([name, title, type]) => (
            <Field
              key={name}
              {...{ name, title, type }}
              required={name === "letter_date"}
              value={values[name]}
              onChange={(v) => set(name, v)}
            />
          ))}
          <Field
            name="district_id"
            title="Target district"
            options={locations?.districts || []}
            value={values.district_id}
            onChange={(v) => set("district_id", v)}
          />
          <Field
            name="ds_division_id"
            title="Target DS division"
            options={locations?.ds_divisions || []}
            disabled={!values.district_id}
            value={values.ds_division_id}
            onChange={(v) => set("ds_division_id", v)}
          />
          <Field
            name="content_template"
            title="Letter body template (optional HTML)"
            type="textarea"
            value={values.content_template}
            onChange={(v) => set("content_template", v)}
            placeholder="Use placeholders such as {full_name_en}, {nic_no}, {address}, and {letter_date}."
          />
        </div>
        <button disabled={busy}>{busy ? "Saving…" : "Save batch"}</button>
      </form>
    </section>
  );
}
export function BatchList() {
  const [page, setPage] = useState(1);
  const [create, setCreate] = useState(false);
  const { data, loading, error } = useApi(`/batches?page=${page}`);
  const navigate = useNavigate();
  return (
    <>
      <Heading
        title="Letter batches"
        description="Prepare official correspondence for one officer or an entire batch."
      >
        <button onClick={() => setCreate((v) => !v)}>+ New batch</button>
      </Heading>
      <Alert error={error} />
      {create && (
        <BatchForm
          onSaved={(batch) => navigate(`/letters/batches/${batch.id}`)}
          onClose={() => setCreate(false)}
        />
      )}
      <section className="panel">
        {loading ? (
          <Loading />
        ) : (
          <div className="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Batch</th>
                  <th>Document type</th>
                  <th>Date</th>
                  <th>Letters</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                {data?.data.map((batch) => (
                  <tr key={batch.id}>
                    <td>
                      <strong>{batch.name}</strong>
                    </td>
                    <td>{label(batch.document_type)}</td>
                    <td>{date(batch.letter_date)}</td>
                    <td>{batch.letters_count}</td>
                    <td>
                      <Link to={`/letters/batches/${batch.id}`}>
                        Open batch →
                      </Link>
                    </td>
                  </tr>
                ))}
                {!data?.data.length && (
                  <tr>
                    <td className="empty" colSpan={5}>
                      No batches yet. Create your first batch to get started.
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
export function BatchDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [revision, setRevision] = useState(0);
  const {
    data: batch,
    loading,
    error: loadError,
  } = useApi(`/batches/${id}`, revision);
  const [selected, setSelected] = useState([]);
  const [file, setFile] = useState(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const [message, setMessage] = useState("");
  const [skipped, setSkipped] = useState([]);
  const [edit, setEdit] = useState(false);
  const action = async (path, body, method = "POST") => {
    setBusy(true);
    setError(null);
    setMessage("");
    try {
      const result = await api(path, { method, body });
      setMessage(result.message);
      setSkipped(result.skipped || []);
      setRevision((v) => v + 1);
      return true;
    } catch (e) {
      setError(e);
      return false;
    } finally {
      setBusy(false);
    }
  };
  if (loading) return <Loading />;
  if (!batch) return <Alert error={loadError} />;
  return (
    <>
      <Link className="back-link" to="/letters">
        ← Letter batches
      </Link>
      <Heading
        title={batch.name}
        description={`${label(batch.document_type)} · ${date(batch.letter_date)}`}
      >
        <button
          className="secondary"
          onClick={() => setEdit((v) => !v)}
          disabled={batch.letters.length > 0}
        >
          Edit settings
        </button>
        {batch.letters.some((l) => l.status === "final") && (
          <a className="button secondary" href={`/api/batches/${id}/download`}>
            Download finalized ZIP
          </a>
        )}
        <button
          className="secondary danger"
          disabled={busy || batch.letters.some((l) => l.status === "final")}
          onClick={async () => {
            if (
              window.confirm("Delete this batch and its drafts?") &&
              (await action(`/batches/${id}`, null, "DELETE"))
            )
              navigate("/letters");
          }}
        >
          Delete batch
        </button>
      </Heading>
      <Alert error={error || loadError} message={message} />
      {skipped.length > 0 && (
        <div className="notice">
          <strong>Skipped rows</strong>
          <ul>
            {skipped.map((row, i) => (
              <li key={i}>
                Row {row.row}: {row.reason}
              </li>
            ))}
          </ul>
        </div>
      )}
      {edit && (
        <BatchForm
          batch={batch}
          onClose={() => setEdit(false)}
          onSaved={() => {
            setEdit(false);
            setRevision((v) => v + 1);
          }}
        />
      )}
      <div className="split-panels">
        <section className="panel">
          <h2>Import officers</h2>
          <p className="muted">
            Upload CSV or Excel, up to 4 MB. Required columns: nic_no and
            full_name_en.
          </p>
          <form
            onSubmit={async (e) => {
              e.preventDefault();
              const body = new FormData();
              body.append("file", file);
              await action(`/batches/${id}/import`, body);
            }}
          >
            <Field
              name="file"
              title="Officer spreadsheet"
              type="file"
              accept=".csv,.txt,.xlsx,.xls"
              required
              onChange={setFile}
            />
            <button disabled={busy || !file}>Import & generate drafts</button>
          </form>
        </section>
        <section className="panel">
          <h2>Select existing officers</h2>
          <OfficerSelect multiple value={selected} onChange={setSelected} />
          <button
            disabled={busy || !selected.length}
            onClick={() =>
              action(`/batches/${id}/generate`, { officer_ids: selected })
            }
          >
            Generate {selected.length || ""} drafts
          </button>
        </section>
      </div>
      <section className="panel">
        <h2>
          Letters <span className="muted">({batch.letters.length})</span>
        </h2>
        <div className="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Officer</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {batch.letters.map((letter) => (
                <tr key={letter.id}>
                  <td>{letter.officer?.full_name_en}</td>
                  <td>{letter.ref_no}</td>
                  <td>
                    <Badge value={letter.status} />
                  </td>
                  <td>
                    <div className="actions">
                      <Link to={`/letters/${letter.id}/edit`}>
                        {letter.status === "final" ? "View" : "Edit"}
                      </Link>
                      <a
                        href={`/api/letters/${letter.id}/pdf`}
                        target="_blank"
                        rel="noreferrer"
                      >
                        PDF
                      </a>
                      {letter.status !== "final" && (
                        <button
                          disabled={busy}
                          className="link-button"
                          onClick={() => {
                            if (
                              window.confirm(
                                "Finalize and archive this letter? It will become read-only.",
                              )
                            )
                              action(`/letters/${letter.id}/finalize`);
                          }}
                        >
                          Finalize
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {!batch.letters.length && (
                <tr>
                  <td className="empty" colSpan={4}>
                    Import or select officers to generate letters.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </section>
    </>
  );
}
export function LetterEditor() {
  const { id } = useParams();
  const [revision, setRevision] = useState(0);
  const { data, loading, error } = useApi(`/letters/${id}`, revision);
  if (loading) return <Loading />;
  if (error) return <Alert error={error} />;
  return (
    <LetterForm
      key={`${id}-${revision}`}
      letter={data}
      onRefresh={() => setRevision((v) => v + 1)}
    />
  );
}
function LetterForm({ letter, onRefresh }) {
  const navigate = useNavigate();
  const [values, setValues] = useState({
    ...letter,
    cc_to: (letter.cc_to || []).join("\n"),
  });
  const { data: meta } = useApi("/metadata");
  const [error, setError] = useState(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [preview, setPreview] = useState(false);
  const isFinal = letter.status === "final";
  const set = (name, v) => setValues((current) => ({ ...current, [name]: v }));
  const mutate = async (method, suffix = "", body) => {
    setBusy(true);
    setError(null);
    try {
      const result = await api(`/letters/${letter.id}${suffix}`, {
        method,
        body,
      });
      setMessage(result.message || "Letter saved.");
      return true;
    } catch (e) {
      setError(e);
      return false;
    } finally {
      setBusy(false);
    }
  };
  const save = () =>
    mutate("PUT", "", {
      ref_no: values.ref_no,
      subject: values.subject,
      body: values.body,
      cc_to: values.cc_to.split("\n").filter((v) => v.trim()),
      signatory_id: values.signatory_id || null,
      controlling_officer_id: values.controlling_officer_id || null,
    });
  return (
    <>
      <Link
        className="back-link"
        to={`/letters/batches/${letter.letter_batch_id}`}
      >
        ← Back to batch
      </Link>
      <Heading
        title={letter.officer?.full_name_en || "Letter"}
        description={letter.ref_no}
      >
        <Badge value={letter.status} />
        <button
          className="secondary"
          disabled={busy}
          onClick={async () => {
            if (isFinal || (await save())) setPreview((v) => !v);
          }}
        >
          {preview ? "Hide preview" : "Preview PDF"}
        </button>
        <a
          className="button secondary"
          href={`/api/letters/${letter.id}/pdf`}
          target="_blank"
          rel="noreferrer"
        >
          Open PDF
        </a>
      </Heading>
      <Alert error={error} message={message} />
      <section className="panel">
        <form
          onSubmit={async (e) => {
            e.preventDefault();
            await save();
            setPreview(false);
          }}
        >
          <fieldset disabled={busy || isFinal}>
            <div className="form-grid">
              <Field
                name="ref_no"
                title="Reference number"
                required
                value={values.ref_no}
                onChange={(v) => set("ref_no", v)}
              />
              <Field
                name="subject"
                required
                value={values.subject}
                onChange={(v) => set("subject", v)}
              />
              <Field
                name="body"
                title="Letter body (HTML and placeholders supported)"
                type="textarea"
                value={values.body}
                onChange={(v) => set("body", v)}
              />
              <Field
                name="cc_to"
                title="Copies to (one recipient per line)"
                type="textarea"
                value={values.cc_to}
                onChange={(v) => set("cc_to", v)}
              />
              <Field
                name="signatory_id"
                title="Signatory"
                options={meta?.signatories || []}
                value={values.signatory_id}
                onChange={(v) => set("signatory_id", v)}
              />
              <Field
                name="controlling_officer_id"
                title="Controlling officer"
                options={meta?.signatories || []}
                value={values.controlling_officer_id}
                onChange={(v) => set("controlling_officer_id", v)}
              />
            </div>
          </fieldset>
          {!isFinal && (
            <div className="actions">
              <button disabled={busy}>Save draft</button>
              <button
                type="button"
                disabled={busy}
                className="secondary"
                onClick={async () => {
                  if (
                    window.confirm(
                      "Save, finalize, and archive this letter?",
                    ) &&
                    (await save()) &&
                    (await mutate("POST", "/finalize"))
                  )
                    onRefresh();
                }}
              >
                Finalize letter
              </button>
              <button
                type="button"
                disabled={busy}
                className="secondary danger"
                onClick={async () => {
                  if (
                    window.confirm("Delete this draft?") &&
                    (await mutate("DELETE"))
                  )
                    navigate(`/letters/batches/${letter.letter_batch_id}`);
                }}
              >
                Delete draft
              </button>
            </div>
          )}
        </form>
      </section>
      {preview && (
        <iframe
          className="pdf-preview"
          title="Letter PDF preview"
          src={`/api/letters/${letter.id}/pdf?preview=${Date.now()}`}
        />
      )}
    </>
  );
}
