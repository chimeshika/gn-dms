import { createContext, useContext, useEffect, useState } from "react";
import { api } from "./api";

const AuthContext = createContext(null);
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const refresh = async () => {
    setLoading(true);
    setError(null);
    try {
      const session = await api("/session");
      setUser(session.user);
    } catch (error) {
      setError(error);
    } finally {
      setLoading(false);
    }
  };
  useEffect(() => {
    refresh();
    const expired = () => setUser(null);
    window.addEventListener("session-expired", expired);
    return () => window.removeEventListener("session-expired", expired);
  }, []);
  const login = async (values) => {
    const session = await api("/login", { method: "POST", body: values });
    setUser(session.user);
  };
  const logout = async () => {
    await api("/logout", { method: "POST" });
    setUser(null);
  };
  return (
    <AuthContext.Provider
      value={{ user, loading, error, refresh, login, logout }}
    >
      {children}
    </AuthContext.Provider>
  );
}
export const useAuth = () => useContext(AuthContext);
