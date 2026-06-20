import { Navigate, useParams } from "react-router";
import { RAW_MATERIAL_ROUTES } from "@/app/lib/inventoryRoutes";
import { RawDeptMaterialPage } from "@/app/pages/inventory/RawDeptMaterialPage";

export function RawMaterialDetailPage() {
  const { slug } = useParams();
  const key = slug ?? "";

  if (key === "honey") {
    return <Navigate to="/inventory/raw-materials/honey/warehouse" replace />;
  }

  const route = RAW_MATERIAL_ROUTES[key];
  if (!route) {
    return null;
  }

  return <RawDeptMaterialPage route={route} />;
}
