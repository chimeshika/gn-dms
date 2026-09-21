import Brand from "./Brand";
import { roleName } from "../lib/permissions";
import {
  Navigate,
  NavLink,
  Outlet,
  useLocation,
  useNavigate,
} from "react-router-dom";
import { useState } from "react";
import { useAuth } from "../lib/auth";
import { Alert, Loading } from "./UI";
import Icon from "./Icon";
import { LanguageSelect, useLanguage } from "../lib/i18n";

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
  const { t } = useLanguage();
  const navigate = useNavigate();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [notice, setNotice] = useState(false);
  const [error, setError] = useState(null);
  const links = [
    "dashboard",
    "officers",
    ...(user.role !== "ministry_head" ? ["documents", "letters"] : []),
    ...(user.role !== "officer" ? ["reports"] : []),
    ...(user.role === "main_admin" ? ["users", "audit-logs"] : []),
    "settings",
  ];
  const section = location.pathname.split("/")[1];
  async function signOut() {
    try {
      await logout();
      navigate("/login");
    } catch (e) {
      setError(e);
    }
  }
  return (
    <div className="workspace">
      {open && (
        <button
          className="sidebar-scrim"
          aria-label="Close navigation"
          onClick={() => setOpen(false)}
        />
      )}
      <aside className={`sidebar ${open ? "is-open" : ""}`}>
        <Brand to="/dashboard" />
        <nav aria-label="Main navigation">
          {links.map((key) => (
            <NavLink key={key} to={`/${key}`} onClick={() => setOpen(false)}>
              <Icon name={key} />
              <span>
                {key === "officers" && user.role === "officer"
                  ? "My Profile"
                  : t(key)}
              </span>
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-bottom">
          <em>For a Better Public Service</em>
          <small>People | Service | Sri Lanka</small>
        </div>
      </aside>
      <div className="workspace-main">
        <header className="topbar">
          <button
            className="secondary mobile-toggle"
            aria-label="Open navigation"
            onClick={() => setOpen(true)}
          >
            <Icon name="menu" />
          </button>
          <div className="header-title">
            <strong>{t(section)}</strong>
            <small>Home / {t(section)}</small>
          </div>
          <form
            className="global-search"
            onSubmit={(e) => {
              e.preventDefault();
              navigate(`/officers?search=${encodeURIComponent(search)}`);
            }}
          >
            <Icon name="search" />
            <input
              aria-label="Global search"
              placeholder="Search officers by name or NIC..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </form>
          <div className="notification">
            <button
              className="icon-button"
              aria-label="Notifications"
              aria-expanded={notice}
              onClick={() => setNotice(!notice)}
            >
              <Icon name="bell" />
            </button>
            {notice && (
              <div className="header-popover">
                No notification feed is configured.
              </div>
            )}
          </div>
          <LanguageSelect />
          <details className="user-menu">
            <summary>
              <span className="avatar">{user.name.slice(0, 1)}</span>
              <span>
                <strong>{user.name}</strong>
                <small>{roleName(user.role)}</small>
              </span>
              <span aria-hidden="true">v</span>
            </summary>
            <div className="header-popover">
              <NavLink to="/settings">Account settings</NavLink>
              <button className="link-button" onClick={signOut}>
                Sign out
              </button>
            </div>
          </details>
        </header>
        <main>
          <Alert error={error} />
          <Outlet />
        </main>
      </div>
    </div>
  );
}
