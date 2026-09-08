let csrfToken;
export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}
export async function api(path, { method = "GET", body, signal } = {}) {
  const headers = {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };
  if (method !== "GET") {
    if (!csrfToken) await api("/session");
    headers["X-CSRF-TOKEN"] = csrfToken;
  }
  const multipart = body instanceof FormData;
  if (body && !multipart) headers["Content-Type"] = "application/json";
  let response;
  try {
    response = await fetch(`/api${path}`, {
      method,
      credentials: "same-origin",
      headers,
      body: body ? (multipart ? body : JSON.stringify(body)) : undefined,
      signal,
    });
  } catch (error) {
    if (error.name === "AbortError") throw error;
    throw new ApiError(
      "Cannot reach the server. Check that the Laravel backend is running and try again.",
      0,
    );
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 419) csrfToken = undefined;
    if (response.status === 401)
      window.dispatchEvent(new Event("session-expired"));
    throw new ApiError(
      data.message ||
        (response.status === 419
          ? "Your session expired. Please try again."
          : "The request could not be completed."),
      response.status,
      data.errors,
    );
  }
  if (data.csrf_token) csrfToken = data.csrf_token;
  return data;
}

export function formPayload(values) {
  const hasFile = Object.values(values).some((value) => value instanceof File);
  if (!hasFile) return values;
  const form = new FormData();
  Object.entries(values).forEach(([key, value]) => {
    if (value !== undefined && value !== null)
      form.append(
        key,
        typeof value === "boolean" ? (value ? "1" : "0") : value,
      );
  });
  return form;
}
