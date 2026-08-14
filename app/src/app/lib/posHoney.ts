import type { HoneyBottleSize } from "@/app/lib/honey";

export const HONEY_POS_VARIANTS = ["light", "amber", "dark"] as const;
export type HoneyPosVariant = (typeof HONEY_POS_VARIANTS)[number];

export interface HoneyCatalogSize {
  size: HoneyBottleSize;
  label: string;
  available_qty: number;
  product_id: number | null;
  unit_price: number | null;
  sellable: boolean;
}

export interface HoneyCatalogVariant {
  variant: HoneyPosVariant;
  image: string;
  total_on_hand: number;
  sizes: HoneyCatalogSize[];
}

export interface HoneyCartLine {
  key: string;
  variant: HoneyPosVariant;
  size: HoneyBottleSize;
  sizeLabel: string;
  productId: number;
  unitPrice: number;
  qty: number;
  bottleUnitIds?: number[];
}

export interface HoneyCheckoutResult {
  sale_id: number;
  receipt_no: string;
  subtotal?: number;
  discount_percent?: number | null;
  discount_amount?: number;
  total: number;
  buyer_name: string;
  items: {
    product_id: number;
    qty: number;
    list_unit_price?: number;
    unit_price: number;
    line_subtotal?: number;
    line_total: number;
    name: string;
    variant?: string;
    size?: string;
  }[];
}

export interface HoneyDiscountRequestResult {
  request_id: number;
  request_no: string;
  status: "pending" | "approved" | "declined";
  buyer_name: string;
  discount_percent: number;
  subtotal: number;
  discount_amount: number;
  total: number;
}

export interface HoneyDiscountRequestStatus extends HoneyDiscountRequestResult {
  decline_note?: string | null;
  receipt?: HoneyCheckoutResult;
}

export const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function variantLabel(v: string): string {
  return v.charAt(0).toUpperCase() + v.slice(1);
}

export function cartLineKey(variant: string, size: string): string {
  return `${variant}|${size}`;
}
