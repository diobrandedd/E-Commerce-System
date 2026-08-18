import { useState, useEffect } from "react";
import { useNavigate } from "react-router";
import { Eye, EyeOff } from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { useAuth, Role } from "@/app/context/AuthContext";
import { ApiError } from "@/app/lib/api";

const DEFAULT_ROUTE_BY_ROLE: Record<Role, string> = {
  executive: "/dashboard",
  prod_manager: "/dashboard",
  hr: "/dashboard",
  team_leader: "/dashboard",
  staff: "/dashboard",
  csr_staff: "/dashboard",
  csr_tl: "/dashboard",
  pos: "/pos",
};

export function LoginPage() {
  const navigate = useNavigate();
  const { login, user, mode, loading } = useAuth();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  useEffect(() => {
    if (loading) return;
    if (mode === "pos") {
      navigate("/pos", { replace: true });
      return;
    }
    if (user) {
      const next = DEFAULT_ROUTE_BY_ROLE[user.role] ?? "/dashboard";
      navigate(next, { replace: true });
    }
  }, [user, mode, loading, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim() || !password) {
      setFormError("Enter your username and password.");
      return;
    }
    setFormError(null);
    setIsLoading(true);
    try {
      const u = await login(username.trim(), password);
      const next = DEFAULT_ROUTE_BY_ROLE[u.role] ?? "/dashboard";
      navigate(next, { replace: true });
    } catch (e) {
      setFormError(e instanceof ApiError ? e.message : "Login failed.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full flex items-center justify-center bg-background px-4" style={{ fontFamily: "'DM Sans', sans-serif" }}>
      <div className="w-full max-w-[420px]">
        <div className="flex flex-col items-center mb-8 text-center">
          <img src={pyxLogo} alt="Pyx Food Products logo" className="w-24 h-24 object-contain mb-4" />
          <div>
            <h1 className="text-2xl font-bold leading-tight" style={{ color: "#3b2a00" }}>Pyx Tracker</h1>
            <p className="text-sm font-medium tracking-wide mt-0.5" style={{ color: "#7a6030" }}>Management System</p>
          </div>
        </div>

        <div className="bg-card rounded-2xl border border-border px-8 py-8" style={{ boxShadow: "0 2px 16px rgba(0,0,0,0.08)" }}>
          <p className="text-sm text-muted-foreground mb-5">Sign in with your assigned Pyx Tracker account.</p>

          <AppErrorBanner message={formError} />

          <form onSubmit={handleSubmit} className="space-y-5">
            <div className="space-y-1.5">
              <label htmlFor="username" className="text-sm font-medium text-foreground">Username</label>
              <input
                id="username"
                type="text"
                value={username}
                onChange={e => setUsername(e.target.value)}
                className="w-full rounded-lg border-2 border-border bg-card text-foreground text-sm px-4 py-3 outline-none focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 transition-all duration-150 placeholder:text-muted-foreground/40"
                autoComplete="username"
                autoFocus
              />
            </div>

            <div className="space-y-1.5">
              <label htmlFor="password" className="text-sm font-medium text-foreground">Password</label>
              <div className="relative">
                <input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  className="w-full rounded-lg border-2 border-border bg-card text-foreground text-sm px-4 py-3 pr-11 outline-none focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 transition-all duration-150 placeholder:text-muted-foreground/40"
                  autoComplete="current-password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  tabIndex={-1}
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full rounded-lg text-sm font-bold py-3.5 flex items-center justify-center transition-all duration-200 hover:brightness-105 hover:shadow-md active:scale-[0.99] disabled:opacity-70"
              style={{ background: "#C8900A", color: "#fff", boxShadow: "0 4px 12px rgba(200,144,10,0.35)" }}
            >
              {isLoading ? <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : "Sign in"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
