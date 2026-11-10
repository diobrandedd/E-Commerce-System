---
date: 2026-11-10
type: fix
status: applied
area: suppliers
tags: [fix]
---

# Fix: Add suppliers.create for add-only access

Introduce `suppliers.create` in the permission catalog. `api_suppliers_create` accepts `suppliers.manage` OR `suppliers.create`. Update/activate/deactivate stay on manage. SPA shows Add Supplier when `canCreateSuppliers` is true; row Edit/Deactivate and inline pricing stay on `canManageSuppliers`.
