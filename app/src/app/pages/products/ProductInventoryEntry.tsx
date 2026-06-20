import { useParams } from "react-router";
import { DeptInventoryPage } from "@/app/pages/dept/DeptInventoryPage";
import { DeptProductFinishedPage } from "@/app/pages/products/DeptProductFinishedPage";
import { PRODUCT_INVENTORY_ROUTES } from "@/app/lib/inventoryRoutes";

export function ProductInventoryEntry() {
  const { productSlug = "" } = useParams();
  if (PRODUCT_INVENTORY_ROUTES[productSlug]) {
    return <DeptProductFinishedPage />;
  }
  return <DeptInventoryPage />;
}
