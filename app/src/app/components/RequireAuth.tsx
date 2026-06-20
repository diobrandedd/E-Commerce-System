import { ReactNode } from "react";
import { Navigate, useLocation } from "react-router";
import { useAuth, Role } from "@/app/context/AuthContext";

interface RequireAuthProps {
  children: ReactNode;
  /** When set, only these SPA-normalized roles are allowed. */
  roles?: Role[];
  /** When true, allow POS-mode sessions through. Defaults to false. */
  allowPOS?: boolean;
  /** Where to redirect when unauthenticated. Defaults to /. */
  redirectTo?: string;
}

export function RequireAuth({ children, roles, allowPOS = false, redirectTo = "/" }: RequireAuthProps) {
  const { user, mode, loading } = useAuth();
  const location = useLocation();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background text-muted-foreground text-sm">
        Checking session…
      </div>
    );
  }

  if (mode === "pos") {
    if (allowPOS) return <>{children}</>;
    return <Navigate to="/pos" replace />;
  }

  if (!user) {
    return <Navigate to={redirectTo} state={{ from: location.pathname }} replace />;
  }

  if (roles && roles.length > 0 && !roles.includes(user.role)) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-2 px-6 text-center bg-background">
        <h1 className="text-lg font-semibold text-foreground">Forbidden</h1>
        <p className="text-sm text-muted-foreground">
          Your account ({user.role}) does not have access to this page.
        </p>
      </div>
    );
  }

  return <>{children}</>;
}
