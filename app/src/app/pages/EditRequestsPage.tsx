import { Navigate } from "react-router";
import { PageLayout } from "@/app/components/PageLayout";
import { EditRequestsSection } from "@/app/components/requests/EditRequestsSection";
import { useAuth } from "@/app/context/AuthContext";

/** Team leader / staff edit-request inbox. Executive + prod_manager use unified Requests hub. */
export function EditRequestsPage() {
  const { user } = useAuth();
  const role = user?.role;

  if (role === "executive" || role === "prod_manager") {
    return <Navigate to="/inventory/requests#edit-requests-section" replace />;
  }

  return (
    <PageLayout
      title="Edit Requests"
      subtitle="Submit and track inventory row edit requests for executive approval"
    >
      <EditRequestsSection isReviewer={false} />
    </PageLayout>
  );
}
