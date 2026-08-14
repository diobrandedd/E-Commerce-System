import type { Department, Role } from "@/app/context/AuthContext";

/** Finished dept stock-in and other exec/PM-only honey inventory mutations. */
export function canMutateHoneyInventory(role: Role | undefined): boolean {
  return role === "executive" || role === "prod_manager";
}

/**
 * Generate / print honey bottle barcodes — executive or CSR only.
 * Honey staff / TL / prod_manager request and scan, but do not generate.
 */
export function canGenerateHoneyBottleLabels(
  role: Role | undefined,
  dept?: Department | undefined,
): boolean {
  if (role === "executive") return true;
  if (role === "csr_tl" || role === "csr_staff") return true;
  if (dept === "csr" && (role === "staff" || role === "team_leader")) return true;
  return false;
}

/**
 * Honey gallon pipeline UI (warehouse / production → packaging).
 * SPA maps IMS `tl` → team_leader; API still scopes TL to honey dept.
 * Packaging Sell was removed — use POS.
 */
export function canMutateHoneyPipeline(role: Role | undefined): boolean {
  return role === "executive" || role === "prod_manager" || role === "team_leader";
}

/** Dept raw Stock in / Use raw bags / Pouch / Produce (SPA maps IMS `tl` → team_leader). */
export function canMutateDeptRawProduction(role: Role | undefined): boolean {
  return role === "executive" || role === "prod_manager" || role === "team_leader";
}

export function canExecutiveDirectEdit(role: Role | undefined): boolean {
  return role === "executive";
}

export function canViewSupplierProfile(role: Role | undefined): boolean {
  return role === "executive";
}
