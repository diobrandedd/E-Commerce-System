import { createElement } from "react";
import { createBrowserRouter } from "react-router";
import { Root } from "./Root";
import { RequireAuth } from "./components/RequireAuth";
import { LoginPage } from "./pages/LoginPage";
import { DashboardPage } from "./pages/DashboardPage";
import { InventoryPage } from "./pages/InventoryPage";
import { EditRequestsPage } from "./pages/EditRequestsPage";
import { RTSPage } from "./pages/RTSPage";
import { StaffManagementPage } from "./pages/exec/StaffManagementPage";
import { CostingPage } from "./pages/exec/CostingPage";
import { ARCreditsPage } from "./pages/exec/ARCreditsPage";
import { ReportsPage } from "./pages/exec/ReportsPage";
import { SuppliersPage } from "./pages/exec/SuppliersPage";
import { SettingsPage } from "./pages/exec/SettingsPage";
import { POSPage } from "./pages/pos/POSPage";
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

        { path: "staff", element: guarded(StaffManagementPage, { roles: ["executive"] }) },
        { path: "reports", element: guarded(ReportsPage, { roles: ["executive", "prod_manager"] }) },
        { path: "suppliers", element: guarded(SuppliersPage, { roles: ["executive"] }) },
        { path: "costing", element: guarded(CostingPage, { roles: ["executive", "prod_manager"] }) },
        { path: "ar", element: guarded(ARCreditsPage) },
        { path: "rts", element: guarded(RTSPage) },
        { path: "rts/record", element: guarded(RTSPage) },
        { path: "settings", element: guarded(SettingsPage) },
      ],
    },
    {
      path: "/pos",
      element: createElement(
        RequireAuth,
        { allowPOS: true, roles: ["pos"] },
        createElement(POSPage)
      ),
    },
  ],
  { basename }
);
