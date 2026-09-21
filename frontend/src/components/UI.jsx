import { label } from "../lib/hooks";

export function Alert({ error, message }) {
  if (!error && !message) return null;
  const details = Object.entries(error?.errors || {});
  return (
    <div
      role={error ? "alert" : "status"}
      className={`notice ${error ? "error" : "success"}`}
    >
      {error?.message || message}
      {details.length > 0 && (
        <ul>
          {details.map(([field, messages]) => (
            <li key={field}>
              {label(field)}: {messages.join(" ")}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
export function Loading() {
  return (
    <div role="status" className="empty">
      Loading…
    </div>
  );
}
export function Heading({ title, description, children }) {
  return (
    <div className="page-heading">
      <div>
        <p className="eyebrow">GN-POMS / WORKSPACE</p>
        <h1>{title}</h1>
        {description && <p className="muted">{description}</p>}
      </div>
      <div className="actions">{children}</div>
    </div>
  );
}
export function Badge({ value }) {
  return (
    <span
      className={`badge ${value === "active" || value === "final" || value === "confirmed" ? "positive" : ""}`}
    >
      {label(value) || "Not set"}
    </span>
  );
}
export function Pagination({ data, onPage }) {
  if (!data || data.last_page <= 1) return null;
  return (
    <div className="pagination">
      <button
        className="secondary"
        disabled={data.current_page <= 1}
        onClick={() => onPage(data.current_page - 1)}
      >
        Previous
      </button>
      <span>
        Page {data.current_page} of {data.last_page} · {data.total} records
      </span>
      <button
        className="secondary"
        disabled={data.current_page >= data.last_page}
        onClick={() => onPage(data.current_page + 1)}
      >
        Next
      </button>
    </div>
  );
}
export function Field({
  name,
  title,
  type = "text",
  value,
  onChange,
  options,
  required = false,
  ...props
}) {
  const id = `field-${name}`;
  return (
    <label
      className={type === "textarea" ? "field wide" : "field"}
      htmlFor={id}
    >
      <span>
        {title || label(name)}
        {required ? " *" : ""}
      </span>
      {options ? (
        <select
          id={id}
          name={name}
          value={value ?? ""}
          required={required}
          onChange={(e) => onChange(e.target.value)}
          {...props}
        >
          <option value="">Select…</option>
          {options.map((option) => (
            <option
              key={option.value ?? option.id}
              value={option.value ?? option.id}
            >
              {option.label ??
                option.name_en ??
                option.full_name_en ??
                option.officer_name}
            </option>
          ))}
        </select>
      ) : type === "textarea" ? (
        <textarea
          id={id}
          name={name}
          rows={6}
          value={value ?? ""}
          required={required}
          onChange={(e) => onChange(e.target.value)}
          {...props}
        />
      ) : type === "checkbox" ? (
        <input
          id={id}
          name={name}
          type="checkbox"
          checked={Boolean(value)}
          onChange={(e) => onChange(e.target.checked)}
          {...props}
        />
      ) : type === "file" ? (
        <input
          id={id}
          name={name}
          type="file"
          required={required}
          onChange={(e) => onChange(e.target.files[0])}
          {...props}
        />
      ) : (
        <input
          id={id}
          name={name}
          type={type}
          value={value ?? ""}
          required={required}
          onChange={(e) => onChange(e.target.value)}
          {...props}
        />
      )}
    </label>
  );
}
