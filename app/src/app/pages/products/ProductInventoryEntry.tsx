import { useLocation, useParams } from "react-router";
import { DeptInventoryPage } from "@/app/pages/dept/DeptInventoryPage";
import { DeptProductFinishedPage } from "@/app/pages/products/DeptProductFinishedPage";
import { PRODUCT_INVENTORY_ROUTES } from "@/app/lib/inventoryRoutes";

export function ProductInventoryEntry() {
  const { productSlug: paramSlug } = useParams();
  const { pathname } = useLocation();
  const fromPath = pathname.includes("/inventory/products/")
    ? (pathname.split("/inventory/products/")[1]?.split("/")[0] ?? "")
    : "";
  const productSlug = paramSlug || fromPath;
  if (PRODUCT_INVENTORY_ROUTES[productSlug]) {
    return <DeptProductFinishedPage productSlug={productSlug} />;
  }
  return <DeptInventoryPage />;
}
