import { createElement } from "react";
import { createBrowserRouter, Navigate, Outlet } from "react-router";
import { Root } from "./Root";
import { RequireAuth } from "./components/RequireAuth";
import { LoginPage } from "./pages/LoginPage";
import { DashboardPage } from "./pages/DashboardPage";
import { InventoryPage } from "./pages/InventoryPage";
import { EditRequestsPage } from "./pages/EditRequestsPage";
import { InventoryRequestsPage } from "./pages/InventoryRequestsPage";
import { RTSPage } from "./pages/RTSPage";
import { HoneyWarehousePage } from "./pages/honey/HoneyWarehousePage";
import { HoneyProductionPage } from "./pages/honey/HoneyProductionPage";
import { HoneyPackagingPage } from "./pages/honey/HoneyPackagingPage";
import { DeptInventoryPage } from "./pages/dept/DeptInventoryPage";
import { StaffManagementPage } from "./pages/exec/StaffManagementPage";
import { CostingPage } from "./pages/exec/CostingPage";
import { ARCreditsPage } from "./pages/exec/ARCreditsPage";
import { ReportsPage } from "./pages/exec/ReportsPage";
import { SuppliersPage } from "./pages/exec/SuppliersPage";
import { SettingsPage } from "./pages/exec/SettingsPage";
import { POSCategoryPage } from "./pages/pos/POSCategoryPage";
import { POSHoneyPage } from "./pages/pos/POSHoneyPage";
import { SalesPage } from "./pages/exec/SalesPage";
import { WaybillStamperPage } from "./pages/csr/WaybillStamperPage";
import { OrderRemarksPage } from "./pages/csr/OrderRemarksPage";
import { PdfToExcelPage } from "./pages/csr/PdfToExcelPage";
import type { Role } from "./context/AuthContext";

// Wrap a page component in RequireAuth.  Using createElement to keep this
// file pure .ts (no JSX) like the original Figma export.
const guarded = (
  Component: React.ComponentType,
  opts: { roles?: Role[]; allowPOS?: boolean } = {}
) => createElement(RequireAuth, opts, createElement(Component));

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

        { path: "dashboard", element: guarded(DashboardPage) },
        { path: "inventory/:dept", element: guarded(InventoryPage) },
        { path: "inventory", element: guarded(InventoryPage) },
        { path: "stock-input", element: guarded(InventoryPage) },
        { path: "daily-count", element: guarded(InventoryPage) },
        { path: "beginning-inventory", element: guarded(InventoryPage) },
        { path: "edit-requests", element: guarded(EditRequestsPage) },
        { path: "inventory/requests", element: guarded(InventoryRequestsPage, { roles: ["executive", "prod_manager", "hr"] }) },
        { path: "inventory/honey/warehouse", element: guarded(HoneyWarehousePage, { roles: ["executive", "prod_manager", "hr"] }) },
        { path: "inventory/honey/production", element: guarded(HoneyProductionPage, { roles: ["executive", "prod_manager", "hr"] }) },
        { path: "inventory/honey/packaging", element: guarded(HoneyPackagingPage, { roles: ["executive", "prod_manager", "hr"] }) },
        { path: "inventory/paragis", element: guarded(DeptInventoryPage, { roles: ["executive", "prod_manager", "hr"] }) },
        { path: "inventory/cacao-brc", element: guarded(DeptInventoryPage, { roles: ["executive", "prod_manager", "hr"] }) },

        { path: "staff", element: guarded(StaffManagementPage, { roles: ["executive"] }) },
        { path: "reports", element: guarded(ReportsPage, { roles: ["executive", "prod_manager"] }) },
        { path: "sales", element: guarded(SalesPage, { roles: ["executive", "prod_manager"] }) },
        { path: "suppliers", element: guarded(SuppliersPage, { roles: ["executive"] }) },
        { path: "costing", element: guarded(CostingPage, { roles: ["executive", "prod_manager"] }) },
        { path: "ar", element: guarded(ARCreditsPage) },
        {
          path: "waybill/stamper",
          element: guarded(WaybillStamperPage, { roles: ["executive", "csr_tl"] }),
        },
        {
          path: "waybill/order-remarks",
          element: guarded(OrderRemarksPage, { roles: ["executive", "csr_tl"] }),
        },
        {
          path: "waybill/pdf-to-excel",
          element: guarded(PdfToExcelPage, { roles: ["executive", "csr_tl"] }),
        },
        {
          path: "waybill-stamper",
          element: createElement(Navigate, { to: "/waybill/stamper", replace: true }),
        },
        { path: "rts", element: guarded(RTSPage, { roles: ["executive", "prod_manager", "hr", "csr_tl", "csr_staff"] }) },
        { path: "rts/record", element: guarded(RTSPage, { roles: ["executive", "prod_manager", "hr", "csr_tl", "csr_staff"] }) },
        { path: "settings", element: guarded(SettingsPage, { roles: ["executive"] }) },

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
          ],
        },
      ],
    },
  ],
  { basename }
);
