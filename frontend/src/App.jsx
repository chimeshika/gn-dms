import { BrowserRouter, Link, Navigate, Route, Routes } from "react-router-dom";
import { LanguageProvider } from "./lib/i18n";
import { AuthProvider } from "./lib/auth";
import { Layout, Protected } from "./components/Layout";
import { Home, Login, Register } from "./pages/Public";
import Dashboard from "./pages/Dashboard";
import { OfficerForm, OfficerProfile } from "./pages/Officers";
import Documents, { UploadDocument } from "./pages/Documents";
import Reports from "./pages/Reports";
import { AuditLogs, Settings } from "./pages/Administration";
import GenerateLetter, { GeneratedLetters } from "./pages/GenerateLetter";
import Records from "./pages/Records";
import { BatchList, BatchDetail, LetterEditor } from "./pages/Letters";

export default function App() {
  return (
    <BrowserRouter>
      <LanguageProvider>
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
                {["officers", "service-histories", "signatories", "users"].map(
                  (resource) => (
                    <Route
                      key={resource}
                      path={`/${resource}`}
                      element={<Records key={resource} resource={resource} />}
                    />
                  ),
                )}
                <Route path="/officers/create" element={<OfficerForm />} />
                <Route path="/officers/:id" element={<OfficerProfile />} />
                <Route path="/officers/:id/edit" element={<OfficerForm />} />
                <Route path="/documents" element={<Documents />} />
                <Route path="/documents/upload" element={<UploadDocument />} />
                <Route path="/reports" element={<Reports />} />
                <Route path="/audit-logs" element={<AuditLogs />} />
                <Route path="/settings" element={<Settings />} />
                <Route path="/letters" element={<GeneratedLetters />} />
                <Route path="/letters/generate" element={<GenerateLetter />} />
                <Route path="/letters/batches" element={<BatchList />} />
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
      </LanguageProvider>
    </BrowserRouter>
  );
}
