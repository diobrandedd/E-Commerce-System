---
title: SPA Routes
generated: 2026-08-15T05:00:54+02:00
type: spa
---


# SPA Routes

> AUTO-GENERATED from `app/src/app/routes.ts`. Manual UX rules: [[../../004_SPA_Design_System]] · [[../../005_SPA_UX_Patterns]]

Basename: `/rts/app/dist` (prod) · dev at `localhost:5173`

| Path | Page component | Roles |
|------|----------------|-------|
| `/dashboard` | `DashboardPage` | any authenticated |
| `/verify-barcode` | `BarcodeVerifyPage` | any authenticated |
| `/inventory` | `InventoryPage` | any authenticated |
| `/stock-input` | `InventoryPage` | any authenticated |
| `/daily-count` | `InventoryPage` | any authenticated |
| `/beginning-inventory` | `InventoryPage` | any authenticated |
| `/edit-requests` | `EditRequestsPage` | any authenticated |
| `/inventory/monthly-count` | `MonthlyInventoryPage` | executive |
| `/inventory/:dept` | `InventoryPage` | any authenticated |
| `/staff` | `StaffManagementPage` | executive |
| `/reports` | `ReportsPage` | executive,prod_manager |
| `/sales` | `SalesPage` | executive,prod_manager |
| `/sales/bulk-orders` | `BulkHoneyOrdersPage` | executive |
| `/suppliers` | `SuppliersPage` | executive |
| `/costing` | `CostingPage` | executive |
| `/ar` | `ARCreditsPage` | any authenticated |
| `/rts` | `RTSPage` | executive,prod_manager,hr,staff,csr_tl,csr_staff,team_leader |
| `/rts/record` | `RTSRecordPage` | executive,prod_manager,csr_tl,csr_staff |
| `/rts/records` | `RTSRecordsPage` | executive,prod_manager,hr,staff,csr_tl,csr_staff,team_leader |
| `/rts/swapped` | `RTSSwappedPage` | executive,prod_manager,hr,staff,csr_tl,csr_staff,team_leader |
| `/rts/leaks` | `RTSLeaksPage` | executive,prod_manager,hr,staff,csr_tl,csr_staff,team_leader |
| `/rts/damaged` | `RTSDamagedInventoryPage` | executive,prod_manager,hr,staff,csr_tl,csr_staff,team_leader |
| `/rts/dashboard` | `RTSDashboardPage` | executive,prod_manager,hr,staff,csr_tl,csr_staff,team_leader |
| `/settings` | `SettingsPage` | executive |
| `/account` | `AccountSettingsPage` | any authenticated |
| `/deliveries` | `DeliveriesPage` | executive,prod_manager,staff |
| `/deliveries/pack-scan` | `HoneyPackScanPage` | executive,prod_manager,staff |
| `/deliveries/review` | `DeliveriesReviewPage` | executive,prod_manager,staff |
| `/deliveries/cancelled` | `DeliveriesCancelledPage` | executive,prod_manager,staff |
| `//` | `Root` | public or POS nested |
| `/honey` | `POSHoneyPage` | public or POS nested |
| `/:deptKey` | `POSDeptPage` | public or POS nested |
