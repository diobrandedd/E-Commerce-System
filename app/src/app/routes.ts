import { createElement } from "react";
import { createBrowserRouter, Navigate, Outlet } from "react-router";
import { Root } from "./Root";
import { RequireAuth } from "./components/RequireAuth";
import { AppShell } from "./components/PageLayout";
import { LoginPage } from "./pages/LoginPage";
import { DashboardPage } from "./pages/DashboardPage";
import { InventoryPage } from "./pages/InventoryPage";
import { EditRequestsPage } from "./pages/EditRequestsPage";
import { InventoryRequestsPage } from "./pages/InventoryRequestsPage";
import { RTSPage } from "./pages/RTSPage";
import { RTSRecordPage } from "./pages/rts/RTSRecordPage";
import { RTSRecordsPage } from "./pages/rts/RTSRecordsPage";
import { RTSDashboardPage } from "./pages/rts/RTSDashboardPage";
import { RTSLeaksPage } from "./pages/rts/RTSLeaksPage";
import { RTSSwappedPage } from "./pages/rts/RTSSwappedPage";
import { RTSDamagedInventoryPage } from "./pages/rts/RTSDamagedInventoryPage";
import { HoneyWarehousePage } from "./pages/honey/HoneyWarehousePage";
import { HoneyProductionPage } from "./pages/honey/HoneyProductionPage";
import { HoneyPackagingPage } from "./pages/honey/HoneyPackagingPage";
import { HoneyProductInventoryPage } from "./pages/products/HoneyProductInventoryPage";
import { ProductInventoryEntry } from "./pages/products/ProductInventoryEntry";
import { RawMaterialsHubPage } from "./pages/inventory/RawMaterialsHubPage";
import { RawMaterialDetailPage } from "./pages/inventory/RawMaterialDetailPage";
import { PackagingMaterialsPage } from "./pages/inventory/PackagingMaterialsPage";
import { SuppliesMaterialsPage } from "./pages/inventory/SuppliesMaterialsPage";
import { CacaoBrcHubPage } from "./pages/inventory/CacaoBrcHubPage";
import { StaffManagementPage } from "./pages/exec/StaffManagementPage";
import { CostingPage } from "./pages/exec/CostingPage";
import { ARCreditsPage } from "./pages/exec/ARCreditsPage";
import { ReportsPage } from "./pages/exec/ReportsPage";
import { SuppliersPage } from "./pages/exec/SuppliersPage";
import { SettingsPage } from "./pages/exec/SettingsPage";
import { POSCategoryPage } from "./pages/pos/POSCategoryPage";
import { POSDeptPage } from "./pages/pos/POSDeptPage";
import { POSHoneyPage } from "./pages/pos/POSHoneyPage";
import { SalesPage } from "./pages/exec/SalesPage";
import { OnlineOrdersGeneratorPage } from "./pages/csr/OnlineOrdersGeneratorPage";
import { DeliveriesPage } from "./pages/deliveries/DeliveriesPage";
import { DeliveriesReviewPage } from "./pages/deliveries/DeliveriesReviewPage";
import { DeliveriesCancelledPage } from "./pages/deliveries/DeliveriesCancelledPage";
import { OnlineParcelsPage } from "./pages/fulfillment/OnlineParcelsPage";
import { PackingMaterialsStagingPage } from "./pages/fulfillment/PackingMaterialsStagingPage";
import { PackingMaterialsLogPage } from "./pages/fulfillment/PackingMaterialsLogPage";
import { AccountSettingsPage } from "./pages/AccountSettingsPage";
import type { Role } from "./context/AuthContext";

// Wrap a page component in RequireAuth.  Using createElement to keep this
// file pure .ts (no JSX) like the original Figma export.
const guarded = (
  Component: React.ComponentType,
  opts: { roles?: Role[]; allowPOS?: boolean } = {}
) => createElement(RequireAuth, opts, createElement(Component));

const invRoles: Role[] = ["executive", "prod_manager", "hr"];
const invGuard = (Component: React.ComponentType) => guarded(Component, { roles: invRoles });

// Raw materials + finished products are also readable by staff (scoped to their
// own department, read-only, no pricing — enforced server-side in materials.php /
// honey.php). Packaging + Supplies stay invRoles-only.
const invViewRoles: Role[] = ["executive", "prod_manager", "hr", "staff", "team_leader"];
const invViewGuard = (Component: React.ComponentType) => guarded(Component, { roles: invViewRoles });

// In production we ship under Apache at /rts/app/dist/ so React Router
// needs a basename matching that path.  During `vite dev` we run at
// http://localhost:5173/ with no basename.
const basename = import.meta.env.PROD ? "/rts/app/dist" : undefined;

export const router = createBrowserRouter(
  [
    {
      path: "/",
      Component: Root,
      children: [
        { index: true, Component: LoginPage },

        {
          element: createElement(RequireAuth, {}, createElement(AppShell)),
          children: [
        { path: "dashboard", element: guarded(DashboardPage) },
        { path: "inventory", element: guarded(InventoryPage) },
        { path: "stock-input", element: guarded(InventoryPage) },
        { path: "daily-count", element: guarded(InventoryPage) },
        { path: "beginning-inventory", element: guarded(InventoryPage) },
        { path: "edit-requests", element: guarded(EditRequestsPage) },
        { path: "inventory/requests", element: guarded(InventoryRequestsPage, { roles: invRoles }) },
        { path: "inventory/raw-materials", element: invGuard(RawMaterialsHubPage) },
        { path: "inventory/raw-materials/honey", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/warehouse", replace: true }) },
        { path: "inventory/raw-materials/honey/warehouse", element: invViewGuard(HoneyWarehousePage) },
        { path: "inventory/raw-materials/honey/production", element: invViewGuard(HoneyProductionPage) },
        { path: "inventory/raw-materials/honey/packaging", element: invViewGuard(HoneyPackagingPage) },
        { path: "inventory/raw-materials/:slug", element: invViewGuard(RawMaterialDetailPage) },
        { path: "inventory/packaging-materials", element: invGuard(PackagingMaterialsPage) },
        { path: "inventory/supplies", element: invGuard(SuppliesMaterialsPage) },
        { path: "inventory/products/honey", element: invViewGuard(HoneyProductInventoryPage) },
        { path: "inventory/products/honey/warehouse", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/warehouse", replace: true }) },
        { path: "inventory/products/honey/production", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/production", replace: true }) },
        { path: "inventory/products/honey/packaging", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/packaging", replace: true }) },
        { path: "inventory/products/:productSlug", element: invViewGuard(ProductInventoryEntry) },
        { path: "inventory/honey/warehouse", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/warehouse", replace: true }) },
        { path: "inventory/honey/production", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/production", replace: true }) },
        { path: "inventory/honey/packaging", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/packaging", replace: true }) },
        { path: "inventory/paragis", element: createElement(Navigate, { to: "/inventory/products/paragis-tea", replace: true }) },
        { path: "inventory/cacao-brc", element: invGuard(CacaoBrcHubPage) },
        { path: "inventory/:dept", element: guarded(InventoryPage) },

        { path: "staff", element: guarded(StaffManagementPage, { roles: ["executive"] }) },
        { path: "reports", element: guarded(ReportsPage, { roles: ["executive", "prod_manager"] }) },
        { path: "sales", element: guarded(SalesPage, { roles: ["executive", "prod_manager"] }) },
        { path: "suppliers", element: guarded(SuppliersPage, { roles: ["executive"] }) },
        { path: "costing", element: guarded(CostingPage, { roles: ["executive"] }) },
        { path: "ar", element: guarded(ARCreditsPage) },
        {
          path: "waybill/online-orders",
          element: guarded(OnlineOrdersGeneratorPage, {
            roles: ["executive", "csr_tl", "csr_staff"],
          }),
        },
        {
          path: "waybill/stamper",
          element: createElement(Navigate, { to: "/waybill/online-orders", replace: true }),
        },
        {
          path: "waybill/order-remarks",
          element: createElement(Navigate, { to: "/waybill/online-orders", replace: true }),
        },
        {
          path: "waybill/pdf-to-excel",
          element: createElement(Navigate, { to: "/waybill/online-orders", replace: true }),
        },
        {
          path: "waybill-stamper",
          element: createElement(Navigate, { to: "/waybill/online-orders", replace: true }),
        },
        { path: "rts", element: guarded(RTSPage, { roles: ["executive", "prod_manager", "hr", "staff", "csr_tl", "csr_staff", "team_leader"] }) },
        { path: "rts/record", element: guarded(RTSRecordPage, { roles: ["executive", "prod_manager", "csr_tl", "csr_staff"] }) },
        { path: "rts/records", element: guarded(RTSRecordsPage, { roles: ["executive", "prod_manager", "hr", "staff", "csr_tl", "csr_staff", "team_leader"] }) },
        { path: "rts/swapped", element: guarded(RTSSwappedPage, { roles: ["executive", "prod_manager", "hr", "staff", "csr_tl", "csr_staff", "team_leader"] }) },
        { path: "rts/leaks", element: guarded(RTSLeaksPage, { roles: ["executive", "prod_manager", "hr", "staff", "csr_tl", "csr_staff", "team_leader"] }) },
        { path: "rts/damaged", element: guarded(RTSDamagedInventoryPage, { roles: ["executive", "prod_manager", "hr", "staff", "csr_tl", "csr_staff", "team_leader"] }) },
        { path: "rts/dashboard", element: guarded(RTSDashboardPage, { roles: ["executive", "prod_manager", "hr", "staff", "csr_tl", "csr_staff", "team_leader"] }) },
        { path: "settings", element: guarded(SettingsPage, { roles: ["executive"] }) },
        { path: "account", element: guarded(AccountSettingsPage) },

        { path: "deliveries", element: guarded(DeliveriesPage, { roles: ["executive", "prod_manager", "staff"] }) },
        { path: "deliveries/review", element: guarded(DeliveriesReviewPage, { roles: ["executive", "prod_manager", "staff"] }) },
        { path: "deliveries/cancelled", element: guarded(DeliveriesCancelledPage, { roles: ["executive", "prod_manager", "staff"] }) },
        {
          path: "fulfillment/online-parcels",
          element: guarded(OnlineParcelsPage, {
            roles: ["executive", "prod_manager", "staff", "csr_tl", "csr_staff"],
          }),
        },
        {
          path: "fulfillment/packing-materials/log",
          element: guarded(PackingMaterialsLogPage, {
            roles: ["executive", "prod_manager", "team_leader", "staff", "csr_tl"],
          }),
        },
        {
          path: "fulfillment/packing-materials",
          element: guarded(PackingMaterialsStagingPage, {
            roles: ["executive", "prod_manager", "team_leader", "staff", "csr_tl"],
          }),
        },
          ],
        },

        {
          path: "pos",
          element: createElement(
            RequireAuth,
            { allowPOS: true, roles: ["pos"] },
            createElement(Outlet)
          ),
          children: [
            { index: true, Component: POSCategoryPage },
            { path: "honey", Component: POSHoneyPage },
            { path: ":deptKey", Component: POSDeptPage },
          ],
        },
      ],
    },
  ],
  { basename }
);
