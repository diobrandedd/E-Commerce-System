import type { Role } from "@/app/context/AuthContext";

/** Finished dept stock-in and other exec/PM-only honey inventory mutations. */
export function canMutateHoneyInventory(role: Role | undefined): boolean {
  return role === "executive" || role === "prod_manager";
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
