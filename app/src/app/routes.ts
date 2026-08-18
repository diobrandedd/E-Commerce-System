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
import { BarcodeVerifyPage } from "./pages/BarcodeVerifyPage";
import { HoneyProductInventoryPage } from "./pages/products/HoneyProductInventoryPage";
import { ProductInventoryEntry } from "./pages/products/ProductInventoryEntry";
import { RawMaterialsHubPage } from "./pages/inventory/RawMaterialsHubPage";
import { RawMaterialDetailPage } from "./pages/inventory/RawMaterialDetailPage";
import { PackagingMaterialsPage } from "./pages/inventory/PackagingMaterialsPage";
import { SuppliesMaterialsPage } from "./pages/inventory/SuppliesMaterialsPage";
import { CacaoBrcHubPage } from "./pages/inventory/CacaoBrcHubPage";
import { FinishedLeakDamagePage } from "./pages/inventory/FinishedLeakDamagePage";
import { StaffManagementPage } from "./pages/exec/StaffManagementPage";
import { CostingPage } from "./pages/exec/CostingPage";
import { ARCreditsPage } from "./pages/exec/ARCreditsPage";
import { ReportsPage } from "./pages/exec/ReportsPage";
import { SuppliersPage } from "./pages/exec/SuppliersPage";
import { SettingsPage } from "./pages/exec/SettingsPage";
import { MonthlyInventoryPage } from "./pages/exec/MonthlyInventoryPage";
import { POSUnifiedPage } from "./pages/pos/POSUnifiedPage";
import { POSLayout } from "./components/pos/POSLayout";
import { SalesPage } from "./pages/exec/SalesPage";
import { BulkHoneyOrdersPage } from "./pages/sales/BulkHoneyOrdersPage";
import { OnlineOrdersGeneratorPage } from "./pages/csr/OnlineOrdersGeneratorPage";
import { DeliveriesPage } from "./pages/deliveries/DeliveriesPage";
import { DeliveriesReviewPage } from "./pages/deliveries/DeliveriesReviewPage";
import { DeliveriesCancelledPage } from "./pages/deliveries/DeliveriesCancelledPage";
import { HoneyPackScanPage } from "./pages/deliveries/HoneyPackScanPage";
import { OnlineParcelsPage } from "./pages/fulfillment/OnlineParcelsPage";
import { PartnerSoldReconcilePage } from "./pages/fulfillment/PartnerSoldReconcilePage";
import { CourierSoaReconcilePage } from "./pages/fulfillment/CourierSoaReconcilePage";
import { HistoricalOrdersImportPage } from "./pages/fulfillment/HistoricalOrdersImportPage";
import { PackingMaterialsStagingPage } from "./pages/fulfillment/PackingMaterialsStagingPage";
import { PackingMaterialsLogPage } from "./pages/fulfillment/PackingMaterialsLogPage";
import { AccountSettingsPage } from "./pages/AccountSettingsPage";
import type { Role } from "./context/AuthContext";
import type { PermissionKey } from "./lib/permissions";

// Wrap a page component in RequireAuth.  Using createElement to keep this
// file pure .ts (no JSX) like the original Figma export.
const guarded = (
  Component: React.ComponentType,
  opts: { roles?: Role[]; permission?: PermissionKey | PermissionKey[]; allowPOS?: boolean } = {}
) => createElement(RequireAuth, opts, createElement(Component));

const permGuard = (Component: React.ComponentType, permission: PermissionKey | PermissionKey[]) =>
  guarded(Component, { permission });

const invPerm: PermissionKey[] = ["packaging.view", "supplies.view"];
const invGuard = (Component: React.ComponentType) => guarded(Component, { permission: invPerm });

const invViewPerm: PermissionKey[] = [
  "inventory.raw.view",
  "inventory.products.view",
  "honey.view",
  "dept.paragis.view",
  "dept.brc.view",
  "dept.cacao.view",
  "dept.comb.view",
];
const invViewGuard = (Component: React.ComponentType) => guarded(Component, { permission: invViewPerm });

const honeyProductionPerm: PermissionKey[] = [
  "honey.ops",
  "honey.dept_produce",
  "honey.bottle.request",
  "honey.bottle.scan",
  "honey.bottle.generate",
  "honey.view",
];
const honeyProductionGuard = (Component: React.ComponentType) =>
  guarded(Component, { permission: honeyProductionPerm });

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
        { path: "verify-barcode", element: guarded(BarcodeVerifyPage) },
        { path: "inventory", element: guarded(InventoryPage) },
        { path: "stock-input", element: guarded(InventoryPage) },
        { path: "daily-count", element: guarded(InventoryPage) },
        { path: "beginning-inventory", element: guarded(InventoryPage) },
        { path: "edit-requests", element: guarded(EditRequestsPage) },
        { path: "inventory/requests", element: permGuard(InventoryRequestsPage, "inventory.requests.view") },
        { path: "inventory/monthly-count", element: permGuard(MonthlyInventoryPage, "inventory.monthly_count") },
        { path: "inventory/raw-materials", element: invViewGuard(RawMaterialsHubPage) },
        { path: "inventory/raw-materials/honey", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/warehouse", replace: true }) },
        { path: "inventory/raw-materials/honey/warehouse", element: invViewGuard(HoneyWarehousePage) },
        { path: "inventory/raw-materials/honey/production", element: honeyProductionGuard(HoneyProductionPage) },
        { path: "inventory/raw-materials/honey/packaging", element: invViewGuard(HoneyPackagingPage) },
        { path: "inventory/raw-materials/honey/bottles", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/production", replace: true }) },
        { path: "inventory/raw-materials/paragis", element: permGuard(RawMaterialDetailPage, "dept.paragis.view") },
        { path: "inventory/raw-materials/black-rice", element: permGuard(RawMaterialDetailPage, "dept.brc.view") },
        { path: "inventory/raw-materials/cacao", element: permGuard(RawMaterialDetailPage, "dept.cacao.view") },
        { path: "inventory/raw-materials/honey-comb", element: permGuard(RawMaterialDetailPage, "dept.comb.view") },
        { path: "inventory/raw-materials/:slug", element: invViewGuard(RawMaterialDetailPage) },
        { path: "inventory/packaging-materials", element: invGuard(PackagingMaterialsPage) },
        { path: "inventory/supplies", element: invGuard(SuppliesMaterialsPage) },
        { path: "inventory/products/honey", element: invViewGuard(HoneyProductInventoryPage) },
        { path: "inventory/products/honey/warehouse", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/warehouse", replace: true }) },
        { path: "inventory/products/honey/production", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/production", replace: true }) },
        { path: "inventory/products/honey/packaging", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/packaging", replace: true }) },
        { path: "inventory/products/paragis-tea", element: permGuard(ProductInventoryEntry, "dept.paragis.view") },
        { path: "inventory/products/black-rice-coffee", element: permGuard(ProductInventoryEntry, "dept.brc.view") },
        { path: "inventory/products/cacao-tablets", element: permGuard(ProductInventoryEntry, "dept.cacao.view") },
        { path: "inventory/products/honey-comb", element: permGuard(ProductInventoryEntry, "dept.comb.view") },
        { path: "inventory/products/:productSlug", element: invViewGuard(ProductInventoryEntry) },
        { path: "inventory/honey/warehouse", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/warehouse", replace: true }) },
        { path: "inventory/honey/production", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/production", replace: true }) },
        { path: "inventory/honey/packaging", element: createElement(Navigate, { to: "/inventory/raw-materials/honey/packaging", replace: true }) },
        { path: "inventory/paragis", element: createElement(Navigate, { to: "/inventory/products/paragis-tea", replace: true }) },
        { path: "inventory/cacao-brc", element: permGuard(CacaoBrcHubPage, ["dept.brc.view", "dept.cacao.view"]) },
        { path: "inventory/leak-damage", element: permGuard(FinishedLeakDamagePage, "inventory.leak_damage.view") },
        { path: "inventory/:dept", element: guarded(InventoryPage) },

        { path: "staff", element: permGuard(StaffManagementPage, "staff.manage") },
        { path: "reports", element: permGuard(ReportsPage, "reports.view") },
        { path: "sales", element: permGuard(SalesPage, "sales.view") },
        { path: "sales/bulk-orders", element: permGuard(BulkHoneyOrdersPage, "sales.bulk_orders") },
        { path: "suppliers", element: permGuard(SuppliersPage, "suppliers.view") },
        { path: "costing", element: permGuard(CostingPage, "costing.view") },
        { path: "ar", element: permGuard(ARCreditsPage, "ar.take") },
        {
          path: "waybill/online-orders",
          element: permGuard(OnlineOrdersGeneratorPage, "csr.online_orders"),
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
        { path: "rts", element: permGuard(RTSPage, "rts.view") },
        { path: "rts/record", element: permGuard(RTSRecordPage, "rts.record") },
        { path: "rts/records", element: permGuard(RTSRecordsPage, "rts.view") },
        { path: "rts/swapped", element: permGuard(RTSSwappedPage, "rts.view") },
        { path: "rts/leaks", element: permGuard(RTSLeaksPage, "rts.view") },
        { path: "rts/damaged", element: permGuard(RTSDamagedInventoryPage, "rts.view") },
        { path: "rts/dashboard", element: permGuard(RTSDashboardPage, "rts.view") },
        { path: "settings", element: permGuard(SettingsPage, "settings.manage") },
        { path: "account", element: guarded(AccountSettingsPage) },

        { path: "deliveries", element: permGuard(DeliveriesPage, "deliveries.view") },
        { path: "deliveries/pack-scan", element: permGuard(HoneyPackScanPage, "deliveries.pack_scan") },
        { path: "deliveries/review", element: permGuard(DeliveriesReviewPage, "deliveries.review") },
        { path: "deliveries/cancelled", element: permGuard(DeliveriesCancelledPage, "deliveries.view") },
        {
          path: "fulfillment/partner-sold-reconcile",
          element: permGuard(PartnerSoldReconcilePage, "csr.reconcile.partner_sold"),
        },
        {
          path: "fulfillment/courier-soa-reconcile",
          element: permGuard(CourierSoaReconcilePage, "csr.reconcile.courier_soa"),
        },
        {
          path: "fulfillment/historical-orders-import",
          element: permGuard(HistoricalOrdersImportPage, "csr.historical_import"),
        },
        {
          path: "fulfillment/online-parcels",
          element: permGuard(OnlineParcelsPage, "csr.parcels.view"),
        },
        {
          path: "fulfillment/packing-materials/log",
          element: permGuard(PackingMaterialsLogPage, "packing.materials.view"),
        },
        {
          path: "fulfillment/packing-materials",
          element: permGuard(PackingMaterialsStagingPage, "packing.materials.view"),
        },
          ],
        },

        {
          path: "pos",
          element: createElement(
            RequireAuth,
            { allowPOS: true, permission: "pos.sell" },
            createElement(POSLayout)
          ),
          children: [
            { index: true, Component: POSUnifiedPage },
            { path: "honey", element: createElement(Navigate, { to: "/pos", replace: true }) },
            { path: ":deptKey", element: createElement(Navigate, { to: "/pos", replace: true }) },
          ],
        },
      ],
    },
  ],
  { basename }
);
