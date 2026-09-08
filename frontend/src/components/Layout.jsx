import {
  Navigate,
  NavLink,
  Outlet,
  useLocation,
  useNavigate,
} from "react-router-dom";
import { useState } from "react";
import { useAuth } from "../lib/auth";
import { label } from "../lib/hooks";
import { Alert, Loading } from "./UI";

export function Protected() {
  const { user, loading, error, refresh } = useAuth();
  const location = useLocation();
  if (loading) return <Loading />;
  if (error)
    return (
      <div className="panel">
        <Alert error={error} />
        <button onClick={refresh}>Retry connection</button>
      </div>
    );
  if (!user)
    return <Navigate to="/login" state={{ from: location.pathname }} replace />;
  if (user.status !== "active")
    return (
      <div className="panel">
        <Alert
          error={
            new Error(
              "Your account is awaiting verification or has been deactivated.",
            )
          }
        />
      </div>
    );
  return <Outlet />;
}
export function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);
  const admin = user?.role !== "officer";
  const links = [
    ["/dashboard", "Overview"],
    ["/officers", admin ? "Officers" : "My profile"],
    ["/documents", "Documents"],
    ["/service-histories", "Service history"],
  ];
  if (admin) links.push(["/letters", "Letter batches"]);
  if (["main_admin", "district_admin"].includes(user?.role))
    links.push(["/signatories", "Signatories"]);
  if (user?.role === "main_admin") links.push(["/users", "Users"]);
  const signOut = async () => {
    setBusy(true);
    try {
      await logout();
      navigate("/login");
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };
  return (
    <div className="workspace">
      <aside className="sidebar">
        <NavLink to="/dashboard" className="brand">
          <span className="brand-mark">GN</span>
          <span>
            GN-DMS<small>OFFICER MANAGEMENT</small>
          </span>
        </NavLink>
        <p className="nav-caption">WORKSPACE</p>
        <nav aria-label="Main navigation">
          {links.map(([to, title]) => (
            <NavLink key={to} to={to}>
              {title}
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-bottom">
          Ministry of Home Affairs
          <small>Grama Niladhari & Public Officers</small>
        </div>
      </aside>
      <div className="workspace-main">
        <header className="topbar">
          <span className="muted">Officer management portal</span>
          <div className="user-menu">
            <span>
              <strong>{user?.name}</strong>
              <small>{label(user?.role)}</small>
            </span>
            <button className="secondary" disabled={busy} onClick={signOut}>
              Sign out
            </button>
          </div>
        </header>
        <main>
          <Alert error={error} />
          <Outlet />
        </main>
      </div>
    </div>
  );
}
