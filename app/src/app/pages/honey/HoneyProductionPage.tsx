import { useCallback, useEffect, useState } from "react";
import { useSearchParams, Link } from "react-router";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { BookOpen, ChevronDown, Loader2, Printer, Tag, AlertTriangle, Archive } from "lucide-react";
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
import {
  AppErrorBanner,
  AppSuccessNotice,
  appHeaderActionClass,
  appHoverCard,
} from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
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
  depleted_at?: string | null;
  voided_at?: string | null;
  voided_by?: number | null;
}

interface VariantGroup {
  variant: string;
  label: string;
  current_qty: number;
  initial_qty: number;
  lot_count: number;
  voided_count?: number;
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
  const [voidedVariant, setVoidedVariant] = useState<string | null>(null);
  const [voidedLots, setVoidedLots] = useState<PrLot[]>([]);
  const [voidedLoading, setVoidedLoading] = useState(false);
  const [voidedError, setVoidedError] = useState<string | null>(null);
  const [voidBusyId, setVoidBusyId] = useState<number | null>(null);
  const [actionMsg, setActionMsg] = useState<string | null>(null);
  const [actionErr, setActionErr] = useState<string | null>(null);

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

  const openVoided = async (variant: string) => {
    setVoidedVariant(variant);
    setVoidedLots([]);
    setVoidedError(null);
    setVoidedLoading(true);
    try {
      const data = await apiGet<{ variant: string; lots: PrLot[] }>("honey.php", "production_voided_list", {
        variant,
      });
      setVoidedLots(data.lots);
    } catch (e) {
      setVoidedError(e instanceof ApiError ? e.message : "Could not load voided lots.");
    } finally {
      setVoidedLoading(false);
    }
  };

  const voidLot = async (lot: PrLot) => {
    if (!canExecEdit) return;
    const qty = Number(lot.current_qty);
    if (Number.isFinite(qty) && qty > 0) {
      setActionErr("Cannot void a lot that still has gallons.");
      return;
    }
    if (!window.confirm(`Void ${lot.supplier_code}? It will move to the Voided list for ${variantLabel(lot.variant)}.`)) {
      return;
    }
    setVoidBusyId(lot.id);
    setActionErr(null);
    setActionMsg(null);
    try {
      await apiPost("honey.php", "production_void", { production_row_id: lot.id });
      setActionMsg(`Voided ${lot.supplier_code}.`);
      await refresh();
    } catch (e) {
      setActionErr(e instanceof ApiError ? e.message : "Could not void lot.");
    } finally {
      setVoidBusyId(null);
    }
  };

  const unvoidLot = async (lot: PrLot) => {
    if (!canExecEdit) return;
    setVoidBusyId(lot.id);
    setActionErr(null);
    setActionMsg(null);
    try {
      await apiPost("honey.php", "production_unvoid", { production_row_id: lot.id });
      setActionMsg(`Restored ${lot.supplier_code} to the main list.`);
      if (voidedVariant) {
        await openVoided(voidedVariant);
      }
      await refresh();
    } catch (e) {
      setActionErr(e instanceof ApiError ? e.message : "Could not restore lot.");
    } finally {
      setVoidBusyId(null);
    }
  };

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
            {actionErr && <AppErrorBanner message={actionErr} />}
            <AppSuccessNotice message={actionMsg} />

            <div data-testid="honey-production-variant-rows">
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
                    const voidedCount = v.voided_count ?? 0;
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
                        {voidedCount > 0 && (
                          <div className="px-4 pb-2.5 -mt-0.5 flex items-center pl-11">
                            <button
                              type="button"
                              className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors duration-200 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C8900A]/35 focus-visible:ring-offset-1 rounded-sm"
                              onClick={() => void openVoided(v.variant)}
                            >
                              <Archive size={12} strokeWidth={2} className="opacity-80" aria-hidden />
                              View {voidedCount} voided
                            </button>
                          </div>
                        )}
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
                                <p className="px-4 py-3 text-sm text-muted-foreground">
                                  No lots on the main list
                                  {voidedCount > 0 ? " — open voided above." : " yet."}
                                </p>
                              ) : (
                                <ul className="divide-y divide-border">
                                  {v.lots.map(lot => {
                                    const inactive = lot.is_active === 0 || lot.is_active === false;
                                    const empty = !(Number(lot.current_qty) > 0);
                                    return (
                                      <li
                                        key={lot.id}
                                        className={cn(
                                          "px-4 py-3 flex flex-wrap items-center gap-3 text-sm",
                                          inactive && "opacity-70",
                                        )}
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
                                          <div className="flex flex-wrap gap-2">
                                            {empty && (
                                              <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                disabled={voidBusyId === lot.id}
                                                onClick={() => void voidLot(lot)}
                                              >
                                                {voidBusyId === lot.id ? (
                                                  <Loader2 size={14} className="animate-spin" />
                                                ) : (
                                                  "Void"
                                                )}
                                              </Button>
                                            )}
                                            <Button type="button" size="sm" variant="outline" onClick={() => setEditRow(lot)}>
                                              Edit
                                            </Button>
                                          </div>
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
            <p>
              Empty lots auto-deactivate after one hour, then auto-void after two days. Executives can Void early; Voided
              opens the archive per variant. Warehouse refill restores the lot.
            </p>
          </AppModalBody>
        </AppModal>
      )}
      {voidedVariant && (
        <AppModal
          open
          onClose={() => setVoidedVariant(null)}
          panelClassName="max-w-2xl w-full"
          labelledBy="pr-voided-title"
        >
          <AppModalHeader
            title={`Voided · ${variantLabel(voidedVariant)}`}
            titleId="pr-voided-title"
            onClose={() => setVoidedVariant(null)}
          />
          <AppModalBody className="space-y-3">
            <p className="text-xs text-muted-foreground">
              Hidden from the main list. Unvoid restores a lot so it shows under the variant again.
            </p>
            {voidedError && <AppErrorBanner message={voidedError} />}
            {voidedLoading ? (
              <p className="text-sm text-muted-foreground flex items-center gap-2">
                <Loader2 size={16} className="animate-spin" /> Loading…
              </p>
            ) : voidedLots.length === 0 ? (
              <p className="text-sm text-muted-foreground">No voided lots for this variant.</p>
            ) : (
              <ul className="divide-y divide-border rounded-xl border border-border overflow-hidden">
                {voidedLots.map(lot => (
                  <li key={lot.id} className={cn("px-4 py-3 flex flex-wrap items-center gap-3 text-sm", appHoverCard)}>
                    <div className="min-w-[7rem] font-mono font-medium">{lot.supplier_code}</div>
                    <div className="text-xs text-muted-foreground flex-1">
                      WH {lot.arrival_date}
                      {lot.stock_out_date ? ` · out ${lot.stock_out_date}` : ""}
                      {lot.voided_at ? ` · voided ${String(lot.voided_at).slice(0, 10)}` : ""}
                    </div>
                    <div className="tabular-nums text-muted-foreground">{Number(lot.current_qty)} gal</div>
                    {canExecEdit && (
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        disabled={voidBusyId === lot.id}
                        onClick={() => void unvoidLot(lot)}
                      >
                        {voidBusyId === lot.id ? <Loader2 size={14} className="animate-spin" /> : "Unvoid"}
                      </Button>
                    )}
                  </li>
                ))}
              </ul>
            )}
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
