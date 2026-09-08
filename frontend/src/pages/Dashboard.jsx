import { Link } from "react-router-dom";
import { useApi, date, label } from "../lib/hooks";
import { useAuth } from "../lib/auth";
import { Alert, Badge, Heading, Loading } from "../components/UI";

export default function Dashboard() {
  const { user } = useAuth();
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
          <div className="stats">
            {[
              ["Officers", data.total_officers],
              ["Awaiting verification", data.pending_officers],
              ["Documents", data.documents],
              ["Confirmed officers", data.confirmed_officers],
            ].map(([title, value]) => (
              <article className="panel stat" key={title}>
                <p className="muted">{title}</p>
                <strong>{value}</strong>
              </article>
            ))}
          </div>
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
              <div className="timeline">
                {data.recent_history.map((event) => (
                  <article key={event.id}>
                    <span className="timeline-dot" />
                    <div>
                      <strong>{event.officer?.full_name_en}</strong>
                      <p>
                        {label(event.event_type)} · {event.description}
                      </p>
                      <small>{date(event.effective_date)}</small>
                    </div>
                  </article>
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </>
  );
}
