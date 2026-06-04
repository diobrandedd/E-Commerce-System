import { useMemo, useState } from "react";
import { CheckCircle2 } from "lucide-react";
import { VariantCardStrip } from "@/app/components/csr/VariantCardStrip";
import {
  OrderRemarksAnimatedCount,
  OrderRemarksCarouselCard,
  OrderRemarksDoneBadge,
  remarksSpring,
} from "@/app/components/csr/orderRemarksMotion";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";
import { honeySizeFromItemLine, shopeeRowNeedsItemResolution } from "@/app/lib/honeyItemVariant";
import { cn } from "@/app/components/ui/utils";
import { motion, useReducedMotion } from "motion/react";

const HONEY_ITEM_PRESETS = [
  { value: "Light 350ml", label: "Light 350ml" },
  { value: "Amber 350ml", label: "Amber 350ml" },
  { value: "Dark 350ml", label: "Dark 350ml" },
  { value: "Light 1L", label: "Light 1L" },
  { value: "Amber 1L", label: "Amber 1L" },
  { value: "Dark 1L", label: "Dark 1L" },
  { value: "Light Gallon", label: "Light Gallon" },
  { value: "Amber Gallon", label: "Amber Gallon" },
  { value: "Dark Gallon", label: "Dark Gallon" },
];

export interface ShopeeRowResolution {
  items: string;
}

interface ItemResolverCarouselProps {
  rows: PdfToExcelRow[];
  resolutions: Record<number, ShopeeRowResolution>;
  onResolve: (page: number, resolution: ShopeeRowResolution) => void;
  disabled?: boolean;
  onAllDone?: () => void;
}

export function ItemResolverCarousel({
  rows,
  resolutions,
  onResolve,
  disabled,
  onAllDone,
}: ItemResolverCarouselProps) {
  const incomplete = useMemo(
    () => rows.filter(r => shopeeRowNeedsItemResolution(r, resolutions[r.page]?.items)),
    [rows, resolutions],
  );

  const presetsForRow = (extracted: string) => {
    const size = honeySizeFromItemLine(extracted);
    if (!size) return HONEY_ITEM_PRESETS;
    return HONEY_ITEM_PRESETS.filter(p => {
      if (size === "350ml") return p.label.includes("350ml");
      if (size === "1L") return p.label.includes("1L");
      if (size === "2L") return p.label.includes("2L") || p.label.includes("Gallon");
      if (size === "3L") return p.label.includes("Gallon");
      if (size === "gallon") return p.label.includes("Gallon");
      return true;
    });
  };
  const [custom, setCustom] = useState("");
  const reduceMotion = useReducedMotion();

  const pending = incomplete.filter(r => !resolutions[r.page]?.items?.trim());
  const current = pending[0];

  if (incomplete.length === 0) {
    return (
      <p className="text-sm text-green-700 flex items-center gap-2">
        <CheckCircle2 size={18} /> All rows have items.
      </p>
    );
  }

  if (!current) {
    return (
      <p className="text-sm text-green-700 flex items-center gap-2">
        <CheckCircle2 size={18} /> All items confirmed.
      </p>
    );
  }

  const done = (page: number) => Boolean(resolutions[page]?.items?.trim());

  return (
    <div className="space-y-3">
      <h2 className="text-sm font-semibold text-foreground">
        Pick honey variant (PDF often shows size only, e.g. &quot;1 x 1L HONEY&quot;) —{" "}
        <OrderRemarksAnimatedCount value={pending.length} className="tabular-nums" /> remaining
      </h2>
      <div className="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2 -mx-1 px-1">
        {incomplete.map(row => (
          <OrderRemarksCarouselCard key={row.page} active={row.page === current.page}>
            <motion.div
              animate={
                reduceMotion
                  ? undefined
                  : { borderColor: done(row.page) ? "rgb(134 239 172)" : "rgb(252 211 77)" }
              }
              transition={remarksSpring}
              className={cn(
                "flex-shrink-0 snap-start w-[min(100%,300px)] sm:w-[300px] bg-card rounded-xl border p-4",
                done(row.page) ? "border-green-300" : "border-amber-300",
                row.page === current.page && "ring-2 ring-primary/30",
              )}
            >
              <p className="text-[10px] text-muted-foreground uppercase">Page {row.page}</p>
              <p className="font-semibold text-sm truncate">{row.name || "—"}</p>
              <p className="text-xs text-muted-foreground font-mono truncate">{row.tracking}</p>
              <p className="text-xs text-muted-foreground">{row.courier}</p>
              {row.items?.trim() && (
                <p className="text-xs mt-1 text-amber-800 bg-amber-50 rounded px-2 py-1">
                  From PDF: <span className="font-medium">{row.items}</span>
                </p>
              )}
              {row.page === current.page && (
                <div className="mt-3 space-y-2">
                  <VariantCardStrip
                    presets={presetsForRow(row.items)}
                    selected={resolutions[row.page]?.items}
                    onSelect={v => onResolve(row.page, { items: v })}
                    disabled={disabled}
                    title="Pick Light / Amber / Dark for this size"
                  />
                  <input
                    type="text"
                    value={custom}
                    onChange={e => setCustom(e.target.value)}
                    placeholder="Or type exact item line…"
                    className="w-full text-xs border rounded-lg px-2 py-1.5"
                    disabled={disabled}
                  />
                  <button
                    type="button"
                    disabled={disabled || !custom.trim()}
                    onClick={() => {
                      onResolve(row.page, { items: custom.trim() });
                      setCustom("");
                      if (pending.length <= 1) onAllDone?.();
                    }}
                    className="w-full text-xs font-semibold py-2 rounded-lg text-white bg-primary disabled:opacity-50"
                  >
                    Confirm item
                  </button>
                </div>
              )}
              {done(row.page) && <OrderRemarksDoneBadge />}
            </motion.div>
          </OrderRemarksCarouselCard>
        ))}
      </div>
    </div>
  );
}
