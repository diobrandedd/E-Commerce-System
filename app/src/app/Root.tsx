import { Outlet } from "react-router";
import { CorrectionNotificationsProvider } from "@/app/components/notifications/CorrectionNotificationsContext";
import { AuthProvider } from "./context/AuthContext";

export function Root() {
  return (
    <AuthProvider>
      <CorrectionNotificationsProvider>
        <Outlet />
      </CorrectionNotificationsProvider>
    </AuthProvider>
  );
}
