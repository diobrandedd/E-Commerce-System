import { Link } from "react-router";
import { useCallback, useEffect, useMemo, useState, type Dispatch, type SetStateAction } from "react";
import {
  AlertCircle,
  ArrowLeft,
  Loader2,
  Plus,
  RefreshCcw,
  Save,
  Trash2,
  PackageCheck,
  RotateCcw,
} from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";
import { ApiError } from "@/app/lib/api";
import { peso } from "@/app/lib/posDept";
import {
  bulkOrdersConfirm,
  bulkOrdersCreate,
  bulkOrdersDelete,
  bulkOrdersGet,
  bulkOrdersList,
  bulkOrdersPreviewCost,
  bulkOrdersSuggestAllocations,
  bulkOrdersUpdate,
  bulkOrdersVoid,
  bulkOrdersWarehouseRows,
  type BulkLineInput,
  type BulkNegotiationMode,
  type BulkOrderDetail,
  type BulkOrderSummary,
  type BulkPreviewLine,
  type BulkWarehouseRow,
  type HoneyVariant,
} from "@/app/lib/bulkOrdersApi";

const VARIANTS: HoneyVariant[] = ["light", "amber", "dark"];
const MARGIN_CHIPS = [10, 15, 20, 25];

type View = "list" | "detail";

function emptyLine(): BulkLineInput {
  return {
    variant: "amber",
    qty_gallons: 0,
    negotiation_mode: "unit_price",
    unit_sale_price: 0,
    allocations: [],
  };
}

function variantLabel(v: string) {
  return v.charAt(0).toUpperCase() + v.slice(1);
}

type WarehousePickCost = { unitCostPerGal: number | null; supplierCode: string };

function clientCogsFromPicks(
  line: BulkLineInput,
  costs: Record<number, WarehousePickCost>,
): { cogs: number; cogsPerGal: number } | null {
  if (line.qty_gallons <= 0 || line.allocations.length === 0) return null;
  let allocSum = 0;
  let cogs = 0;
  for (const a of line.allocations) {
    const row = costs[a.warehouse_row_id];
    if (!row || row.unitCostPerGal == null) return null;
    allocSum += a.qty_gallons;
    cogs += a.qty_gallons * row.unitCostPerGal;
  }
  if (Math.abs(allocSum - line.qty_gallons) > 0.02) return null;
  return { cogs: roundMoney(cogs), cogsPerGal: roundMoney(cogs / line.qty_gallons) };
}

function missingCostSupplierCodes(
  lines: BulkLineInput[],
  costs: Record<number, WarehousePickCost>,
): string[] {
  const out = new Set<string>();
  for (const line of lines) {
    for (const a of line.allocations) {
      const row = costs[a.warehouse_row_id];
      if (!row || row.unitCostPerGal == null) {
        out.add(row?.supplierCode ?? `#${a.warehouse_row_id}`);
      }
    }
  }
  return [...out];
}

/** Client-side quote math — mirrors server preview for all negotiation modes. */
function computeLineQuote(
  line: BulkLineInput,
  previewLine?: BulkPreviewLine,
  warehouseCosts?: Record<number, WarehousePickCost>,
) {
  const qty = line.qty_gallons;
  if (qty <= 0) return null;

  let cogs = previewLine?.line_cogs ?? null;
  let cogsPerGal = previewLine?.cogs_per_gallon ?? null;
  if (cogs == null && warehouseCosts) {
    const client = clientCogsFromPicks(line, warehouseCosts);
    if (client) {
      cogs = client.cogs;
      cogsPerGal = client.cogsPerGal;
    }
  }

  let unit = 0;
  if (line.negotiation_mode === "unit_price") {
    unit = line.unit_sale_price ?? 0;
  } else if (line.negotiation_mode === "total_price") {
    const total = line.total_deal_price ?? 0;
    unit = total > 0 ? total / qty : 0;
  } else if (line.negotiation_mode === "margin_pct") {
    const pct = line.target_margin_pct ?? 0;
    if (cogsPerGal != null && cogsPerGal > 0 && pct > 0 && pct < 100) {
      unit = cogsPerGal / (1 - pct / 100);
    } else if (previewLine) {
      unit = previewLine.unit_sale_price;
    }
  } else if (previewLine) {
    unit = previewLine.unit_sale_price;
  }

  const revenue = roundMoney(qty * unit);
  const margin = cogs != null ? roundMoney(revenue - cogs) : null;
  const marginPct = margin != null && revenue > 0 ? roundPct((margin / revenue) * 100) : null;
  const marginPerGal = margin != null && qty > 0 ? roundMoney(margin / qty) : null;

  return {
    unit: roundMoney(unit),
    revenue,
    cogs,
    cogsPerGal,
    margin,
    marginPerGal,
    marginPct,
    targetMarginPct: line.negotiation_mode === "margin_pct" ? line.target_margin_pct ?? null : null,
    belowCost: cogsPerGal != null && unit > 0 && unit < cogsPerGal,
  };
}

function roundMoney(n: number) {
  return Math.round(n * 100) / 100;
}

function roundPct(n: number) {
  return Math.round(n * 10) / 10;
}

function LinePriceBreakdown({
  line,
  quote,
  previewBusy,
  allocComplete,
  allocSum,
  onSetLineQty,
}: {
  line: BulkLineInput;
  quote: ReturnType<typeof computeLineQuote>;
  previewBusy: boolean;
  allocComplete: boolean;
  allocSum: number;
  onSetLineQty?: (qty: number) => void;
}) {
  const mode = line.negotiation_mode;
  const qty = line.qty_gallons;

  if (qty <= 0) {
    if (allocSum > 0) {
      return (
        <div className="text-xs text-amber-900 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 space-y-2">
          <p>
            You picked <strong>{allocSum.toFixed(2)} gal</strong> but <strong>Quantity (gal)</strong> is still blank —
            costing and margin need both to match.
          </p>
          {onSetLineQty && (
            <Button type="button" size="sm" variant="outline" onClick={() => onSetLineQty(roundMoney(allocSum))}>
              Set quantity to {allocSum.toFixed(2)} gal
            </Button>
          )}
        </div>
      );
    }
    return (
      <p className="text-xs text-muted-foreground rounded-lg border border-dashed border-border px-3 py-2.5">
        Enter <strong>Quantity (gal)</strong> at the top of this line, then pick supplier codes — or add picks first
        and we&apos;ll set quantity for you.
      </p>
    );
  }

  if (!allocComplete) {
    if (allocSum > 0) {
      const diff = roundMoney(allocSum - qty);
      return (
        <p className="text-xs text-amber-900 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
          Picks total <strong>{allocSum.toFixed(2)} gal</strong> but this line needs{" "}
          <strong>{qty} gal</strong>
          {diff > 0 ? (
            <> — remove {diff.toFixed(2)} gal from picks, or change the line quantity.</>
          ) : (
            <> — add {Math.abs(diff).toFixed(2)} more gal in the picks above.</>
          )}
        </p>
      );
    }
    return (
      <p className="text-xs text-amber-900 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
        Select a supplier code, enter gallons, and click <strong>Add</strong> — total picked must equal{" "}
        <strong>{qty} gal</strong>.
      </p>
    );
  }

  if (previewBusy && quote?.cogs == null) {
    return (
      <p className="text-xs text-muted-foreground flex items-center gap-2 px-1 py-1">
        <Loader2 size={14} className="animate-spin shrink-0" />
        Loading cost from warehouse picks…
      </p>
    );
  }

  if (!quote?.cogsPerGal) {
    const hasSale =
      (mode === "unit_price" && (line.unit_sale_price ?? 0) > 0) ||
      (mode === "total_price" && (line.total_deal_price ?? 0) > 0);
    return (
      <div className="text-xs text-amber-900 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 space-y-1">
        <p>
          A picked warehouse lot has <strong>no unit cost</strong> (see “no cost” in the dropdown) — set cost under
          Inventory → Raw Honey → Warehouse before margin can be calculated.
        </p>
        {hasSale && quote && quote.revenue > 0 && (
          <p className="text-foreground">
            Your quote: <strong>{peso(quote.unit)}/gal</strong> × {qty} gal = <strong>{peso(quote.revenue)}</strong>{" "}
            (margin pending until cost is set).
          </p>
        )}
      </div>
    );
  }

  const needsInput =
    (mode === "margin_pct" && !(line.target_margin_pct ?? 0)) ||
    (mode === "unit_price" && !(line.unit_sale_price ?? 0)) ||
    (mode === "total_price" && !(line.total_deal_price ?? 0));

  if (needsInput) {
    const hint =
      mode === "margin_pct"
        ? "Enter a target margin % (or tap a preset) to see sale ₱/gal and total for this line."
        : mode === "unit_price"
          ? "Enter your sale price per gallon — we'll show total deal and margin vs cost."
          : "Enter the partner's total offer — we'll show ₱/gal and margin vs cost.";
    return (
      <div className="rounded-lg border border-dashed border-border px-3 py-2.5 space-y-1">
        <p className="text-xs font-medium text-foreground">Your honey cost (from picks)</p>
        <p className="text-sm">
          <strong>{peso(quote.cogsPerGal)}/gal</strong>
          <span className="text-muted-foreground"> · {qty} gal = </span>
          <strong>{peso(quote.cogs!)}</strong>
          <span className="text-muted-foreground"> total COGS</span>
        </p>
        <p className="text-xs text-muted-foreground pt-1">{hint}</p>
      </div>
    );
  }

  if (!quote || quote.revenue <= 0) return null;

  const modeLabel =
    mode === "margin_pct"
      ? `Sale price at ${line.target_margin_pct}% margin`
      : mode === "unit_price"
        ? "Your sale price"
        : "Partner total offer";

  return (
    <div className="rounded-xl border border-[#C8900A]/30 bg-[#C8900A]/[0.06] p-4 space-y-3">
      <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        Price breakdown · {qty} gal
      </p>

      <div className="space-y-2 text-sm">
        <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
          <span className="text-muted-foreground">Honey cost (COGS)</span>
          <span className="text-right">
            <strong>{peso(quote.cogsPerGal)}/gal</strong>
            <span className="text-muted-foreground mx-1.5">→</span>
            <strong>{peso(quote.cogs!)}</strong>
            <span className="text-muted-foreground text-xs ml-1">total</span>
          </span>
        </div>

        <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t border-border/60 pt-2">
          <span className="font-medium text-foreground">{modeLabel}</span>
          <span className="text-right">
            <strong className="text-[#8B6914]">{peso(quote.unit)}/gal</strong>
            <span className="text-muted-foreground mx-1.5">→</span>
            <strong className="text-[#8B6914]">{peso(quote.revenue)}</strong>
            <span className="text-muted-foreground text-xs ml-1">total</span>
          </span>
        </div>

        {quote.margin != null && (
          <div
            className={cn(
              "flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t border-border/60 pt-2",
              quote.belowCost ? "text-red-700" : "text-green-800",
            )}
          >
            <span className="font-medium">Profit margin</span>
            <span className="text-right">
              <strong>
                {quote.marginPerGal != null ? `${peso(quote.marginPerGal)}/gal` : peso(quote.margin)}
              </strong>
              <span className="text-muted-foreground mx-1.5">→</span>
              <strong>{peso(quote.margin)}</strong>
              <span className="text-muted-foreground text-xs ml-1">
                {quote.marginPct != null ? `(${quote.marginPct}% of sale)` : "total"}
              </span>
            </span>
          </div>
        )}
      </div>

      {mode === "margin_pct" && quote.targetMarginPct != null && (
        <p className="text-[11px] text-muted-foreground leading-relaxed border-t border-border/40 pt-2">
          <strong>{quote.targetMarginPct}% margin</strong> means {quote.targetMarginPct}% of the sale price is profit.
          From cost {peso(quote.cogsPerGal)}/gal → sell at {peso(quote.unit)}/gal × {qty} gal ={" "}
          <strong>{peso(quote.revenue)}</strong>.
        </p>
      )}
      {mode === "unit_price" && (
        <p className="text-[11px] text-muted-foreground leading-relaxed border-t border-border/40 pt-2">
          At {peso(quote.unit)}/gal × {qty} gal = <strong>{peso(quote.revenue)}</strong>
          {quote.marginPct != null && (
            <>
              {" "}
              — {quote.marginPct}% margin ({peso(quote.margin!)} profit) over cost {peso(quote.cogsPerGal)}/gal.
            </>
          )}
        </p>
      )}
      {mode === "total_price" && (
        <p className="text-[11px] text-muted-foreground leading-relaxed border-t border-border/40 pt-2">
          Partner offer <strong>{peso(quote.revenue)}</strong> for {qty} gal = {peso(quote.unit)}/gal
          {quote.marginPct != null && (
            <>
              {" "}
              — {quote.marginPct}% margin ({peso(quote.margin!)} profit) over cost {peso(quote.cogsPerGal)}/gal.
            </>
          )}
        </p>
      )}

      {quote.belowCost && (
        <p className="text-xs text-red-700 flex items-center gap-1.5">
          <AlertCircle size={14} className="shrink-0" />
          Sale price is below honey cost — you would lose money on this line.
        </p>
      )}
    </div>
  );
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    draft: "bg-amber-100 text-amber-800",
    confirmed: "bg-green-100 text-green-800",
    voided: "bg-muted text-muted-foreground",
  };
  return map[status] ?? "bg-muted";
}

export function BulkHoneyOrdersPage() {
  const [view, setView] = useState<View>("list");
  const [orders, setOrders] = useState<BulkOrderSummary[]>([]);
  const [statusFilter, setStatusFilter] = useState<"all" | "draft" | "confirmed" | "voided">("all");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [missingMigration, setMissingMigration] = useState(false);

  const [editId, setEditId] = useState<number | null>(null);
  const [detail, setDetail] = useState<BulkOrderDetail | null>(null);
  const [customerName, setCustomerName] = useState("");
  const [partnerLabel, setPartnerLabel] = useState("");
  const [notes, setNotes] = useState("");
  const [shipmentDate, setShipmentDate] = useState("");
  const [courier, setCourier] = useState("");
  const [trackingRef, setTrackingRef] = useState("");
  const [lines, setLines] = useState<BulkLineInput[]>([emptyLine()]);
  const [preview, setPreview] = useState<{ lines: BulkPreviewLine[]; totals: BulkOrderSummary | null }>({
    lines: [],
    totals: null,
  });
  const [previewBusy, setPreviewBusy] = useState(false);
  const [previewError, setPreviewError] = useState<string | null>(null);
  const [saveBusy, setSaveBusy] = useState(false);
  const [formOpen, setFormOpen] = useState(false);
  const [warehouseCosts, setWarehouseCosts] = useState<Record<number, WarehousePickCost>>({});
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [actionBusy, setActionBusy] = useState(false);

  const refreshList = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await bulkOrdersList(statusFilter);
      setOrders(data.orders);
      setMissingMigration(!!data.missing_migration);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load bulk orders.");
    } finally {
      setLoading(false);
    }
  }, [statusFilter]);

  useEffect(() => {
    if (view === "list") void refreshList();
  }, [view, refreshList]);

  const loadDetail = useCallback(async (id: number) => {
    setLoading(true);
    setError(null);
    try {
      const data = await bulkOrdersGet(id);
      setDetail(data);
      setView("detail");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load order.");
    } finally {
      setLoading(false);
    }
  }, []);

  const closeForm = () => {
    setFormOpen(false);
    setPreview({ lines: [], totals: null });
    setPreviewError(null);
  };

  const openNew = () => {
    setEditId(null);
    setCustomerName("");
    setPartnerLabel("");
    setNotes("");
    setShipmentDate("");
    setCourier("");
    setTrackingRef("");
    setLines([emptyLine()]);
    setPreview({ lines: [], totals: null });
    setPreviewError(null);
    setFormOpen(true);
  };

  const openEdit = (d: BulkOrderDetail) => {
    setEditId(d.order.id);
    setCustomerName(d.order.customer_name);
    setPartnerLabel(d.order.partner_label ?? "");
    setNotes(d.order.notes ?? "");
    setShipmentDate(d.order.shipment_date ?? "");
    setCourier(d.order.courier ?? "");
    setTrackingRef(d.order.tracking_ref ?? "");
    setLines(
      d.lines.map(l => ({
        variant: l.variant,
        qty_gallons: l.qty_gallons,
        negotiation_mode: (l.negotiation_mode ?? "unit_price") as BulkNegotiationMode,
        target_margin_pct: l.target_margin_pct,
        unit_sale_price: l.unit_sale_price,
        allocations: l.allocations.map(a => ({
          warehouse_row_id: a.warehouse_row_id,
          qty_gallons: a.qty_gallons,
        })),
      })),
    );
    setPreview({ lines: [], totals: null });
    setPreviewError(null);
    setFormOpen(true);
  };

  const lineVariantsKey = useMemo(() => [...new Set(lines.map(l => l.variant))].join("|"), [lines]);

  useEffect(() => {
    if (!formOpen) {
      setWarehouseCosts({});
      return;
    }
    let cancelled = false;
    const variants = lineVariantsKey.split("|").filter(Boolean) as HoneyVariant[];
    if (variants.length === 0) return;
    void Promise.all(variants.map(v => bulkOrdersWarehouseRows(v))).then(results => {
      if (cancelled) return;
      const idx: Record<number, WarehousePickCost> = {};
      for (const data of results) {
        for (const row of data.rows) {
          idx[row.warehouse_row_id] = {
            unitCostPerGal: row.unit_cost_per_gallon,
            supplierCode: row.supplier_code,
          };
        }
      }
      setWarehouseCosts(idx);
    });
    return () => {
      cancelled = true;
    };
  }, [formOpen, lineVariantsKey]);

  const linesReady = useMemo(
    () =>
      lines.every(
        l =>
          l.qty_gallons > 0 &&
          l.allocations.length > 0 &&
          Math.abs(l.allocations.reduce((s, a) => s + a.qty_gallons, 0) - l.qty_gallons) < 0.02,
      ),
    [lines],
  );

  const saveDraftBlockers = useMemo(() => {
    const blockers: string[] = [];
    if (!customerName.trim()) blockers.push("customer name");
    if (!linesReady) blockers.push("warehouse picks matching line gallons");
    return blockers;
  }, [customerName, linesReady]);

  const canSaveDraft = saveDraftBlockers.length === 0;

  useEffect(() => {
    if (!formOpen || !linesReady) {
      setPreview({ lines: [], totals: null });
      setPreviewError(null);
      return;
    }
    const t = window.setTimeout(() => {
      setPreviewBusy(true);
      setPreviewError(null);
      bulkOrdersPreviewCost(lines)
        .then(data => {
          setPreview({ lines: data.lines, totals: data.totals as BulkOrderSummary });
          setPreviewError(null);
        })
        .catch(e => {
          setPreview({ lines: [], totals: null });
          setPreviewError(e instanceof ApiError ? e.message : "Could not compute honey COGS.");
        })
        .finally(() => setPreviewBusy(false));
    }, 350);
    return () => window.clearTimeout(t);
  }, [formOpen, lines, linesReady]);

  const buildPayload = () => ({
    customer_name: customerName.trim(),
    partner_label: partnerLabel.trim() || undefined,
    notes: notes.trim() || undefined,
    shipment_date: shipmentDate || undefined,
    courier: courier.trim() || undefined,
    tracking_ref: trackingRef.trim() || undefined,
    lines,
  });

  const saveDraft = async () => {
    if (!customerName.trim()) {
      setError("Customer name is required.");
      return;
    }
    if (!linesReady) {
      setError("Complete warehouse allocations for each line.");
      return;
    }
    setSaveBusy(true);
    setError(null);
    try {
      const payload = buildPayload();
      const result = editId
        ? await bulkOrdersUpdate(editId, payload)
        : await bulkOrdersCreate(payload);
      setNotice(editId ? "Draft updated." : "Draft saved.");
      setDetail(result);
      setFormOpen(false);
      setView("detail");
      setEditId(result.order.id);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Save failed.");
    } finally {
      setSaveBusy(false);
    }
  };

  const runConfirm = async () => {
    if (!detail) return;
    setActionBusy(true);
    setError(null);
    try {
      const result = await bulkOrdersConfirm(detail.order.id);
      setDetail(result);
      setConfirmOpen(false);
      setNotice("Order confirmed — warehouse stock deducted.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Confirm failed.");
    } finally {
      setActionBusy(false);
    }
  };

  const runVoid = async () => {
    if (!detail) return;
    setActionBusy(true);
    setError(null);
    try {
      const result = await bulkOrdersVoid(detail.order.id);
      setDetail(result);
      setNotice("Order voided — warehouse stock restored.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Void failed.");
    } finally {
      setActionBusy(false);
    }
  };

  const runDelete = async () => {
    if (!detail || detail.order.status !== "draft") return;
    setActionBusy(true);
    setError(null);
    try {
      await bulkOrdersDelete(detail.order.id);
      setNotice("Draft deleted.");
      setDetail(null);
      setView("list");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Delete failed.");
    } finally {
      setActionBusy(false);
    }
  };

  return (
    <PageLayout
      title="Bulk orders"
      subtitle="B2B honey from warehouse — negotiate from COGS, confirm to deduct stock."
    >
      <div className="space-y-4 max-w-6xl mx-auto">
        {missingMigration && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Run <code className="text-xs">php tools/apply_migration_phase51.php</code> to enable bulk orders.
          </div>
        )}
        <AppErrorBanner message={error} />
        <AppSuccessNotice message={notice} />

        {view === "list" && (
          <AppSectionEnter>
            <div className="flex flex-wrap items-center gap-2 justify-between">
              <div className="flex gap-2">
                {(["all", "draft", "confirmed", "voided"] as const).map(s => (
                  <Button
                    key={s}
                    type="button"
                    size="sm"
                    variant={statusFilter === s ? "default" : "outline"}
                    onClick={() => setStatusFilter(s)}
                  >
                    {s === "all" ? "All" : s.charAt(0).toUpperCase() + s.slice(1)}
                  </Button>
                ))}
              </div>
              <div className="flex gap-2">
                <Button type="button" variant="outline" size="sm" onClick={() => void refreshList()}>
                  <RefreshCcw size={14} className="mr-1" /> Refresh
                </Button>
                <Button type="button" size="sm" onClick={openNew}>
                  <Plus size={14} className="mr-1" /> New bulk order
                </Button>
              </div>
            </div>

            {loading ? (
              <div className="flex justify-center py-16">
                <Loader2 className="animate-spin text-muted-foreground" size={28} />
              </div>
            ) : orders.length === 0 ? (
              <p className="text-sm text-muted-foreground py-8 text-center">No bulk orders yet.</p>
            ) : (
              <div className="rounded-xl border border-border overflow-hidden mt-4">
                <table className="w-full text-sm">
                  <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                      <th className="px-4 py-3">Order</th>
                      <th className="px-4 py-3">Customer</th>
                      <th className="px-4 py-3">Status</th>
                      <th className="px-4 py-3 text-right">Gal</th>
                      <th className="px-4 py-3 text-right">COGS</th>
                      <th className="px-4 py-3 text-right">Revenue</th>
                      <th className="px-4 py-3 text-right">Margin</th>
                    </tr>
                  </thead>
                  <tbody>
                    {orders.map(o => (
                      <tr
                        key={o.id}
                        className={cn("border-t border-border cursor-pointer", appHoverRow)}
                        onClick={() => void loadDetail(o.id)}
                      >
                        <td className="px-4 py-3 font-medium">{o.order_no}</td>
                        <td className="px-4 py-3">{o.customer_name}</td>
                        <td className="px-4 py-3">
                          <span className={cn("text-xs px-2 py-0.5 rounded-full font-medium", statusBadge(o.status))}>
                            {o.status}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-right">{o.total_gallons.toLocaleString()}</td>
                        <td className="px-4 py-3 text-right">{peso(o.total_cogs)}</td>
                        <td className="px-4 py-3 text-right">{peso(o.total_revenue)}</td>
                        <td className="px-4 py-3 text-right">{peso(o.total_margin)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </AppSectionEnter>
        )}

        {view === "detail" && detail && (
          <AppSectionEnter>
            <div className="flex flex-wrap gap-2 items-center mb-4">
              <Button type="button" variant="ghost" size="sm" onClick={() => setView("list")}>
                <ArrowLeft size={14} className="mr-1" /> Back
              </Button>
              {detail.order.status === "draft" && (
                <>
                  <Button type="button" variant="outline" size="sm" onClick={() => openEdit(detail)}>
                    Edit draft
                  </Button>
                  <Button type="button" size="sm" onClick={() => setConfirmOpen(true)}>
                    <PackageCheck size={14} className="mr-1" /> Confirm & deduct
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="text-red-600"
                    disabled={actionBusy}
                    onClick={() => void runDelete()}
                  >
                    <Trash2 size={14} className="mr-1" /> Delete
                  </Button>
                </>
              )}
              {detail.order.status === "confirmed" && (
                <Button type="button" variant="outline" size="sm" disabled={actionBusy} onClick={() => void runVoid()}>
                  <RotateCcw size={14} className="mr-1" /> Void & restore stock
                </Button>
              )}
            </div>

            <BulkOrderDetailView detail={detail} />

            <AppModal open={confirmOpen} onClose={() => setConfirmOpen(false)} panelClassName="max-w-md w-full">
              <AppModalHeader title="Confirm & deduct warehouse stock?" onClose={() => setConfirmOpen(false)} />
              <AppModalBody className="text-sm text-muted-foreground space-y-2">
                <p>This will deduct gallons from the supplier codes listed on this order. This cannot be undone except by voiding.</p>
                <ul className="list-disc pl-5">
                  {detail.supplier_codes_used.map(s => (
                    <li key={s.supplier_code}>
                      <strong>{s.supplier_code}</strong> — {s.qty_gallons} gal
                    </li>
                  ))}
                </ul>
              </AppModalBody>
              <AppModalFooter>
                <Button type="button" variant="outline" onClick={() => setConfirmOpen(false)} disabled={actionBusy}>
                  Cancel
                </Button>
                <Button type="button" onClick={() => void runConfirm()} disabled={actionBusy}>
                  {actionBusy ? "Confirming…" : "Confirm & deduct"}
                </Button>
              </AppModalFooter>
            </AppModal>
          </AppSectionEnter>
        )}

        <AppModal
          open={formOpen}
          onClose={closeForm}
          panelClassName="max-w-5xl w-full"
          labelledBy="bulk-order-form-title"
        >
          <AppModalHeader
            title={editId ? "Edit draft" : "New bulk order"}
            titleId="bulk-order-form-title"
            subtitle="Negotiate from warehouse COGS — save as draft, confirm when it ships."
            onClose={closeForm}
          />
          <AppModalBody className="space-y-4">
            <BulkOrderFormFields
              customerName={customerName}
              setCustomerName={setCustomerName}
              partnerLabel={partnerLabel}
              setPartnerLabel={setPartnerLabel}
              notes={notes}
              setNotes={setNotes}
              lines={lines}
              setLines={setLines}
              preview={preview}
              previewBusy={previewBusy}
              previewError={previewError}
              warehouseCosts={warehouseCosts}
            />
          </AppModalBody>
          {(preview.totals || lines.some((l, i) => computeLineQuote(l, preview.lines[i], warehouseCosts))) && (
            <div className="px-5 py-3 border-t border-border bg-muted/10 flex-shrink-0">
              <OrderCostingSummary
                lines={lines}
                previewLines={preview.lines}
                previewBusy={previewBusy}
                warehouseCosts={warehouseCosts}
                embedded
              />
            </div>
          )}
          <AppModalFooter className="justify-between flex-col sm:flex-row gap-2 items-stretch sm:items-center">
            <div className="flex flex-col gap-1 min-w-0">
              {!canSaveDraft && (
                <p className="text-xs text-amber-800">
                  To save: {saveDraftBlockers.join(" · ")}
                </p>
              )}
              {previewError && canSaveDraft && (
                <p className="text-xs text-amber-800">Fix costing errors above before save will succeed.</p>
              )}
            </div>
            <div className="flex gap-2 justify-end shrink-0">
            <Button type="button" variant="outline" onClick={closeForm} disabled={saveBusy}>
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => void saveDraft()}
              disabled={saveBusy || !canSaveDraft}
              className="px-6 font-bold text-white bg-[#C8900A] hover:bg-[#a87808] disabled:opacity-50"
            >
              {saveBusy ? (
                <Loader2 size={16} className="inline animate-spin mr-1" />
              ) : (
                <Save size={16} className="inline mr-1" />
              )}
              Save draft
            </Button>
            </div>
          </AppModalFooter>
        </AppModal>
      </div>
    </PageLayout>
  );
}

function BulkOrderDetailView({ detail }: { detail: BulkOrderDetail }) {
  const o = detail.order;
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold">{o.order_no}</h2>
          <p className="text-muted-foreground">{o.customer_name}</p>
          {o.partner_label && <p className="text-sm text-muted-foreground">Partner: {o.partner_label}</p>}
        </div>
        <span className={cn("text-xs px-2 py-1 rounded-full font-medium", statusBadge(o.status))}>{o.status}</span>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {[
          { label: "Total COGS", value: peso(o.total_cogs) },
          { label: "Revenue", value: peso(o.total_revenue) },
          { label: "Margin", value: peso(o.total_margin) },
          { label: "Margin %", value: o.margin_pct != null ? `${o.margin_pct.toFixed(1)}%` : "—" },
        ].map(c => (
          <div key={c.label} className="rounded-xl border border-border bg-card p-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">{c.label}</p>
            <p className="text-lg font-bold mt-1">{c.value}</p>
          </div>
        ))}
      </div>

      <div className="rounded-xl border border-border p-4">
        <h3 className="font-semibold mb-2">Supplier codes used</h3>
        <table className="w-full text-sm">
          <thead>
            <tr className="text-xs text-muted-foreground uppercase">
              <th className="text-left py-1">Code</th>
              <th className="text-right py-1">Gallons</th>
            </tr>
          </thead>
          <tbody>
            {detail.supplier_codes_used.map(s => (
              <tr key={s.supplier_code} className="border-t border-border">
                <td className="py-2 font-mono">{s.supplier_code}</td>
                <td className="py-2 text-right">{s.qty_gallons}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {detail.lines.map(line => (
        <div key={line.id} className="rounded-xl border border-border p-4 space-y-2">
          <p className="font-semibold">
            {variantLabel(line.variant)} — {line.qty_gallons} gal @ {peso(line.unit_sale_price)}/gal
          </p>
          <p className="text-sm text-muted-foreground">
            COGS {peso(line.line_cogs)} ({peso(line.cogs_per_gallon)}/gal) · Revenue {peso(line.line_revenue)} · Margin{" "}
            {peso(line.line_margin)}
          </p>
          <table className="w-full text-xs mt-2">
            <thead>
              <tr className="text-muted-foreground">
                <th className="text-left py-1">Supplier code</th>
                <th className="text-right py-1">Gal</th>
                <th className="text-right py-1">Cost/gal</th>
                <th className="text-right py-1">COGS</th>
              </tr>
            </thead>
            <tbody>
              {line.allocations.map(a => (
                <tr key={`${a.warehouse_row_id}-${a.supplier_code}`} className="border-t border-border/60">
                  <td className="py-1 font-mono">{a.supplier_code}</td>
                  <td className="py-1 text-right">{a.qty_gallons}</td>
                  <td className="py-1 text-right">{a.unit_cost_per_gallon != null ? peso(a.unit_cost_per_gallon) : "—"}</td>
                  <td className="py-1 text-right">{peso(a.line_cogs_slice)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ))}
    </div>
  );
}

function BulkOrderFormFields({
  customerName,
  setCustomerName,
  partnerLabel,
  setPartnerLabel,
  notes,
  setNotes,
  lines,
  setLines,
  preview,
  previewBusy,
  previewError,
  warehouseCosts,
}: {
  customerName: string;
  setCustomerName: (v: string) => void;
  partnerLabel: string;
  setPartnerLabel: (v: string) => void;
  notes: string;
  setNotes: (v: string) => void;
  lines: BulkLineInput[];
  setLines: Dispatch<SetStateAction<BulkLineInput[]>>;
  preview: { lines: BulkPreviewLine[]; totals: BulkOrderSummary | null };
  previewBusy: boolean;
  previewError: string | null;
  warehouseCosts: Record<number, WarehousePickCost>;
}) {
  const updateLine = (idx: number, patch: Partial<BulkLineInput>) => {
    setLines(prev => prev.map((l, i) => (i === idx ? { ...l, ...patch } : l)));
  };

  const addLine = () => setLines(prev => [...prev, emptyLine()]);
  const removeLine = (idx: number) => setLines(prev => prev.filter((_, i) => i !== idx));

  return (
    <>
      <div className="rounded-lg border border-[#C8900A]/30 bg-[#C8900A]/5 px-4 py-3 text-sm text-foreground/90 space-y-1">
        <p className="font-semibold">How this form works</p>
        <ol className="list-decimal pl-5 space-y-0.5 text-muted-foreground">
          <li>Enter gallons, then pick supplier codes (or use <strong>Auto-fill lots</strong> — not confirm).</li>
          <li>Negotiate your sale price — compare to <strong>honey COGS</strong> and margin.</li>
          <li><strong>Save draft</strong> (stock unchanged), then <strong>Confirm &amp; deduct</strong> on the order when it ships.</li>
        </ol>
      </div>

      {previewError && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex items-start gap-2">
          <AlertCircle size={16} className="mt-0.5 shrink-0" />
          <span>{previewError} Set unit cost on the warehouse row under Inventory → Raw Honey → Warehouse.</span>
        </div>
      )}

      <div className="grid sm:grid-cols-2 gap-3">
        <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          Customer *
          <input
            value={customerName}
            onChange={e => setCustomerName(e.target.value)}
            className="w-full mt-1 border rounded-lg px-3 py-2 text-sm font-normal"
          />
        </label>
        <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          Partner label
          <input
            value={partnerLabel}
            onChange={e => setPartnerLabel(e.target.value)}
            className="w-full mt-1 border rounded-lg px-3 py-2 text-sm font-normal"
          />
        </label>
        <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:col-span-2">
          Notes
          <input
            value={notes}
            onChange={e => setNotes(e.target.value)}
            className="w-full mt-1 border rounded-lg px-3 py-2 text-sm font-normal"
          />
        </label>
      </div>

      {lines.map((line, idx) => (
        <BulkLineEditor
          key={idx}
          line={line}
          previewLine={preview.lines[idx]}
          previewBusy={previewBusy}
          warehouseCosts={warehouseCosts}
          onChange={patch => updateLine(idx, patch)}
          onRemove={lines.length > 1 ? () => removeLine(idx) : undefined}
        />
      ))}

      <Button type="button" variant="outline" size="sm" onClick={addLine}>
        <Plus size={14} className="mr-1" /> Add line
      </Button>
    </>
  );
}

function OrderCostingSummary({
  lines,
  previewLines,
  previewBusy,
  warehouseCosts,
  embedded,
}: {
  lines: BulkLineInput[];
  previewLines: BulkPreviewLine[];
  previewBusy: boolean;
  warehouseCosts?: Record<number, WarehousePickCost>;
  embedded?: boolean;
}) {
  let totalRevenue = 0;
  let totalCogs = 0;
  let hasCogs = true;
  let totalGallons = 0;

  for (let i = 0; i < lines.length; i++) {
    const q = computeLineQuote(lines[i], previewLines[i], warehouseCosts);
    if (!q) continue;
    totalRevenue += q.revenue;
    totalGallons += lines[i].qty_gallons;
    if (q.cogs != null) totalCogs += q.cogs;
    else hasCogs = false;
  }

  const totalMargin = hasCogs ? roundMoney(totalRevenue - totalCogs) : null;
  const marginPct =
    totalMargin != null && totalRevenue > 0 ? roundPct((totalMargin / totalRevenue) * 100) : null;
  const cogsPerGal =
    hasCogs && totalGallons > 0 ? roundMoney(totalCogs / totalGallons) : null;
  const missingCodes =
    warehouseCosts && !hasCogs ? missingCostSupplierCodes(lines, warehouseCosts) : [];

  return (
    <div
      className={cn(
        "space-y-2",
        !embedded && "sticky bottom-4 bg-background/95 backdrop-blur border border-border rounded-xl p-4 shadow-lg",
      )}
    >
      {!hasCogs && !previewBusy && missingCodes.length > 0 && totalRevenue > 0 && (
        <p className="text-xs text-amber-900 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
          <strong>COGS and margin are blank</strong> because{" "}
          {missingCodes.length === 1 ? "lot" : "lots"}{" "}
          <span className="font-mono">{missingCodes.join(", ")}</span> have no unit cost. Set cost in{" "}
          <Link
            to="/inventory/raw-materials/honey/warehouse"
            className="underline font-semibold hover:text-amber-950"
          >
            Inventory → Raw Honey → Warehouse
          </Link>
          , then COGS, margin, and margin % will fill in below.
        </p>
      )}
      <div
        className={cn(
          "grid grid-cols-2 sm:grid-cols-4 gap-3",
          embedded
            ? ""
            : "bg-background/95 backdrop-blur border border-border rounded-xl p-4 shadow-lg",
        )}
      >
      <div>
        <p className="text-xs text-muted-foreground uppercase">Honey COGS (cost)</p>
        <p className="text-lg font-bold">{hasCogs ? peso(totalCogs) : previewBusy ? "…" : "—"}</p>
        {hasCogs && cogsPerGal != null && (
          <p className="text-[10px] text-muted-foreground mt-0.5">{peso(cogsPerGal)}/gal avg</p>
        )}
        {!hasCogs && !previewBusy && missingCodes.length > 0 && (
          <p className="text-[10px] text-amber-700 mt-0.5 leading-snug">
            Blocked — no cost on {missingCodes.join(", ")}
          </p>
        )}
      </div>
      <div>
        <p className="text-xs text-muted-foreground uppercase">Your quote (total)</p>
        <p className="text-lg font-bold">{totalRevenue > 0 ? peso(totalRevenue) : "—"}</p>
      </div>
      <div>
        <p className="text-xs text-muted-foreground uppercase">Margin</p>
        <p className="text-lg font-bold">{totalMargin != null ? peso(totalMargin) : "—"}</p>
        {!hasCogs && !previewBusy && totalRevenue > 0 && (
          <p className="text-[10px] text-muted-foreground mt-0.5">Needs COGS first</p>
        )}
      </div>
      <div>
        <p className="text-xs text-muted-foreground uppercase">Margin %</p>
        <p className="text-lg font-bold">{marginPct != null ? `${marginPct}%` : "—"}</p>
        {!hasCogs && !previewBusy && totalRevenue > 0 && (
          <p className="text-[10px] text-muted-foreground mt-0.5">Needs COGS first</p>
        )}
      </div>
      </div>
    </div>
  );
}

function BulkLineEditor({
  line,
  previewLine,
  previewBusy,
  warehouseCosts,
  onChange,
  onRemove,
}: {
  line: BulkLineInput;
  previewLine?: BulkPreviewLine;
  previewBusy?: boolean;
  warehouseCosts?: Record<number, WarehousePickCost>;
  onChange: (patch: Partial<BulkLineInput>) => void;
  onRemove?: () => void;
}) {
  const [whRows, setWhRows] = useState<BulkWarehouseRow[]>([]);
  const [whBusy, setWhBusy] = useState(false);
  const [pickRowId, setPickRowId] = useState("");
  const [pickQty, setPickQty] = useState("");
  const [fifoHint, setFifoHint] = useState<string | null>(null);
  const [addPickHint, setAddPickHint] = useState<string | null>(null);

  const allocSum = line.allocations.reduce((s, a) => s + a.qty_gallons, 0);
  const allocComplete = line.qty_gallons > 0 && Math.abs(allocSum - line.qty_gallons) < 0.02;
  const remainingGal = Math.max(0, roundMoney(line.qty_gallons - allocSum));

  const suggestPickQty = useCallback(
    (rowId: string) => {
      const row = whRows.find(r => String(r.warehouse_row_id) === rowId);
      if (!row || line.qty_gallons <= 0) return "";
      const need = Math.max(0, line.qty_gallons - allocSum);
      if (need <= 0) return "";
      const take = Math.min(need, row.current_qty);
      return take > 0 ? String(take) : "";
    },
    [whRows, line.qty_gallons, allocSum],
  );

  useEffect(() => {
    setWhBusy(true);
    bulkOrdersWarehouseRows(line.variant)
      .then(d => setWhRows(d.rows))
      .catch(() => setWhRows([]))
      .finally(() => setWhBusy(false));
  }, [line.variant]);

  const suggestFifo = async () => {
    if (line.qty_gallons <= 0) {
      setFifoHint("Enter quantity (gallons) first.");
      return;
    }
    setFifoHint(null);
    try {
      const data = await bulkOrdersSuggestAllocations(line.variant, line.qty_gallons);
      onChange({ allocations: data.allocations });
      setFifoHint("Auto-filled oldest warehouse lots. You can still edit picks below.");
    } catch (e) {
      setFifoHint(e instanceof ApiError ? e.message : "Could not auto-fill — add supplier codes manually.");
    }
  };

  const addAllocation = () => {
    setAddPickHint(null);
    const wid = parseInt(pickRowId, 10);
    const qty = parseFloat(pickQty);
    if (!wid) {
      setAddPickHint("Select a supplier code from the dropdown first.");
      return;
    }
    if (!Number.isFinite(qty) || qty <= 0) {
      setAddPickHint("Enter how many gallons to take from this lot, then click Add.");
      return;
    }
    const row = whRows.find(r => r.warehouse_row_id === wid);
    if (!row) {
      setAddPickHint("That warehouse row is no longer available — refresh the variant or pick another code.");
      return;
    }
    if (qty > row.current_qty + 0.001) {
      setAddPickHint(`${row.supplier_code} only has ${row.current_qty} gal available.`);
      return;
    }
    const existing = line.allocations.find(a => a.warehouse_row_id === wid);
    const next = existing
      ? line.allocations.map(a => (a.warehouse_row_id === wid ? { ...a, qty_gallons: qty } : a))
      : [...line.allocations, { warehouse_row_id: wid, qty_gallons: qty }];
    const newSum = roundMoney(next.reduce((s, a) => s + a.qty_gallons, 0));
    const patch: Partial<BulkLineInput> = { allocations: next };
    if (line.qty_gallons <= 0) {
      patch.qty_gallons = newSum;
    }
    onChange(patch);
    setPickQty("");
    setAddPickHint(
      line.qty_gallons <= 0
        ? `Added ${row.supplier_code} — ${qty} gal. Line quantity set to ${newSum} gal.`
        : `Added ${row.supplier_code} — ${qty} gal.`,
    );
  };

  const updateAllocQty = (wid: number, qtyStr: string) => {
    const qty = parseFloat(qtyStr);
    if (!Number.isFinite(qty) || qty <= 0) return;
    const next = line.allocations.map(a => (a.warehouse_row_id === wid ? { ...a, qty_gallons: qty } : a));
    const newSum = roundMoney(next.reduce((s, a) => s + a.qty_gallons, 0));
    onChange({
      allocations: next,
      qty_gallons: line.qty_gallons <= 0 || line.allocations.length <= 1 ? newSum : line.qty_gallons,
    });
  };

  const removeAlloc = (wid: number) => {
    const next = line.allocations.filter(a => a.warehouse_row_id !== wid);
    const newSum = roundMoney(next.reduce((s, a) => s + a.qty_gallons, 0));
    onChange({
      allocations: next,
      ...(next.length === 0 ? {} : { qty_gallons: newSum > 0 ? newSum : line.qty_gallons }),
    });
  };

  const syncQtyFromPicks = () => {
    if (allocSum > 0) onChange({ qty_gallons: roundMoney(allocSum) });
  };

  const mode = line.negotiation_mode;
  const quote = computeLineQuote(line, previewLine, warehouseCosts);

  return (
    <div className="rounded-xl border border-border p-4 space-y-3">
      <div className="flex justify-between items-center">
        <h3 className="font-semibold">Honey line</h3>
        {onRemove && (
          <Button type="button" variant="ghost" size="sm" className="text-red-600" onClick={onRemove}>
            Remove
          </Button>
        )}
      </div>

      <div className="grid sm:grid-cols-3 gap-3">
        <label className="text-xs font-semibold text-muted-foreground uppercase">
          Variant
          <select
            value={line.variant}
            onChange={e => onChange({ variant: e.target.value as HoneyVariant, allocations: [] })}
            className="w-full mt-1 border rounded-lg px-2 py-2 text-sm font-normal capitalize"
          >
            {VARIANTS.map(v => (
              <option key={v} value={v}>
                {v}
              </option>
            ))}
          </select>
        </label>
        <label className="text-xs font-semibold text-muted-foreground uppercase">
          Quantity (gal) *
          <input
            type="number"
            min={0}
            step={0.001}
            placeholder={allocSum > 0 && line.qty_gallons <= 0 ? `e.g. ${allocSum}` : "How many gal in this deal?"}
            value={line.qty_gallons || ""}
            onChange={e => onChange({ qty_gallons: parseFloat(e.target.value) || 0 })}
            className={cn(
              "w-full mt-1 border rounded-lg px-2 py-2 text-sm font-normal",
              allocSum > 0 && line.qty_gallons <= 0 && "border-amber-400 ring-1 ring-amber-200",
              allocComplete && "border-green-400/60",
            )}
          />
          <span className="text-[10px] font-normal normal-case text-muted-foreground">
            Must match total picked below{allocSum > 0 && line.qty_gallons <= 0 ? " — or click Add to auto-set" : ""}
          </span>
        </label>
        <div className="flex flex-col items-stretch justify-end gap-1">
          <Button type="button" variant="outline" size="sm" className="w-full" onClick={() => void suggestFifo()}>
            Auto-fill lots
          </Button>
          <p className="text-[10px] text-muted-foreground leading-tight">
            Picks oldest supplier codes — not stock confirm
          </p>
        </div>
      </div>
      {fifoHint && (
        <p className={cn("text-xs", fifoHint.includes("Auto-filled") ? "text-green-700" : "text-amber-700")}>
          {fifoHint}
        </p>
      )}

      <div className="rounded-lg bg-muted/40 p-3 space-y-2">
        <p className="text-xs font-semibold uppercase text-muted-foreground">Which supplier codes? (warehouse picks)</p>
        <p className={cn("text-xs", allocComplete ? "text-green-700" : "text-amber-700")}>
          Allocated {allocSum.toFixed(2)} / {line.qty_gallons > 0 ? line.qty_gallons : "?"} gal
          {line.qty_gallons <= 0 && allocSum > 0 && (
            <>
              {" "}
              <button
                type="button"
                className="underline font-medium hover:text-amber-900"
                onClick={syncQtyFromPicks}
              >
                Set quantity to {allocSum.toFixed(2)}
              </button>
            </>
          )}
          {!allocComplete && line.qty_gallons > 0 && remainingGal > 0 && (
            <span className="text-muted-foreground"> · {remainingGal.toFixed(2)} gal still needed</span>
          )}
        </p>
        {whBusy ? (
          <Loader2 size={16} className="animate-spin" />
        ) : (
          <div className="flex flex-wrap gap-2 items-end">
            <select
              value={pickRowId}
              onChange={e => {
                const id = e.target.value;
                setPickRowId(id);
                setAddPickHint(null);
                const suggested = suggestPickQty(id);
                if (suggested) {
                  setPickQty(suggested);
                } else if (id) {
                  const row = whRows.find(r => String(r.warehouse_row_id) === id);
                  if (row && line.qty_gallons <= 0) setPickQty(String(row.current_qty));
                }
              }}
              className="border rounded-lg px-2 py-1.5 text-sm min-w-[180px]"
            >
              <option value="">Supplier code…</option>
              {whRows.map(r => (
                <option key={r.warehouse_row_id} value={String(r.warehouse_row_id)}>
                  {r.supplier_code} — {r.arrival_date} — {r.current_qty} gal
                  {r.unit_cost_per_gallon != null ? ` @ ${peso(r.unit_cost_per_gallon)}` : " (no cost)"}
                </option>
              ))}
            </select>
            <input
              type="number"
              min={0}
              step={0.001}
              placeholder={
                line.qty_gallons > 0
                  ? `Gal (need ${remainingGal || line.qty_gallons})`
                  : pickRowId
                    ? "Gal from lot"
                    : "Gal"
              }
              value={pickQty}
              onChange={e => {
                setPickQty(e.target.value);
                setAddPickHint(null);
              }}
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  addAllocation();
                }
              }}
              className="border rounded-lg px-2 py-1.5 text-sm w-28"
            />
            <Button type="button" size="sm" variant="secondary" onClick={addAllocation}>
              Add
            </Button>
          </div>
        )}
        {addPickHint && (
          <p className={cn("text-xs", addPickHint.startsWith("Added") ? "text-green-700" : "text-amber-800")}>
            {addPickHint}
          </p>
        )}
        {line.allocations.length > 0 && (
          <ul className="text-sm space-y-1 mt-2">
            {line.allocations.map(a => {
              const row = whRows.find(r => r.warehouse_row_id === a.warehouse_row_id);
              const missingCost = row != null && row.unit_cost_per_gallon == null;
              return (
                <li key={a.warehouse_row_id} className="flex flex-wrap justify-between items-center gap-2">
                  <span className="font-mono text-xs">
                    {row?.supplier_code ?? `#${a.warehouse_row_id}`}
                    {missingCost && (
                      <span className="text-amber-700 font-sans ml-1">(no cost — set in warehouse)</span>
                    )}
                  </span>
                  <div className="flex items-center gap-2">
                    <input
                      type="number"
                      min={0}
                      step={0.001}
                      value={a.qty_gallons}
                      onChange={e => updateAllocQty(a.warehouse_row_id, e.target.value)}
                      className="border rounded-lg px-2 py-1 text-xs w-20 text-right"
                      aria-label={`Gallons for ${row?.supplier_code ?? a.warehouse_row_id}`}
                    />
                    <span className="text-xs text-muted-foreground">gal</span>
                    <Button type="button" variant="ghost" size="sm" onClick={() => removeAlloc(a.warehouse_row_id)}>
                      Remove
                    </Button>
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </div>

      <div className="space-y-3">
        <p className="text-xs font-semibold uppercase text-muted-foreground">Negotiate sale price</p>
        <p className="text-[11px] text-muted-foreground -mt-1">
          Choose how you want to quote — the breakdown below updates from your warehouse cost and gallons.
        </p>
        <div className="flex flex-wrap gap-2">
          {(["margin_pct", "unit_price", "total_price"] as const).map(m => (
            <Button
              key={m}
              type="button"
              size="sm"
              variant={mode === m ? "default" : "outline"}
              onClick={() => onChange({ negotiation_mode: m })}
            >
              {m === "margin_pct" ? "Margin %" : m === "unit_price" ? "₱/gal" : "Total ₱"}
            </Button>
          ))}
        </div>
        {mode === "margin_pct" && (
          <div className="space-y-2">
            <label className="text-xs text-muted-foreground block">
              Target margin % <span className="font-normal">(profit as % of sale price)</span>
              <input
                type="number"
                min={0}
                max={99}
                step={0.1}
                placeholder="e.g. 10"
                value={line.target_margin_pct ?? ""}
                onChange={e => onChange({ target_margin_pct: parseFloat(e.target.value) || 0 })}
                className="border rounded-lg px-3 py-2 text-sm w-full max-w-xs mt-1 font-normal text-foreground"
              />
            </label>
            <div className="flex flex-wrap gap-2">
              {MARGIN_CHIPS.map(pct => (
                <Button
                  key={pct}
                  type="button"
                  size="sm"
                  variant={line.target_margin_pct === pct ? "default" : "outline"}
                  onClick={() => onChange({ target_margin_pct: pct, negotiation_mode: "margin_pct" })}
                >
                  {pct}%
                </Button>
              ))}
            </div>
          </div>
        )}
        {mode === "unit_price" && (
          <label className="text-xs text-muted-foreground block">
            Sale price per gallon
            <input
              type="number"
              min={0}
              step={0.01}
              placeholder="e.g. 600"
              value={line.unit_sale_price ?? ""}
              onChange={e => onChange({ unit_sale_price: parseFloat(e.target.value) || 0 })}
              className="border rounded-lg px-3 py-2 text-sm w-full max-w-xs mt-1 font-normal text-foreground"
            />
          </label>
        )}
        {mode === "total_price" && (
          <label className="text-xs text-muted-foreground block">
            Total deal price <span className="font-normal">(what the partner pays for all {line.qty_gallons || "…"} gal)</span>
            <input
              type="number"
              min={0}
              step={0.01}
              placeholder="e.g. 60000"
              value={line.total_deal_price ?? ""}
              onChange={e => onChange({ total_deal_price: parseFloat(e.target.value) || 0 })}
              className="border rounded-lg px-3 py-2 text-sm w-full max-w-xs mt-1 font-normal text-foreground"
            />
          </label>
        )}

        <LinePriceBreakdown
          line={line}
          quote={quote}
          previewBusy={!!previewBusy}
          allocComplete={allocComplete}
          allocSum={allocSum}
          onSetLineQty={qty => onChange({ qty_gallons: qty })}
        />
      </div>
    </div>
  );
}
