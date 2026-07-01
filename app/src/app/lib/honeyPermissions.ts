import type { Role } from "@/app/context/AuthContext";

export function canMutateHoneyInventory(role: Role | undefined): boolean {
  return role === "executive" || role === "prod_manager";
}

export function canExecutiveDirectEdit(role: Role | undefined): boolean {
  return role === "executive";
}

export function canViewSupplierProfile(role: Role | undefined): boolean {
  return role === "executive";
}
