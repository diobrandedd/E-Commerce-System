import type { DeptProductKey } from "@/app/lib/deptProducts";
import type { AuthUser, Department, Role } from "@/app/context/AuthContext";
import { hasPermission, type PermissionKey } from "@/app/lib/permissions";

function perms(user: AuthUser | null | undefined): string[] {
  return user?.permissions ?? [];
}

const DEPT_LINE_PERM: Record<DeptProductKey, { view: PermissionKey; produce: PermissionKey; barcodes: PermissionKey }> = {
  pt: { view: "dept.paragis.view", produce: "dept.paragis.produce", barcodes: "dept.paragis.barcodes" },
  brc: { view: "dept.brc.view", produce: "dept.brc.produce", barcodes: "dept.brc.barcodes" },
  jar_cacao: { view: "dept.cacao.view", produce: "dept.cacao.produce", barcodes: "dept.cacao.barcodes" },
  honey_comb: { view: "dept.comb.view", produce: "dept.comb.produce", barcodes: "dept.comb.barcodes" },
};

/** Finished dept stock-in and other exec/PM-only honey inventory mutations. */
export function canMutateHoneyInventory(user: AuthUser | null | undefined): boolean {
  return hasPermission(perms(user), user?.role, "honey.mutate");
}

export function canGenerateHoneyBottleLabels(
  user: AuthUser | null | undefined,
  _dept?: Department | undefined,
): boolean {
  return hasPermission(perms(user), user?.role, "honey.bottle.generate");
}

export function canMutateHoneyPipeline(user: AuthUser | null | undefined): boolean {
  return hasPermission(perms(user), user?.role, "honey.ops");
}

export function canMutateDeptRawProduction(
  user: AuthUser | null | undefined,
  productKey?: DeptProductKey,
): boolean {
  if (productKey) {
    return hasPermission(perms(user), user?.role, DEPT_LINE_PERM[productKey].produce);
  }
  return hasPermission(perms(user), user?.role, [
    "dept.paragis.produce",
    "dept.brc.produce",
    "dept.cacao.produce",
    "dept.comb.produce",
    "honey.dept_produce",
  ]);
}

export function canDeptPouchBarcodeOps(
  user: AuthUser | null | undefined,
  productKey?: DeptProductKey,
): boolean {
  if (productKey) {
    return hasPermission(perms(user), user?.role, DEPT_LINE_PERM[productKey].barcodes);
  }
  return hasPermission(perms(user), user?.role, [
    "dept.paragis.barcodes",
    "dept.brc.barcodes",
    "dept.cacao.barcodes",
    "dept.comb.barcodes",
    "dept.pouch_barcodes",
  ]);
}

export function canViewDeptLine(
  user: AuthUser | null | undefined,
  productKey: DeptProductKey,
): boolean {
  return hasPermission(perms(user), user?.role, DEPT_LINE_PERM[productKey].view);
}

export function canExecutiveDirectEdit(user: AuthUser | null | undefined): boolean {
  return hasPermission(perms(user), user?.role, "honey.executive_edit");
}

export function canViewSupplierProfile(user: AuthUser | null | undefined): boolean {
  return hasPermission(perms(user), user?.role, "suppliers.view");
}

/** @deprecated use AuthUser overload — role-only checks ignore granular permissions */
export function canMutateHoneyInventoryRole(role: Role | undefined): boolean {
  return role === "executive" || role === "prod_manager";
}
