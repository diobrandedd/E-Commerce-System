import { useCallback, useEffect, useState } from "react";
import { useSearchParams, Link } from "react-router";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { BookOpen, ChevronDown, Loader2, Printer, Tag, AlertTriangle } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { HoneyStockStatusBadge } from "@/app/components/honey/HoneyStockStatusBadge";
import {
  HoneyBottleAdminPanel,
  HoneyBottleRequestsPanel,
  HoneyProductionScanStockInPanel,
} from "@/app/components/honey/HoneyBottleBarcodePanels";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { HoneyStageContent, HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { HoneyLotGallonProgress } from "@/app/components/honey/HoneyLotGallonProgress";
import { PageLayout } from "@/app/components/PageLayout";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { Button } from "@/app/components/ui/button";
import { AppErrorBanner, appHeaderActionClass, appHoverCard } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, ApiError } from "@/app/lib/api";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { canExecutiveDirectEdit, canGenerateHoneyBottleLabels, canViewSupplierProfile } from "@/app/lib/honeyPermissions";
import { variantLabel } from "@/app/lib/posHoney";
import { cn } from "@/app/components/ui/utils";

interface PrLot {
  id: number;
  supplier_code: string;
  arrival_date: string;
  stock_out_date?: string;
  variant: string;
  current_qty: string | number;
  initial_qty: string | number;
  is_active?: number | boolean;
}

interface VariantGroup {
  variant: string;
  label: string;
  current_qty: number;
  initial_qty: number;
  lot_count: number;
  lots: PrLot[];
}

type ModalKind = "request" | "admin" | "guide" | null;

export function HoneyProductionPage() {
  const { user, can } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const reduceMotion = useReducedMotion();
  const canRequest = can("honey.bottle.request");
  const canGenerate = can("honey.bottle.generate") || canGenerateHoneyBottleLabels(user);
  const isCsrOnly = user?.role === "csr_tl" || user?.role === "csr_staff";
  const canLeakDamage = can("inventory.leak_damage.view");
  const showSupplierProfile = canViewSupplierProfile(user);
  const canExecEdit = canExecutiveDirectEdit(user);
  const [variants, setVariants] = useState<VariantGroup[]>([]);
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [modal, setModal] = useState<ModalKind>(null);
  const [pendingLabels, setPendingLabels] = useState(0);
  const [editRow, setEditRow] = useState<PrLot | null>(null);

  const refreshPending = useCallback(async () => {
    if (!canGenerate) {
      setPendingLabels(0);
      return;
    }
    try {
      const res = await bottleBarcodesApi.pendingCount();
      setPendingLabels(res.count);
    } catch {
      setPendingLabels(0);
    }
  }, [canGenerate]);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ variants: VariantGroup[] }>("honey.php", "production_variant_list");
      setVariants(data.variants);
      await refreshPending();
      setLoadError(null);
    } catch (error) {
      setLoadError(error instanceof ApiError ? error.message : "Could not load production.");
    } finally {
      setLoading(false);
    }
  }, [refreshPending]);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  useEffect(() => {
    if (!canGenerate) return;
    if (searchParams.get("labels") === "1") {
      setModal("admin");
      const next = new URLSearchParams(searchParams);
      next.delete("labels");
      setSearchParams(next, { replace: true });
    }
  }, [canGenerate, searchParams, setSearchParams]);

  const closeModal = () => setModal(null);

  const barcodeButtons = (
    <div className="flex flex-wrap items-center gap-2">
      {canRequest && (
        <Button type="button" size="sm" className="min-h-11" variant="outline" onClick={() => setModal("request")}>
          <Tag size={14} /> Request barcodes
        </Button>
      )}
      {canGenerate && (
        <Button
          type="button"
          size="sm"
          className="relative min-h-11 overflow-visible"
          onClick={() => setModal("admin")}
        >
          <Printer size={14} />
          Generate / print requests
          {pendingLabels > 0 && (
            <span className="absolute -top-1.5 -right-1.5 min-w-[1.25rem] h-5 px-1 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center leading-none">
              {pendingLabels > 99 ? "99+" : pendingLabels}
            </span>
          )}
        </Button>
      )}
    </div>
  );

  return (
    <PageLayout
      title="Raw Honey"
      subtitle={
        isCsrOnly
          ? "Generate and print bottle barcode labels (A4 · 68 per page)"
          : "Production — scan stock-in and review gallons by variant"
      }
      actions={
        <button type="button" onClick={() => setModal("guide")} className={appHeaderActionClass}>
          <BookOpen size={13} /> Guide
        </button>
      }
    >
      <div className="space-y-4">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          {!isCsrOnly && <HoneyStageTabs />}
          {barcodeButtons}
        </div>
        <HoneyStageContent>
          <div className="space-y-4">
            {canLeakDamage && (
              <Link
                to="/inventory/leak-damage"
                className="inline-flex min-h-11 items-center gap-2 py-2 px-3 text-sm font-semibold border border-red-300 text-red-700 rounded-md hover:bg-red-50 transition-colors w-fit"
              >
                <AlertTriangle size={16} /> Leak / damage scan
              </Link>
            )}
            {!isCsrOnly && <HoneyProductionScanStockInPanel onStocked={() => void refresh()} />}
            {loadError && (
              <div>
                <AppErrorBanner message={loadError} />
                <Button type="button" variant="outline" className="min-h-11" onClick={() => void refresh()}>
                  Retry loading
                </Button>
              </div>
            )}

            <div>
              <h2 className="text-sm font-semibold mb-1">Production gallons</h2>
              <p className="text-xs text-muted-foreground mb-3">
                One row per variant. Expand to see supplier codes and gallons from warehouse stock-out.
              </p>
              {loading ? (
                <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-muted-foreground">
                  <Loader2 className="inline animate-spin" size={18} />
                </div>
              ) : (
                <div className="space-y-3">
                  {variants.map(v => {
                    const open = !!expanded[v.variant];
                    return (
                      <div key={v.variant} className={cn("rounded-xl border border-border bg-card overflow-hidden", appHoverCard)}>
                        <button
                          type="button"
                          className="w-full flex items-center gap-3 px-4 py-3 text-left"
                          onClick={() => setExpanded(prev => ({ ...prev, [v.variant]: !prev[v.variant] }))}
                        >
                          <ChevronDown
                            size={18}
                            className={cn("shrink-0 text-muted-foreground transition-transform", open && "rotate-180")}
                          />
                          <div className="flex-1 min-w-0">
                            <p className="font-semibold text-foreground">{variantLabel(v.variant)}</p>
                            <p className="text-xs text-muted-foreground">
                              {v.lot_count} supplier lot{v.lot_count === 1 ? "" : "s"}
                            </p>
                          </div>
                          <div className="text-right shrink-0">
                            <HoneyStockStatusBadge current={v.current_qty} initial={v.initial_qty} />
                            <div className="mt-1 min-w-[9rem]">
                              <HoneyLotGallonProgress current={v.current_qty} initial={v.initial_qty} className="max-w-none" />
                            </div>
                          </div>
                        </button>
                        <AnimatePresence initial={false}>
                          {open && (
                            <motion.div
                              initial={reduceMotion ? false : { height: 0, opacity: 0 }}
                              animate={{ height: "auto", opacity: 1 }}
                              exit={reduceMotion ? undefined : { height: 0, opacity: 0 }}
                              transition={{ duration: 0.22 }}
                              className="overflow-hidden border-t border-border"
                            >
                              {v.lots.length === 0 ? (
                                <p className="px-4 py-3 text-sm text-muted-foreground">No lots for this variant yet.</p>
                              ) : (
                                <ul className="divide-y divide-border">
                                  {v.lots.map(lot => {
                                    const inactive = lot.is_active === 0 || lot.is_active === false;
                                    return (
                                      <li
                                        key={lot.id}
                                        className={cn("px-4 py-3 flex flex-wrap items-center gap-3 text-sm", inactive && "opacity-70")}
                                      >
                                        <div className="min-w-[7rem]">
                                          {showSupplierProfile ? (
                                            <SupplierCodeButton code={lot.supplier_code} />
                                          ) : (
                                            <span className="font-mono font-medium">{lot.supplier_code}</span>
                                          )}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                          WH {lot.arrival_date}
                                          {lot.stock_out_date ? ` · out ${lot.stock_out_date}` : ""}
                                        </div>
                                        <div className="flex-1 min-w-[8rem]">
                                          <HoneyLotGallonProgress
                                            current={lot.current_qty}
                                            initial={lot.initial_qty}
                                            className="max-w-none"
                                          />
                                        </div>
                                        {canExecEdit && (
                                          <Button type="button" size="sm" variant="outline" onClick={() => setEditRow(lot)}>
                                            Edit
                                          </Button>
                                        )}
                                      </li>
                                    );
                                  })}
                                </ul>
                              )}
                            </motion.div>
                          )}
                        </AnimatePresence>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        </HoneyStageContent>
      </div>

      {modal === "request" && (
        <AppModal open onClose={closeModal} panelClassName="max-w-3xl w-full" labelledBy="pr-req-title">
          <AppModalHeader title="Request bottle barcodes" titleId="pr-req-title" onClose={closeModal} />
          <AppModalBody>
            <HoneyBottleRequestsPanel />
          </AppModalBody>
        </AppModal>
      )}
      {modal === "admin" && (
        <AppModal open onClose={closeModal} panelClassName="max-w-4xl w-full" labelledBy="pr-admin-title">
          <AppModalHeader title="Generate / print requests" titleId="pr-admin-title" onClose={closeModal} />
          <AppModalBody>
            <HoneyBottleAdminPanel />
          </AppModalBody>
        </AppModal>
      )}
      {modal === "guide" && (
        <AppModal open onClose={closeModal} panelClassName="max-w-lg w-full" labelledBy="pr-guide-title">
          <AppModalHeader title="Production guide" titleId="pr-guide-title" onClose={closeModal} />
          <AppModalBody className="space-y-3 text-sm text-muted-foreground">
            <p>Stock out gallons from Warehouse first. Scan bottles or SKUs here to credit finished packaging pools.</p>
            <p>Gallons deduct FIFO by variant across supplier lots — no lot picker. Expand a variant to see suppliers.</p>
          </AppModalBody>
        </AppModal>
      )}
      {editRow && (
        <ExecutiveRowEditModal
          stage="production"
          row={{
            id: editRow.id,
            supplier_code: editRow.supplier_code,
            variant: editRow.variant,
            current_qty: String(editRow.current_qty),
            initial_qty: String(editRow.initial_qty),
            is_active: editRow.is_active,
          }}
          onClose={() => setEditRow(null)}
          onSaved={() => {
            setEditRow(null);
            void refresh();
          }}
        />
      )}
    </PageLayout>
  );
}
