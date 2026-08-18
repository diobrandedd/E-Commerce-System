import { Navigate, useLocation, useParams } from "react-router";
import { RAW_MATERIAL_ROUTES } from "@/app/lib/inventoryRoutes";
import { RawDeptMaterialPage } from "@/app/pages/inventory/RawDeptMaterialPage";

function slugFromPath(pathname: string, prefix: string): string {
  const i = pathname.indexOf(prefix);
  if (i < 0) return "";
  return pathname.slice(i + prefix.length).split("/")[0] ?? "";
}

export function RawMaterialDetailPage() {
  const { slug } = useParams();
  const { pathname } = useLocation();
  const key = slug || slugFromPath(pathname, "/inventory/raw-materials/");

  if (key === "honey") {
    return <Navigate to="/inventory/raw-materials/honey/warehouse" replace />;
  }

  const route = RAW_MATERIAL_ROUTES[key];
  if (!route) {
    return null;
  }

  return <RawDeptMaterialPage route={route} />;
}
