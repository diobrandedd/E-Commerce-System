import { useCallback, useEffect, useState } from "react";
import { useSearchParams } from "react-router";
import { BookOpen, Loader2, Printer, ScanLine, Tag } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { HoneyStockStatusBadge } from "@/app/components/honey/HoneyStockStatusBadge";
import {
  HoneyBottleAdminPanel,
  HoneyBottleRequestsPanel,
  HoneyBottleScanInPanel,
} from "@/app/components/honey/HoneyBottleBarcodePanels";
import { HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { PageLayout } from "@/app/components/PageLayout";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { Button } from "@/app/components/ui/button";
import { appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet } from "@/app/lib/api";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { canGenerateHoneyBottleLabels, canViewSupplierProfile } from "@/app/lib/honeyPermissions";

interface PrRow {
  id: number;
  supplier_code: string;
  arrival_date: string;
  variant: string;
  current_qty: string;
  initial_qty: string;
}

type ModalKind = "request" | "scan" | "admin" | "guide" | null;

export function HoneyProductionPage() {
  const { user } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const canGenerate = canGenerateHoneyBottleLabels(user?.role, user?.dept);
  const isCsrOnly = user?.role === "csr_tl" || user?.role === "csr_staff";
  const showHoneyOps = !isCsrOnly;
  const showSupplierProfile = canViewSupplierProfile(user?.role);
  const [rows, setRows] = useState<PrRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState<ModalKind>(null);
  const [pendingLabels, setPendingLabels] = useState(0);

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
    } catch {
      /* ignore */
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

  return (
    <PageLayout
      title="Raw Honey"
      subtitle={
        isCsrOnly
          ? "Generate and print bottle barcode labels (A4 · 68 per page)"
          : "Production — request bottle labels, scan stock-in, and review lot history"
      }
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <button type="button" onClick={() => setModal("guide")} className={appHeaderActionClass}>
            <BookOpen size={13} /> Guide
          </button>
          {showHoneyOps && (
            <>
              <Button type="button" size="sm" variant="outline" onClick={() => setModal("request")}>
                <Tag size={14} /> Request<span className="hidden sm:inline">&nbsp;barcodes</span>
              </Button>
              <Button type="button" size="sm" variant="outline" onClick={() => setModal("scan")}>
                <ScanLine size={14} /> Scan<span className="hidden sm:inline">&nbsp;to inventory</span>
              </Button>
            </>
          )}
          {canGenerate && (
            <Button
              type="button"
              size="sm"
              className="relative overflow-visible"
              onClick={() => setModal("admin")}
            >
              <Printer size={14} />
              <span className="sm:hidden">Print</span>
              <span className="hidden sm:inline">Generate / print requests</span>
              {pendingLabels > 0 && (
                <span className="absolute -top-1.5 -right-1.5 min-w-[1.25rem] h-5 px-1 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center leading-none">
                  {pendingLabels > 99 ? "99+" : pendingLabels}
                </span>
              )}
            </Button>
          )}
        </div>
      }
    >
      <div className="space-y-4">
        {!isCsrOnly && <HoneyStageTabs />}
        <div className="bg-card rounded-xl border overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-secondary/40">
                {["Supplier code", "From WH", "Variant", "Gallon/s", "Status"].map(h => (
                  <th key={h} className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={5} className="py-8 text-center">
                    <Loader2 className="inline animate-spin" size={14} />
                  </td>
                </tr>
              )}
              {!loading && pagedRows.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-sm text-muted-foreground">
                    No production lots yet.
                  </td>
                </tr>
              )}
              {pagedRows.map(r => (
                <tr key={r.id} className="border-b">
                  <td className="px-4 py-3">
                    {showSupplierProfile ? (
                      <SupplierCodeButton code={r.supplier_code} />
                    ) : (
                      <span className="font-mono">{r.supplier_code}</span>
                    )}
                  </td>
                  <td className="px-4 py-3">{r.arrival_date}</td>
                  <td className="px-4 py-3 capitalize">{r.variant}</td>
                  <td className="px-4 py-3">
                    <QtyCounter current={r.current_qty} initial={r.initial_qty} />
                  </td>
                  <td className="px-4 py-3">
                    <HoneyStockStatusBadge current={r.current_qty} initial={r.initial_qty} />
                  </td>
                </tr>
              ))}
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
                className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
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
                className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>

      <AppModal open={modal === "request"} onClose={closeModal} panelClassName="max-w-3xl w-full">
        <AppModalHeader title="Request barcodes" onClose={closeModal} />
        <AppModalBody className="max-h-[75vh] overflow-y-auto">
          <HoneyBottleRequestsPanel canAdmin={canGenerate} />
        </AppModalBody>
      </AppModal>

      <AppModal open={modal === "scan"} onClose={closeModal} panelClassName="max-w-lg w-full">
        <AppModalHeader title="Scan to inventory" onClose={closeModal} />
        <AppModalBody>
          <HoneyBottleScanInPanel />
        </AppModalBody>
      </AppModal>

      <AppModal open={modal === "admin"} onClose={closeModal} panelClassName="max-w-3xl w-full">
        <AppModalHeader title="Generate / print requests" onClose={closeModal} />
        <AppModalBody className="max-h-[75vh] overflow-y-auto">
          {canGenerate ? <HoneyBottleAdminPanel /> : null}
        </AppModalBody>
      </AppModal>

      <AppModal open={modal === "guide"} onClose={closeModal}>
        <AppModalHeader title="Honey bottle barcodes" onClose={closeModal} />
        <AppModalBody className="space-y-3 text-sm text-muted-foreground">
          <p>Honey staff request labels. Executives and CSR generate unique barcodes and print an A4 sheet (68 labels per page, 4×17 — barcode plus the code text underneath for manual entry, cut on dashed guides). Print at 100% scale so the bars stay camera-scannable.</p>
          <p>Sticker bottles, then Scan to inventory. Packaging totals only move when a bottle is scanned.</p>
          <p>Courier packing uses Deliveries → Pack scan. Waybill + photo ships without deducting stock.</p>
        </AppModalBody>
      </AppModal>
    </PageLayout>
  );
}
