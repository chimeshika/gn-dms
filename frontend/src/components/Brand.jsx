import { Link } from "react-router-dom";

export default function Brand({ to = "/", large = false }) {
  return (
    <Link className={`system-brand${large ? " system-brand-large" : ""}`} to={to} aria-label="GN-POMS home">
      <img className="system-brand-emblem" src="/branding/sri-lanka-emblem.svg" alt="" width="600" height="851" />
      <span className="system-brand-copy">
        <strong>GN-POMS</strong>
        <span>Grama Niladhari &amp; Public<br />Officers Management System</span>
      </span>
    </Link>
  );
}
