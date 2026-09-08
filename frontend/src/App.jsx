import { BrowserRouter, Link, Navigate, Route, Routes } from "react-router-dom";
import { AuthProvider } from "./lib/auth";
import { Layout, Protected } from "./components/Layout";
import { Home, Login, Register } from "./pages/Public";
import Dashboard from "./pages/Dashboard";
import Records from "./pages/Records";
import { BatchList, BatchDetail, LetterEditor } from "./pages/Letters";

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route element={<Protected />}>
            <Route element={<Layout />}>
              <Route path="/dashboard" element={<Dashboard />} />
              <Route
                path="/admin"
                element={<Navigate to="/dashboard" replace />}
              />
              {[
                "officers",
                "documents",
                "service-histories",
                "signatories",
                "users",
              ].map((resource) => (
                <Route
                  key={resource}
                  path={`/${resource}`}
                  element={<Records key={resource} resource={resource} />}
                />
              ))}
              <Route path="/letters" element={<BatchList />} />
              <Route path="/letters/batches/:id" element={<BatchDetail />} />
              <Route path="/letters/:id/edit" element={<LetterEditor />} />
            </Route>
          </Route>
          <Route
            path="*"
            element={
              <div className="auth-page">
                <h1>Page not found</h1>
                <Link to="/">Return home</Link>
              </div>
            }
          />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
