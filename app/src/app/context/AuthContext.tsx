import { createContext, useContext, useEffect, useState, ReactNode, useCallback } from "react";
import { apiGet, apiPost, setCsrfToken, setForwardingFetchHeaders, ApiError } from "@/app/lib/api";
import type { PermissionKey } from "@/app/lib/permissions";
import { hasPermission } from "@/app/lib/permissions";

export interface ForwardingInfo {
  enabled: boolean;
  public_url: string;
  host: string;
  label: string;
  allow_localhost_without_redirect?: boolean;
  fetch_headers: Record<string, string>;
}

function maybeRedirectToForwarding(f?: ForwardingInfo): void {
  if (!f?.enabled || !f.public_url || !f.host) return;
  const h = window.location.hostname.toLowerCase();
  if (f.allow_localhost_without_redirect !== false) {
    if (h === "localhost" || h === "127.0.0.1" || h.startsWith("127.")) return;
  }
  // Already on ngrok (any subdomain) — do not bounce on refresh (avoids failed reload if tunnel URL changed).
  if (h.includes("ngrok")) return;
  if (window.location.protocol === "https:" && h === f.host.toLowerCase()) return;
  const base = f.public_url.replace(/\/$/, "");
  const target = base + window.location.pathname + window.location.search + window.location.hash;
  if (target === window.location.href) return;
  window.location.replace(target);
}

export type Role =
  | "executive"
  | "prod_manager"
  | "hr"
  | "team_leader"
  | "staff"
  | "csr_staff"
  | "csr_tl"
  | "pos";

export type Department = "honey" | "paragis" | "cacao_brc" | "csr" | "all";

export interface AuthUser {
  id: number;
  username: string;
  name: string;
  email: string;
  employee_id?: string;
  role: Role;
  php_role: string;
  dept: Department;
  dept_id: number | null;
  dept_name: string;
  permissions?: string[];
}

export interface MePayload {
  authenticated: boolean;
  mode: "user" | "pos" | null;
  csrf: string;
  forwarding?: ForwardingInfo;
  user: AuthUser | null;
}

interface AuthContextType {
  user: AuthUser | null;
  permissions: string[];
  mode: "user" | "pos" | null;
  loading: boolean;
  error: string | null;
  can: (key: PermissionKey | PermissionKey[]) => boolean;
  login: (username: string, password: string) => Promise<AuthUser>;
  posLogin: (pin: string) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [mode, setMode] = useState<"user" | "pos" | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const applyMe = useCallback((payload: MePayload) => {
    maybeRedirectToForwarding(payload.forwarding);
    setCsrfToken(payload.csrf ?? null);
    setForwardingFetchHeaders(payload.forwarding?.fetch_headers);
    setUser(payload.user);
    setPermissions(payload.user?.permissions ?? []);
    setMode(payload.mode);
  }, []);

  const can = useCallback(
    (key: PermissionKey | PermissionKey[]) => hasPermission(permissions, user?.role, key),
    [permissions, user?.role],
  );

  const refresh = useCallback(async () => {
    try {
      const payload = await apiGet<MePayload>("auth.php", "me");
      applyMe(payload);
      setError(null);
    } catch (e) {
      setUser(null);
      setMode(null);
      setError(e instanceof ApiError ? e.message : "Could not contact server.");
    } finally {
      setLoading(false);
    }
  }, [applyMe]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const login = useCallback(
    async (username: string, password: string) => {
      const payload = await apiPost<MePayload>("auth.php", "login", { username, password });
      applyMe(payload);
      if (!payload.user) throw new ApiError("Login response missing user.", 500);
      return payload.user;
    },
    [applyMe]
  );

  const posLogin = useCallback(
    async (pin: string) => {
      const payload = await apiPost<MePayload>("auth.php", "pos_login", { pin });
      applyMe(payload);
    },
    [applyMe]
  );

  const logout = useCallback(async () => {
    try {
      await apiPost<{ authenticated: boolean }>("auth.php", "logout");
    } catch {
      // ignore network errors on logout; we still clear local state
    }
    setUser(null);
    setPermissions([]);
    setMode(null);
    setCsrfToken(null);
    setForwardingFetchHeaders(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, permissions, mode, loading, error, can, login, posLogin, logout, refresh }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
  return ctx;
}
