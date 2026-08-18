import { Outlet } from "react-router";
import { CorrectionNotificationsProvider } from "@/app/components/notifications/CorrectionNotificationsContext";
import { BarcodePendingProvider } from "@/app/components/notifications/BarcodePendingContext";
import { usePyxScrollDocument } from "@/app/hooks/usePyxScrollDocument";
import { AuthProvider } from "./context/AuthContext";

function PyxScrollDocumentListener() {
  usePyxScrollDocument();
  return null;
}

export function Root() {
  return (
    <AuthProvider>
      <CorrectionNotificationsProvider>
        <BarcodePendingProvider>
          <PyxScrollDocumentListener />
          <Outlet />
        </BarcodePendingProvider>
      </CorrectionNotificationsProvider>
    </AuthProvider>
  );
}
