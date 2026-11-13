import type { InventoryCheckKind } from "@/app/lib/inventoryCheck";

const KIND_STYLES: Record<InventoryCheckKind, string> = {
  honey: "bg-amber-100 text-amber-900 border-amber-200",
  dept: "bg-sky-100 text-sky-900 border-sky-200",
  freebie: "bg-violet-100 text-violet-900 border-violet-200",
  supply: "bg-orange-100 text-orange-950 border-orange-300",
};

const KIND_FALLBACK: Record<InventoryCheckKind, string> = {
  honey: "Honey",
  dept: "Product",
  freebie: "Freebie",
  supply: "Supplies",
};

export function InventoryCheckKindBadge({
  kind,
  typeLabel,
  className = "",
}: {
  kind: InventoryCheckKind | string;
  typeLabel?: string | null;
  className?: string;
}) {
  const k = (kind === "honey" || kind === "dept" || kind === "freebie" || kind === "supply"
    ? kind
    : "dept") as InventoryCheckKind;
  const label = (typeLabel && typeLabel.trim() !== "" ? typeLabel : KIND_FALLBACK[k]).trim();

  return (
    <span
      className={`inline-flex items-center rounded-md border px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${KIND_STYLES[k]} ${className}`}
    >
      {label}
    </span>
  );
}
