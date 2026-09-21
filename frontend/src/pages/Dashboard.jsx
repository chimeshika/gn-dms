import { canManage } from "../lib/permissions";
import { Stats, Analytics } from "./Reports";
import { Link } from "react-router-dom";
import { useApi, date, label } from "../lib/hooks";
import { useAuth } from "../lib/auth";
import { Alert, Badge, Heading, Loading } from "../components/UI";

export default function Dashboard() {
  const { user } = useAuth();
  const { data: analytics, error: analyticsError } = useApi("/analytics");
  const { data, loading, error } = useApi("/dashboard");
  if (loading) return <Loading />;
  return (
    <>
      <Heading
        title={`Welcome, ${user.name.split(" ")[0]}`}
        description="Your service records and recent activity, in one place."
      />
      <Alert error={error} />
      {data && (
        <>
          <Alert error={analyticsError} />
          {analytics && <Stats data={analytics} />}
          {canManage(user) && (
            <div className="quick-actions">
              <div>
                <h2>Quick Actions</h2>
                <p className="muted">Common tasks for system administration</p>
              </div>
              <div className="actions">
                <Link className="button" to="/officers/create">
                  + Add Officer
                </Link>
                <Link className="button teal" to="/documents/upload">
                  Upload Document
                </Link>
                <Link className="button secondary" to="/letters/generate">
                  Generate Letter
                </Link>
              </div>
            </div>
          )}
          {analytics && <Analytics data={analytics} />}
          {data.profile && (
            <section className="panel">
              <h2>My officer record</h2>
              <div className="profile-grid">
                <p>
                  <small>Full name</small>
                  {data.profile.full_name_en}
                </p>
                <p>
                  <small>NIC</small>
                  {data.profile.nic_no}
                </p>
                <p>
                  <small>Grade</small>
                  {label(data.profile.current_grade)}
                </p>
                <p>
                  <small>Service status</small>
                  <Badge value={data.profile.service_status} />
                </p>
              </div>
            </section>
          )}
          <section className="panel">
            <div className="section-heading">
              <h2>Recent service activity</h2>
              <Link to="/service-histories">View all →</Link>
            </div>
            {!data.recent_history.length ? (
              <p className="empty">No service events recorded yet.</p>
            ) : (
              <div className="table-scroll">
                <table>
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Officer</th>
                      <th>Event</th>
                      <th>Description</th>
                      <th>Reference</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.recent_history.map((event) => (
                      <tr key={event.id}>
                        <td>{date(event.effective_date)}</td>
                        <td>{event.officer?.full_name_en}</td>
                        <td>
                          <Badge value={event.event_type} />
                        </td>
                        <td>{event.description}</td>
                        <td>{event.ref_no || "Not recorded"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </>
      )}
    </>
  );
}
