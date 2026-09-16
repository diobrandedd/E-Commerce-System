import { useCallback, useEffect, useState } from "react";
import { useSearchParams, Link } from "react-router";
import { BookOpen, Loader2, Printer, Tag, AlertTriangle } from "lucide-react";
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
import { AppErrorBanner, appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, ApiError } from "@/app/lib/api";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { canExecutiveDirectEdit, canGenerateHoneyBottleLabels, canViewSupplierProfile } from "@/app/lib/honeyPermissions";

interface PrRow {
  id: number;
  supplier_code: string;
  arrival_date: string;
  stock_out_date?: string;
  variant: string;
  current_qty: string;
  initial_qty: string;
  is_active?: number | boolean;
}

type ModalKind = "request" | "admin" | "guide" | null;

export function HoneyProductionPage() {
  const { user, can } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const canRequest = can("honey.bottle.request");
  const canGenerate = can("honey.bottle.generate") || canGenerateHoneyBottleLabels(user);
  const isCsrOnly = user?.role === "csr_tl" || user?.role === "csr_staff";
  const canLeakDamage = can("inventory.leak_damage.view");
  const showSupplierProfile = canViewSupplierProfile(user);
  const canExecEdit = canExecutiveDirectEdit(user);
  const [rows, setRows] = useState<PrRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [modal, setModal] = useState<ModalKind>(null);
  const [pendingLabels, setPendingLabels] = useState(0);
  const [editRow, setEditRow] = useState<PrRow | null>(null);

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
      const data = await apiGet<{ rows: PrRow[] }>("honey.php", "production_list");
      setRows(data.rows);
      setPage(1);
      await refreshPending();
      setLoadError(null);
    } catch (error) {
      setLoadError(error instanceof ApiError ? error.message : "Could not load production lots.");
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

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);

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
          : "Production — request bottle labels, scan stock-in, and review lot history"
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
        {!isCsrOnly && (
          <HoneyProductionScanStockInPanel lots={rows} onStocked={() => void refresh()} />
        )}
        {loadError && (
          <div>
            <AppErrorBanner message={loadError} />
            <Button type="button" variant="outline" className="min-h-11" onClick={() => void refresh()}>
              Retry loading
            </Button>
          </div>
        )}
        <div className="sm:hidden space-y-3" data-testid="honey-production-mobile-cards">
          {loading ? (
            <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-muted-foreground">
              <Loader2 className="inline animate-spin" size={18} />
            </div>
          ) : pagedRows.length === 0 ? (
            <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground">
              No production lots yet.
            </div>
          ) : (
            pagedRows.map(r => {
              const inactive = r.is_active === 0 || r.is_active === false;
              return (
              <article key={r.id} className={`rounded-xl border bg-card p-4 ${inactive ? "border-border/60 opacity-75" : "border-border"}`}>
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Supplier</p>
                    {showSupplierProfile ? (
                      <SupplierCodeButton code={r.supplier_code} className="min-h-11" />
                    ) : (
                      <p className="font-mono text-sm font-semibold">{r.supplier_code}</p>
                    )}
                    <p className="mt-1 text-xs capitalize text-muted-foreground">{r.variant}</p>
                  </div>
                  <div className="flex flex-col items-end gap-1">
                    {inactive ? (
                      <span className="inline-flex text-xs px-2 py-0.5 rounded-full font-medium bg-secondary text-muted-foreground">
                        Inactive
                      </span>
                    ) : (
                      <HoneyStockStatusBadge current={r.current_qty} initial={r.initial_qty} />
                    )}
                  </div>
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2 text-sm">
                  <div className="rounded-lg bg-secondary/30 px-3 py-2">
                    <p className="text-[11px] text-muted-foreground">From warehouse</p>
                    <p className="mt-0.5 font-medium">{r.arrival_date}</p>
                  </div>
                  <div className="rounded-lg bg-secondary/30 px-3 py-2">
                    <p className="text-[11px] text-muted-foreground">Stocked out</p>
                    <p className="mt-0.5 font-medium">{r.stock_out_date ?? "—"}</p>
                  </div>
                </div>
                <div className="mt-2 rounded-lg border border-border px-3 py-2">
                  <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Gallons on hand</p>
                  <div className="mt-1">
                    <HoneyLotGallonProgress
                      current={r.current_qty}
                      initial={r.initial_qty}
                      testId={`honey-production-gal-progress-${r.id}`}
                      className="max-w-none"
                    />
                  </div>
                </div>
                {canExecEdit ? (
                  <Button
                    type="button"
                    variant="outline"
                    className="mt-3 min-h-11 w-full"
                    onClick={() => setEditRow(r)}
                  >
                    Edit lot
                  </Button>
                ) : null}
              </article>
              );
            })
          )}
        </div>
        <div className="hidden sm:block bg-card rounded-xl border overflow-x-auto" data-testid="honey-production-desktop-table">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-secondary/40">
                {["Supplier code", "From WH", "Stock out", "Variant", "Gallon/s", "Status", ...(canExecEdit ? ["Actions"] : [])].map(h => (
                  <th key={h} className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={canExecEdit ? 7 : 6} className="py-8 text-center">
                    <Loader2 className="inline animate-spin" size={14} />
                  </td>
                </tr>
              )}
              {!loading && pagedRows.length === 0 && (
                <tr>
                  <td colSpan={canExecEdit ? 7 : 6} className="px-4 py-8 text-center text-sm text-muted-foreground">
                    No production lots yet.
                  </td>
                </tr>
              )}
              {pagedRows.map(r => {
                const inactive = r.is_active === 0 || r.is_active === false;
                return (
                <tr key={r.id} className={`border-b ${inactive ? "bg-secondary/20" : ""}`}>
                  <td className="px-4 py-3">
                    {showSupplierProfile ? (
                      <SupplierCodeButton code={r.supplier_code} />
                    ) : (
                      <span className="font-mono">{r.supplier_code}</span>
                    )}
                  </td>
                  <td className="px-4 py-3">{r.arrival_date}</td>
                  <td className="px-4 py-3">{r.stock_out_date ?? "—"}</td>
                  <td className="px-4 py-3 capitalize">{r.variant}</td>
                  <td className="px-4 py-3">
                    <HoneyLotGallonProgress
                      current={r.current_qty}
                      initial={r.initial_qty}
                      testId={`honey-production-gal-progress-${r.id}`}
                    />
                  </td>
                  <td className="px-4 py-3">
                    {inactive ? (
                      <span className="inline-flex text-xs px-2 py-0.5 rounded-full font-medium bg-secondary text-muted-foreground">
                        Inactive
                      </span>
                    ) : (
                      <HoneyStockStatusBadge current={r.current_qty} initial={r.initial_qty} />
                    )}
                  </td>
                  {canExecEdit ? (
                    <td className="px-4 py-3">
                      <Button type="button" size="sm" variant="ghost" onClick={() => setEditRow(r)}>
                        Edit
                      </Button>
                    </td>
                  ) : null}
                </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        {!loading && totalRows > 0 && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 mt-3">
            <p className="text-xs text-muted-foreground">
              Showing {start + 1}-{Math.min(start + perPage, totalRows)} of {totalRows} rows
            </p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={safePage <= 1}
                className="min-h-11 text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Previous
              </button>
              <span className="text-xs text-muted-foreground">
                Page {safePage} of {totalPages}
              </span>
              <button
                type="button"
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
                className="min-h-11 text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>
        )}
          </div>
        </HoneyStageContent>
      </div>

      <AppModal open={modal === "request"} onClose={closeModal} panelClassName="max-w-3xl w-full" labelledBy="honey-production-request-title">
        <AppModalHeader title="Request barcodes" titleId="honey-production-request-title" onClose={closeModal} />
        <AppModalBody className="max-h-[75vh] overflow-y-auto">
          <HoneyBottleRequestsPanel canAdmin={canGenerate} />
        </AppModalBody>
      </AppModal>

      <AppModal open={modal === "admin"} onClose={closeModal} panelClassName="max-w-3xl w-full" labelledBy="honey-production-admin-title">
        <AppModalHeader title="Generate / print requests" titleId="honey-production-admin-title" onClose={closeModal} />
        <AppModalBody className="max-h-[75vh] overflow-y-auto">
          {canGenerate ? <HoneyBottleAdminPanel /> : null}
        </AppModalBody>
      </AppModal>

      <AppModal open={modal === "guide"} onClose={closeModal} labelledBy="honey-production-guide-title">
        <AppModalHeader title="Honey bottle barcodes" titleId="honey-production-guide-title" onClose={closeModal} />
        <AppModalBody className="space-y-3 text-sm text-muted-foreground">
          <p>Honey staff request labels. Executives and CSR generate unique barcodes and print an A4 sheet (68 labels per page, 4×17 — barcode plus the code text underneath for manual entry, cut on dashed guides). Print at 100% scale so the bars stay camera-scannable.</p>
          <p>
            Stock out from Warehouse part by part if needed — same supplier code + arrival + variant shares{" "}
            <strong className="text-foreground">one</strong> Production lot (later transfers refill; Light / Amber / Dark stay separate). Then
            Production → scan stock-in: pick an active lot with gallons left, scan SKU/unique barcodes, and packaging gains bottles while
            production gallons drop. Executives can Edit a lot to adjust gallons or deactivate it.
          </p>
          <p>Courier packing uses Deliveries → Pack scan. Waybill + photo ships without deducting stock.</p>
        </AppModalBody>
      </AppModal>
      {editRow && (
        <ExecutiveRowEditModal
          stage="production"
          row={editRow}
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
