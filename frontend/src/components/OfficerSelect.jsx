import { useState } from "react";
import { useApi } from "../lib/hooks";
import { Alert, Field, Pagination } from "./UI";

export default function OfficerSelect({
  value,
  onChange,
  selectedOfficer,
  multiple = false,
}) {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const { data, loading, error } = useApi(
    `/records/officers?page=${page}&search=${encodeURIComponent(search)}`,
  );
  const options = data?.data || [];
  const all =
    selectedOfficer && !options.some((o) => o.id === selectedOfficer.id)
      ? [selectedOfficer, ...options]
      : options;
  return (
    <div className="wide officer-select">
      <Field
        name="officer_search"
        title="Search officers by name or NIC"
        value={search}
        onChange={(v) => {
          setSearch(v);
          setPage(1);
        }}
      />
      <Alert error={error} />
      {loading ? (
        <p role="status">Loading officers…</p>
      ) : multiple ? (
        <div className="officer-options">
          {all.length === 0 && <p className="muted">No officers found.</p>}
          {all.map((officer) => (
            <label key={officer.id}>
              <input
                type="checkbox"
                checked={value.includes(officer.id)}
                onChange={(e) =>
                  onChange(
                    e.target.checked
                      ? [...value, officer.id]
                      : value.filter((id) => id !== officer.id),
                  )
                }
              />
              {officer.full_name_en} <small>{officer.nic_no}</small>
            </label>
          ))}
        </div>
      ) : (
        <Field
          name="officer_id"
          title="Officer"
          required
          options={all}
          value={value}
          onChange={onChange}
        />
      )}
      <Pagination data={data} onPage={setPage} />
    </div>
  );
}
