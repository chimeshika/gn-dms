import { useEffect, useState } from "react";
import { api } from "./api";

export function useApi(path, revision = 0) {
  const [state, setState] = useState({
    data: null,
    loading: true,
    error: null,
  });
  useEffect(() => {
    const controller = new AbortController();
    setState({ data: null, loading: true, error: null });
    api(path, { signal: controller.signal })
      .then((data) => setState({ data, loading: false, error: null }))
      .catch((error) => {
        if (error.name !== "AbortError")
          setState({ data: null, loading: false, error });
      });
    return () => controller.abort();
  }, [path, revision]);
  return state;
}
export const label = (value) =>
  String(value ?? "")
    .replaceAll("_", " ")
    .replaceAll("-", " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
export const date = (value) => (value ? String(value).slice(0, 10) : "—");
