import { Link, useLocation, useNavigate } from "react-router-dom";
import { useState } from "react";
import { useAuth } from "../lib/auth";
import { api } from "../lib/api";
import { useApi } from "../lib/hooks";
import { Alert, Field } from "../components/UI";

export function Home() {
  return (
    <div className="public-page">
      <header className="public-header">
        <Link className="brand" to="/">
          <span className="brand-mark">GN</span>GN-DMS
        </Link>
        <Link className="button secondary" to="/login">
          Sign in
        </Link>
      </header>
      <section className="hero">
        <p className="eyebrow">MINISTRY OF HOME AFFAIRS</p>
        <h1>
          A connected service.
          <br />A complete officer record.
        </h1>
        <p>
          Manage Grama Niladhari registration, service records, and official
          correspondence in one place.
        </p>
        <div className="actions">
          <Link className="button" to="/register">
            Register as an officer
          </Link>
          <Link className="button secondary" to="/login">
            Open your workspace →
          </Link>
        </div>
      </section>
      <section className="feature-grid">
        {[
          [
            "01",
            "Officer registration",
            "Submit your details for verification by your Divisional Secretariat.",
          ],
          [
            "02",
            "Service history",
            "Keep appointments, promotions, and service records together.",
          ],
          [
            "03",
            "Official correspondence",
            "Prepare, review, and archive official letters and documents.",
          ],
        ].map(([n, title, text]) => (
          <article className="panel" key={n}>
            <span className="feature-number">{n}</span>
            <h2>{title}</h2>
            <p className="muted">{text}</p>
          </article>
        ))}
      </section>
      <footer>Grama Niladhari & Public Officers Management System</footer>
    </div>
  );
}
export function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [values, setValues] = useState({
    email: "",
    password: "",
    remember: false,
  });
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      await login(values);
      navigate(location.state?.from || "/dashboard", { replace: true });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };
  return (
    <div className="auth-page">
      <Link className="back-link" to="/">
        ← Back to GN-DMS
      </Link>
      <section className="auth-card">
        <span className="brand-mark">GN</span>
        <p className="eyebrow">YOUR OFFICER WORKSPACE</p>
        <h1>Welcome back</h1>
        <p className="muted">
          Sign in to access your records and correspondence.
        </p>
        <Alert error={error} />
        <form onSubmit={submit}>
          <Field
            name="email"
            type="email"
            required
            autoComplete="username"
            value={values.email}
            onChange={(email) => setValues({ ...values, email })}
          />
          <Field
            name="password"
            type="password"
            required
            autoComplete="current-password"
            value={values.password}
            onChange={(password) => setValues({ ...values, password })}
          />
          <Field
            name="remember"
            title="Remember me"
            type="checkbox"
            value={values.remember}
            onChange={(remember) => setValues({ ...values, remember })}
          />
          <button disabled={busy}>{busy ? "Signing in…" : "Sign in"}</button>
        </form>
        <p className="muted">
          New officer? <Link to="/register">Register here</Link>
        </p>
      </section>
    </div>
  );
}
export function Register() {
  const [values, setValues] = useState({ gender: "male", medium: "si" });
  const [error, setError] = useState(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const { data: locations, error: locationError } = useApi(
    `/locations?district_id=${values.district_id || ""}&ds_division_id=${values.ds_division_id || ""}`,
  );
  const set = (name, value) =>
    setValues((current) => ({
      ...current,
      [name]: value,
      ...(name === "district_id"
        ? { ds_division_id: "", gn_division_id: "" }
        : name === "ds_division_id"
          ? { gn_division_id: "" }
          : {}),
    }));
  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      const result = await api("/register", { method: "POST", body: values });
      setMessage(result.message);
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };
  return (
    <div className="public-page registration">
      <Link className="back-link" to="/">
        ← Back to GN-DMS
      </Link>
      <section className="panel">
        <p className="eyebrow">JOIN THE OFFICER PORTAL</p>
        <h1>Officer registration</h1>
        <p className="muted">
          Your Divisional Secretariat will review and verify your details.
        </p>
        <Alert error={error || locationError} message={message} />
        {message ? (
          <Link className="button" to="/login">
            Go to sign in
          </Link>
        ) : (
          <form onSubmit={submit}>
            <div className="form-grid">
              {[
                ["nic_no", "NIC number", "text", true],
                ["full_name_en", "Full name (English)", "text", true],
                ["full_name_si", "Full name (Sinhala)"],
                ["full_name_ta", "Full name (Tamil)"],
                ["dob", "Date of birth", "date", true],
                ["email", "Email address", "email", true],
                ["phone", "Phone"],
                ["first_appointment_date", "First appointment date", "date"],
                ["address_line1", "Address line 1"],
                ["address_line2", "Address line 2"],
                ["address_line3", "Address line 3"],
              ].map(([name, title, type, required]) => (
                <Field
                  key={name}
                  {...{ name, title, type, required }}
                  value={values[name]}
                  onChange={(v) => set(name, v)}
                />
              ))}
              <Field
                name="gender"
                options={[
                  { value: "male", label: "Male" },
                  { value: "female", label: "Female" },
                ]}
                required
                value={values.gender}
                onChange={(v) => set("gender", v)}
              />
              <Field
                name="medium"
                options={[
                  { value: "si", label: "Sinhala" },
                  { value: "ta", label: "Tamil" },
                  { value: "en", label: "English" },
                ]}
                required
                value={values.medium}
                onChange={(v) => set("medium", v)}
              />
              <Field
                name="district_id"
                title="District"
                options={locations?.districts || []}
                required
                value={values.district_id}
                onChange={(v) => set("district_id", v)}
              />
              <Field
                name="ds_division_id"
                title="DS division"
                options={locations?.ds_divisions || []}
                disabled={!values.district_id}
                required
                value={values.ds_division_id}
                onChange={(v) => set("ds_division_id", v)}
              />
              <Field
                name="gn_division_id"
                title="GN division"
                options={locations?.gn_divisions || []}
                disabled={!values.ds_division_id}
                value={values.gn_division_id}
                onChange={(v) => set("gn_division_id", v)}
              />
              <Field
                name="password"
                type="password"
                minLength={8}
                autoComplete="new-password"
                required
                value={values.password}
                onChange={(v) => set("password", v)}
              />
              <Field
                name="password_confirmation"
                title="Confirm password"
                type="password"
                autoComplete="new-password"
                required
                value={values.password_confirmation}
                onChange={(v) => set("password_confirmation", v)}
              />
            </div>
            <button disabled={busy || Boolean(locationError)}>
              {busy ? "Submitting…" : "Submit registration"}
            </button>
          </form>
        )}
      </section>
    </div>
  );
}
