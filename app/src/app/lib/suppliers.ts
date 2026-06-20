import { apiGet, apiPost, apiPostForm, getCsrfToken } from "@/app/lib/api";

export type SupplierCategory = "honey" | "paragis" | "black_rice" | "cacao" | "honey_comb";

export interface Supplier {
  id: number;
  first_name: string;
  last_name: string;
  display_name: string;
  contact_number: string;
  address: string;
  category: SupplierCategory;
  category_label: string;
  is_active: boolean;
  valid_id_url: string;
  created_at: string;
  updated_at: string;
  payment_account?: string;
  payment_account_number?: string;
}

export const SUPPLIER_CATEGORIES: { value: SupplierCategory; label: string }[] = [
  { value: "honey", label: "Honey" },
  { value: "paragis", label: "Paragis" },
  { value: "black_rice", label: "Black Rice" },
  { value: "cacao", label: "Cacao" },
  { value: "honey_comb", label: "Honey Comb" },
];

/** Mirror PHP honey_supplier_code: initials of every word + MMDDYY */
export function previewSupplierCode(displayName: string, arrivalDate: string): string {
  const words = displayName.trim().split(/\s+/).filter(Boolean);
  let initials = words.map(w => w[0]?.toUpperCase() ?? "").join("");
  if (!initials) initials = "XX";
  const d = new Date(arrivalDate + "T12:00:00");
  if (Number.isNaN(d.getTime())) return initials;
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  const yy = String(d.getFullYear()).slice(-2);
  return initials + mm + dd + yy;
}

export function listSuppliers(params?: {
  category?: SupplierCategory;
  active?: boolean;
  q?: string;
}): Promise<{ suppliers: Supplier[] }> {
  return apiGet("suppliers.php", "list", {
    category: params?.category,
    active: params?.active ? "1" : undefined,
    q: params?.q,
  });
}

export function getSupplier(id: number): Promise<{ supplier: Supplier }> {
  return apiGet("suppliers.php", "get", { id });
}

export function getSupplierByHoneyCode(code: string): Promise<{ supplier: Supplier; supplier_code: string }> {
  return apiGet("suppliers.php", "get_by_code", { code });
}

export function createSupplierForm(form: FormData): Promise<{ supplier: Supplier }> {
  const token = getCsrfToken();
  if (token) form.set("csrf", token);
  return apiPostForm("suppliers.php", "create", form);
}

export function updateSupplierForm(form: FormData): Promise<{ supplier: Supplier }> {
  const token = getCsrfToken();
  if (token) form.set("csrf", token);
  return apiPostForm("suppliers.php", "update", form);
}

export function setSupplierActive(id: number, active: boolean): Promise<{ supplier: Supplier }> {
  return apiPost("suppliers.php", active ? "activate" : "deactivate", { id });
}
