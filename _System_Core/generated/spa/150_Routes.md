---
title: SPA Routes
generated: 2026-06-12T11:00:26+02:00
type: spa
---


# SPA Routes

> AUTO-GENERATED from `app/src/app/routes.ts`. Manual UX rules: [[../../004_SPA_Design_System]] · [[../../005_SPA_UX_Patterns]]

Basename: `/rts/app/dist` (prod) · dev at `localhost:5173`

| Path | Page component | Roles |
|------|----------------|-------|
| `/dashboard` | `DashboardPage` | any authenticated |
| `/inventory` | `InventoryPage` | any authenticated |
| `/stock-input` | `InventoryPage` | any authenticated |
| `/daily-count` | `InventoryPage` | any authenticated |
| `/beginning-inventory` | `InventoryPage` | any authenticated |
| `/edit-requests` | `EditRequestsPage` | any authenticated |
| `/inventory/:dept` | `InventoryPage` | any authenticated |
| `/staff` | `StaffManagementPage` | executive |
| `/reports` | `ReportsPage` | executive,prod_manager |
| `/sales` | `SalesPage` | executive,prod_manager |
| `/suppliers` | `SuppliersPage` | executive |
| `/costing` | `CostingPage` | executive |
| `/ar` | `ARCreditsPage` | any authenticated |
| `/rts` | `RTSPage` | executive,prod_manager,hr,csr_tl,csr_staff |
| `/rts/record` | `RTSRecordPage` | executive,prod_manager,csr_tl,csr_staff |
| `/rts/records` | `RTSRecordsPage` | executive,prod_manager,hr,csr_tl,csr_staff |
| `/rts/dashboard` | `RTSDashboardPage` | executive,prod_manager,hr,csr_tl,csr_staff |
| `/settings` | `SettingsPage` | executive |
| `/deliveries` | `DeliveriesPage` | executive,prod_manager |
| `/deliveries/review` | `DeliveriesReviewPage` | executive,prod_manager |
| `//` | `Root` | public or POS nested |
| `/honey` | `POSHoneyPage` | public or POS nested |
| `/paragis` | `POSDeptPage` | public or POS nested |
| `/brc` | `POSDeptPage` | public or POS nested |
| `/cacao` | `POSDeptPage` | public or POS nested |
