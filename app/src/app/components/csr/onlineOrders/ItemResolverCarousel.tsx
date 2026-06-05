import { useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { CheckCircle2, Pencil } from "lucide-react";
import { VariantCardStrip } from "@/app/components/csr/VariantCardStrip";
import {
  OrderRemarksAllDonePanel,
  OrderRemarksAnimatedCount,
  OrderRemarksCarouselCard,
  OrderRemarksDoneBadge,
  OrderRemarksStepReveal,
  remarksSpring,
} from "@/app/components/csr/orderRemarksMotion";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";
import {
  honeyItemLineText,
  honeySizeFromItemLine,
  shopeeRowIsReadyForExport,
  shopeeRowNeedsItemResolution,
} from "@/app/lib/honeyItemVariant";
import { enrichShopeePresets } from "@/app/lib/honeyPackagingStock";
import { useHoneyPackagingStock } from "@/app/hooks/useHoneyPackagingStock";
import { cn } from "@/app/components/ui/utils";

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
  confirmed?: boolean;
}

interface ItemResolverCarouselProps {
  rows: PdfToExcelRow[];
  resolutions: Record<number, ShopeeRowResolution>;
  onResolve: (page: number, resolution: ShopeeRowResolution) => void;
  onClearResolution: (page: number) => void;
  disabled?: boolean;
  showCompleted: boolean;
  onShowCompletedChange: (value: boolean) => void;
  focusPage?: number | null;
}

export function ItemResolverCarousel({
  rows,
  resolutions,
  onResolve,
  onClearResolution,
  disabled,
  showCompleted,
  onShowCompletedChange,
  focusPage,
}: ItemResolverCarouselProps) {
  const { snapshot: packagingStock } = useHoneyPackagingStock();
  const needsWork = useMemo(
    () => rows.filter(r => shopeeRowNeedsItemResolution(r, undefined)),
    [rows],
  );

  const pendingRows = useMemo(
    () => needsWork.filter(r => !shopeeRowIsReadyForExport(r, resolutions[r.page])),
    [needsWork, resolutions],
  );

  const completedRows = useMemo(
    () => needsWork.filter(r => shopeeRowIsReadyForExport(r, resolutions[r.page])),
    [needsWork, resolutions],
  );

  const visibleRows = showCompleted ? needsWork : pendingRows;
  const doneCount = completedRows.length;

  const [activePage, setActivePage] = useState<number | null>(null);
  const [draftItems, setDraftItems] = useState("");
  const [custom, setCustom] = useState("");
  const [confirmingPage, setConfirmingPage] = useState<number | null>(null);
  const reduceMotion = useReducedMotion();

  const currentPage = useMemo(() => {
    if (focusPage != null && needsWork.some(r => r.page === focusPage)) return focusPage;
    if (activePage != null && pendingRows.some(r => r.page === activePage)) return activePage;
    return pendingRows[0]?.page ?? null;
  }, [focusPage, activePage, needsWork, pendingRows]);

  useEffect(() => {
    if (focusPage != null) setActivePage(focusPage);
  }, [focusPage]);

  useEffect(() => {
    if (currentPage != null && confirmingPage !== currentPage) {
      setDraftItems("");
      setCustom("");
      setConfirmingPage(null);
    }
  }, [currentPage, confirmingPage]);

  const presetsForRow = (extracted: unknown) => {
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

  const startConfirm = (page: number, items: string) => {
    setDraftItems(items.trim());
    setConfirmingPage(page);
    setActivePage(page);
  };

  const commitConfirm = (page: number) => {
    if (!draftItems.trim()) return;
    onResolve(page, { items: draftItems.trim(), confirmed: true });
    setConfirmingPage(null);
    setDraftItems("");
    setCustom("");
    setActivePage(null);
  };

  const cancelConfirm = () => {
    setConfirmingPage(null);
    setDraftItems("");
  };

  const handleEdit = (page: number) => {
    onClearResolution(page);
    setActivePage(page);
    setConfirmingPage(null);
    setDraftItems("");
    setCustom("");
  };

  if (needsWork.length === 0) {
    return (
      <p className="text-sm text-green-700 flex items-center gap-2">
        <CheckCircle2 size={18} /> All rows have packer-ready items from the PDF.
      </p>
    );
  }

  const isConfirming = confirmingPage === currentPage && draftItems.trim() !== "";

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="text-xs text-muted-foreground">
          Pick honey variant (PDF often shows size only) —{" "}
          <OrderRemarksAnimatedCount value={pendingRows.length} className="font-semibold text-foreground" />{" "}
          remaining
          {doneCount > 0 ? (
            <>
              {" · "}
              <OrderRemarksAnimatedCount value={doneCount} className="font-semibold text-foreground" /> confirmed
            </>
          ) : null}
        </p>
        {doneCount > 0 && (
          <label className="flex items-center gap-2 text-xs cursor-pointer select-none">
            <input
              type="checkbox"
              checked={showCompleted}
              onChange={e => onShowCompletedChange(e.target.checked)}
              className="rounded border-border"
            />
            Show completed
          </label>
        )}
      </div>

      {pendingRows.length === 0 ? (
        <OrderRemarksAllDonePanel>
          <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-center text-sm text-green-800">
            <CheckCircle2 size={22} className="mx-auto mb-2 text-green-600" />
            All variant picks confirmed. Review below, or tick <strong>Show completed</strong> to edit any row.
          </div>
        </OrderRemarksAllDonePanel>
      ) : null}

      {visibleRows.length > 0 ? (
        <div className="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2 -mx-1 px-1 scrollbar-thin">
          <AnimatePresence mode="popLayout">
            {visibleRows.map(row => {
              const ready = shopeeRowIsReadyForExport(row, resolutions[row.page]);
              const active = row.page === currentPage;
              const res = resolutions[row.page];
              const pdfItems = honeyItemLineText(row.items);

              return (
                <OrderRemarksCarouselCard key={row.page} cardKey={row.page}>
                  <motion.div
                    layout
                    animate={
                      reduceMotion
                        ? undefined
                        : { borderColor: ready ? "rgb(134 239 172)" : "rgb(252 211 77)" }
                    }
                    transition={remarksSpring}
                    className={cn(
                      "flex-shrink-0 snap-start w-[min(100%,300px)] sm:w-[300px] bg-card rounded-xl border p-4",
                      ready ? "border-green-300" : "border-amber-300",
                      active && !ready && "ring-2 ring-primary/30",
                    )}
                  >
                    <p className="text-[10px] text-muted-foreground uppercase">Page {row.page}</p>
                    <p className="font-semibold text-sm truncate">{row.name || "—"}</p>
                    <p className="text-xs text-muted-foreground font-mono truncate">{row.tracking}</p>
                    <p className="text-xs text-muted-foreground">{row.courier}</p>
                    {pdfItems && (
                      <p className="text-xs mt-1 text-amber-800 bg-amber-50 rounded px-2 py-1">
                        From PDF: <span className="font-medium">{pdfItems}</span>
                      </p>
                    )}

                    {ready && res?.items && (
                      <OrderRemarksDoneBadge show>
                        <p className="text-[10px] mt-2 p-2 rounded-lg bg-green-50 text-green-800 border border-green-200 break-words">
                          {res.items}
                        </p>
                      </OrderRemarksDoneBadge>
                    )}

                    {ready && (
                      <button
                        type="button"
                        disabled={disabled}
                        onClick={() => handleEdit(row.page)}
                        className="mt-2 w-full text-xs font-medium py-1.5 rounded-lg border border-border flex items-center justify-center gap-1 hover:bg-muted/40"
                      >
                        <Pencil size={12} /> Edit item
                      </button>
                    )}

                    {!ready && active && (
                      <div className="mt-3 space-y-2">
                        {isConfirming ? (
                          <OrderRemarksStepReveal show>
                            <div className="rounded-lg border border-primary/30 bg-primary/5 p-3 space-y-2">
                              <p className="text-xs font-semibold text-foreground">Is this correct for packers?</p>
                              <p className="text-sm font-medium text-foreground break-words">{draftItems}</p>
                              <div className="flex gap-2">
                                <button
                                  type="button"
                                  disabled={disabled}
                                  onClick={() => commitConfirm(row.page)}
                                  className="flex-1 text-xs font-semibold py-2 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
                                >
                                  Yes, correct
                                </button>
                                <button
                                  type="button"
                                  disabled={disabled}
                                  onClick={cancelConfirm}
                                  className="flex-1 text-xs font-semibold py-2 rounded-lg border border-border bg-card disabled:opacity-50"
                                >
                                  Change
                                </button>
                              </div>
                            </div>
                          </OrderRemarksStepReveal>
                        ) : (
                          <>
                            <VariantCardStrip
                              presets={enrichShopeePresets(presetsForRow(row.items), packagingStock)}
                              selected={undefined}
                              onSelect={v => startConfirm(row.page, v)}
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
                              onClick={() => startConfirm(row.page, custom.trim())}
                              className="w-full text-xs font-semibold py-2 rounded-lg text-white bg-primary disabled:opacity-50"
                            >
                              Review custom line
                            </button>
                          </>
                        )}
                      </div>
                    )}

                    {!ready && !active && (
                      <button
                        type="button"
                        disabled={disabled}
                        onClick={() => setActivePage(row.page)}
                        className="mt-2 w-full text-xs font-medium py-1.5 rounded-lg border border-amber-300 text-amber-900 bg-amber-50"
                      >
                        Resolve this row
                      </button>
                    )}
                  </motion.div>
                </OrderRemarksCarouselCard>
              );
            })}
          </AnimatePresence>
        </div>
      ) : null}

      {visibleRows.length > 1 && pendingRows.length > 0 && (
        <p className="text-[10px] text-muted-foreground text-center">Scroll sideways for more rows</p>
      )}
    </div>
  );
}
