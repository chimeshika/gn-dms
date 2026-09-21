import { beforeEach, afterEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import App from "./App";

const admin = {
  id: 1,
  name: "Test Admin",
  email: "admin@example.test",
  role: "main_admin",
  status: "active",
};
const dashboard = {
  total_officers: 2,
  pending_officers: 1,
  documents: 3,
  confirmed_officers: 1,
  recent_history: [],
};
const page = (data) => ({
  data,
  total: data.length,
  current_page: 1,
  last_page: 1,
});
let requests, sessionUser, handlers;
function reply(data, status = 200) {
  return { ok: status < 400, status, json: async () => data };
}
beforeEach(() => {
  requests = [];
  sessionUser = null;
  handlers = {};
  window.history.replaceState({}, "", "/");
  vi.stubGlobal(
    "fetch",
    vi.fn(async (url, options = {}) => {
      requests.push({ url, ...options });
      if (handlers[url]) return handlers[url](options);
      if (url === "/api/session")
        return reply({ user: sessionUser, csrf_token: "test-csrf-token" });
      if (url === "/api/dashboard") return reply(dashboard);
      if (url === "/api/metadata")
        return reply({
          document_types: [{ value: "appointment", label: "Appointment" }],
          signatories: [],
        });
      if (url.startsWith("/api/locations"))
        return reply({
          districts: [{ id: 1, name_en: "Colombo" }],
          ds_divisions: [{ id: 1, name_en: "Homagama", district_id: 1 }],
          gn_divisions: [],
        });
      if (url.startsWith("/api/records/")) return reply(page([]));
      return reply({ message: "Unexpected request: " + url }, 404);
    }),
  );
});
afterEach(() => vi.unstubAllGlobals());

describe("React application workflows", () => {
  it("retains officer form values while moving between steps", async () => {
    sessionUser = admin;
    window.history.replaceState({}, "", "/officers/create");
    render(<App />);
    const name = await screen.findByLabelText("Full name (English) *");
    fireEvent.change(name, { target: { value: "Wizard Officer" } });
    fireEvent.submit(
      screen.getByRole("button", { name: "Next" }).closest("form"),
    );
    await screen.findByLabelText("District *");
    fireEvent.click(screen.getByRole("button", { name: "Previous" }));
    expect(screen.getByLabelText("Full name (English) *")).toHaveValue(
      "Wizard Officer",
    );
    expect(
      requests.some(
        (r) => r.method === "POST" && r.url === "/api/records/officers",
      ),
    ).toBe(false);
  });
  it("rejects unsupported uploads before sending a document", async () => {
    sessionUser = admin;
    window.history.replaceState({}, "", "/documents/upload");
    render(<App />);
    const input = await screen.findByLabelText("Choose document");
    fireEvent.change(input, {
      target: {
        files: [new File(["bad"], "script.html", { type: "text/html" })],
      },
    });
    expect(await screen.findByRole("alert")).toHaveTextContent(
      "PDF, JPEG or PNG",
    );
    expect(
      screen.getByRole("button", { name: "Upload Document" }),
    ).toBeDisabled();
    expect(
      requests.some(
        (r) => r.method === "POST" && r.url === "/api/records/documents",
      ),
    ).toBe(false);
  });
  it("shows persisted profile history and dependent records in separate tabs", async () => {
    sessionUser = admin;
    window.history.replaceState({}, "", "/officers/4");
    handlers["/api/officers/4"] = () =>
      reply({
        id: 4,
        full_name_en: "Example Officer",
        nic_no: "1234",
        service_status: "appointed",
        service_histories: [
          {
            id: 1,
            event_type: "transfer",
            effective_date: "2025-01-01",
            old_value: "Previous division",
            new_value: "Current division",
          },
        ],
        documents: [],
        dependents: [
          {
            name: "Example Dependent",
            relationship: "Child",
            dob: "2012-01-01",
          },
        ],
      });
    render(<App />);
    await screen.findByRole("heading", { name: /Example Officer/ });
    fireEvent.click(screen.getByRole("tab", { name: "Service History" }));
    expect(screen.getByText("Previous division")).toBeInTheDocument();
    expect(screen.getByText("Current division")).toBeInTheDocument();
    fireEvent.click(screen.getByRole("tab", { name: "Dependents" }));
    expect(screen.getByText(/Example Dependent/)).toBeInTheDocument();
  });
  it("shows the public homepage and registration link", async () => {
    render(<App />);
    expect(
      screen.getByRole("heading", { name: /A connected service/ }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Register as an officer" }),
    ).toHaveAttribute("href", "/register");
    await waitFor(() => expect(fetch).toHaveBeenCalled());
  });
  it("redirects protected pages to login and displays server validation errors", async () => {
    window.history.replaceState({}, "", "/dashboard");
    handlers["/api/login"] = () =>
      reply(
        {
          message: "Invalid credentials.",
          errors: { email: ["Email or password is incorrect."] },
        },
        422,
      );
    render(<App />);
    await screen.findByRole("heading", { name: "Welcome to GN-POMS" });
    fireEvent.change(screen.getByLabelText("Email *"), {
      target: { value: "admin@example.test" },
    });
    fireEvent.change(screen.getByLabelText("Password *"), {
      target: { value: "incorrect" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));
    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Email or password is incorrect.",
    );
    expect(
      requests.find((r) => r.url === "/api/login").headers["X-CSRF-TOKEN"],
    ).toBe("test-csrf-token");
  });
  it("signs in, renders the dashboard, and signs out", async () => {
    window.history.replaceState({}, "", "/login");
    handlers["/api/login"] = () =>
      reply({ user: admin, csrf_token: "rotated-token" });
    handlers["/api/logout"] = () =>
      reply({ message: "Signed out.", csrf_token: "logged-out-token" });
    render(<App />);
    fireEvent.change(screen.getByLabelText("Email *"), {
      target: { value: "admin@example.test" },
    });
    fireEvent.change(screen.getByLabelText("Password *"), {
      target: { value: "password" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));
    await screen.findByRole("heading", { name: "Welcome, Test" });
    expect(screen.getByRole("link", { name: "Users" })).toBeInTheDocument();
    fireEvent.click(screen.getByText("Test Admin").closest("summary"));
    fireEvent.click(screen.getByRole("button", { name: "Sign out" }));
    await screen.findByRole("heading", { name: "Welcome to GN-POMS" });
  });
  it("hides administrator actions for officers", async () => {
    sessionUser = { ...admin, role: "officer" };
    window.history.replaceState({}, "", "/documents");
    render(<App />);
    await screen.findByRole("heading", { name: "Documents" });
    expect(
      screen.queryByRole("link", { name: "Users" }),
    ).not.toBeInTheDocument();
    expect(
      screen.queryByRole("button", { name: /Add record/ }),
    ).not.toBeInTheDocument();
    await screen.findByText("No records found.");
  });
  it("submits registration and displays the verification message", async () => {
    window.history.replaceState({}, "", "/register");
    handlers["/api/register"] = () =>
      reply({ message: "Registration submitted. Awaiting verification." }, 201);
    render(<App />);
    await screen.findByRole("option", { name: "Colombo" });
    for (const [name, value] of [
      ["NIC number *", "921234567V"],
      ["Full name (English) *", "Test Officer"],
      ["Date of birth *", "1992-01-01"],
      ["Email address *", "officer@example.test"],
      ["Password *", "password123"],
      ["Confirm password *", "password123"],
    ])
      fireEvent.change(screen.getByLabelText(name), { target: { value } });
    fireEvent.change(screen.getByLabelText("District *"), {
      target: { value: "1" },
    });
    await screen.findByRole("option", { name: "Homagama" });
    fireEvent.change(screen.getByLabelText("DS division *"), {
      target: { value: "1" },
    });
    fireEvent.submit(
      screen
        .getByRole("button", { name: "Submit registration" })
        .closest("form"),
    );
    expect(await screen.findByRole("status")).toHaveTextContent(
      "Awaiting verification",
    );
    expect(
      screen.getByRole("link", { name: "Go to sign in" }),
    ).toBeInTheDocument();
  });
  it("creates a batch and navigates to its details", async () => {
    sessionUser = admin;
    window.history.replaceState({}, "", "/letters/batches");
    const batch = {
      id: 7,
      name: "September appointments",
      document_type: "appointment",
      letter_date: "2026-09-08",
      letters: [],
    };
    handlers["/api/batches?page=1"] = () => reply(page([]));
    handlers["/api/batches"] = () => reply(batch, 201);
    handlers["/api/batches/7"] = () => reply(batch);
    render(<App />);
    fireEvent.click(await screen.findByRole("button", { name: "+ New batch" }));
    fireEvent.change(screen.getByLabelText("Batch name *"), {
      target: { value: batch.name },
    });
    await screen.findByRole("option", { name: "Appointment" });
    fireEvent.submit(
      screen.getByRole("button", { name: "Save batch" }).closest("form"),
    );
    await screen.findByRole("heading", { name: batch.name });
    expect(
      screen.getByRole("button", { name: "Import & generate drafts" }),
    ).toBeDisabled();
  });
  it("renders finalized letters read-only", async () => {
    sessionUser = admin;
    window.history.replaceState({}, "", "/letters/9/edit");
    handlers["/api/letters/9"] = () =>
      reply({
        id: 9,
        letter_batch_id: 7,
        ref_no: "REF/9",
        subject: "Appointment",
        body: "Archived letter",
        status: "final",
        cc_to: [],
        officer: { full_name_en: "Test Officer" },
      });
    render(<App />);
    expect(await screen.findByLabelText("Reference number *")).toBeDisabled();
    expect(
      screen.queryByRole("button", { name: "Save draft" }),
    ).not.toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Open PDF" })).toHaveAttribute(
      "href",
      "/api/letters/9/pdf",
    );
  });
});
