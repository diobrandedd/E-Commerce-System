import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useSearchParams } from "react-router";
import { BookOpen, CheckCircle2, Loader2, Plus, XCircle } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { CorrectionReviewModal, type CorrectionRequestDetail } from "@/app/components/honey/CorrectionReviewModal";
import { EditRequestsSection } from "@/app/components/requests/EditRequestsSection";
import { InventoryRequestsGuideModal } from "@/app/components/requests/InventoryRequestsGuideModal";
import { runInventoryRequestsTour } from "@/app/components/tour/guides/inventoryRequestsTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { RequestsHubSection } from "@/app/components/requests/RequestsHubSection";
import { SupplierCombobox } from "@/app/components/suppliers/SupplierCombobox";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_SIZES, HONEY_VARIANTS } from "@/app/lib/honey";
import { PackagingOnHandRequestModal } from "@/app/components/inventory/PackagingOnHandRequestModal";
import { SupplyIssueRequestModal } from "@/app/components/inventory/SupplyIssueRequestModal";
import { SupplyRequestReviewModal } from "@/app/components/inventory/SupplyRequestReviewModal";
import {
  approveMaterialRequest,
  listMaterialRequests,
  rejectMaterialRequest,
  type MaterialAdjustmentRequest,
} from "@/app/lib/materials";
import type { Supplier } from "@/app/lib/suppliers";

type CorrectionType = "qty_delta" | "set_qty" | "variant" | "supplier" | "packaging_size";

function formatRequestLine(r: CorrectionRequestDetail): string {
  if (r.summary) return r.summary;
  const ctx = r.row_context;
  const prefix = ctx?.supplier_code ? `${ctx.supplier_code} · ` : "";
  const t = (r.correction_type ?? "qty_delta") as CorrectionType;
  if (t === "set_qty") return `${prefix}Set qty → ${r.new_value}`;
  if (t === "variant") return `${prefix}Variant → ${r.new_value}`;
  if (t === "supplier") return `${prefix}Supplier change`;
  if (t === "packaging_size") return `${prefix}Packaging ${r.new_value}`;
  return `${prefix}${r.action} ${r.amount}${r.size ? ` (${r.size})` : ""}`;
}

const ROWS_PER_PAGE = 12;

function TablePagination({
  page,
  setPage,
  totalRows,
  perPage,
  noun,
}: {
  page: number;
  setPage: (fn: (p: number) => number) => void;
  totalRows: number;
  perPage: number;
  noun: string;
}) {
  if (totalRows <= 0) return null;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 pt-3">
      <p className="text-xs text-muted-foreground">
        Showing {start + 1}–{Math.min(start + perPage, totalRows)} of {totalRows} {noun}
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
  );
}

export function InventoryRequestsPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";
  const isPm = user?.role === "prod_manager";
  const isHr = user?.role === "hr";
  const readonly = isHr;
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();
  const { refresh: refreshNotifications, markViewed, markMaterialViewed } = useCorrectionNotifications();
  const [requests, setRequests] = useState<CorrectionRequestDetail[]>([]);
  const [materialRequests, setMaterialRequests] = useState<MaterialAdjustmentRequest[]>([]);
  const [materialError, setMaterialError] = useState<string | null>(null);
  const [actingPackagingId, setActingPackagingId] = useState<number | null>(null);
  const [supplyPage, setSupplyPage] = useState(1);
  const [packagingPage, setPackagingPage] = useState(1);
  const [guideOpen, setGuideOpen] = useState(false);
  const [supplyReviewRequest, setSupplyReviewRequest] = useState<MaterialAdjustmentRequest | null>(null);
  const [discountQueue, setDiscountQueue] = useState<Array<{
    id: number;
    request_no: string;
    buyer_name: string;
    discount_percent: number;
    subtotal_amount: number;
    discount_amount: number;
    total_amount: number;
    item_count: number;
    cashier_label: string;
    created_at: string;
  }>>([]);
  const [actingDiscountId, setActingDiscountId] = useState<number | null>(null);
  const [discountError, setDiscountError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [showNew, setShowNew] = useState(false);
  const [showPackagingRequest, setShowPackagingRequest] = useState(false);
  const [showSupplyIssueRequest, setShowSupplyIssueRequest] = useState(false);
  const [reviewId, setReviewId] = useState<number | null>(null);
  const [correctionPage, setCorrectionPage] = useState(1);
  const [discountPage, setDiscountPage] = useState(1);
  const markedViewedRef = useRef<Set<number>>(new Set());
  const markedMaterialViewedRef = useRef<Set<number>>(new Set());
  const sectionsInitRef = useRef(false);
  const [sectionOpen, setSectionOpen] = useState<Record<string, boolean>>({});

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const [data, matData] = await Promise.all([
        apiGet<{ requests: CorrectionRequestDetail[] }>("honey.php", "correction_list"),
        listMaterialRequests(),
      ]);
      setRequests(data.requests);
      setMaterialRequests(matData.requests);
      setCorrectionPage(1);
      setDiscountPage(1);
      setSupplyPage(1);
      setPackagingPage(1);
      if (isExec) {
        const discountData = await apiGet<{ rows: typeof discountQueue }>("pos_discounts.php", "list", { status: "pending", limit: "50" });
        setDiscountQueue(discountData.rows);
      } else {
        setDiscountQueue([]);
      }
    } finally {
      setLoading(false);
    }
  }, [isExec]);

  useEffect(() => {
    refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const markViewedOnce = useCallback(
    async (id: number) => {
      if (!isExec || markedViewedRef.current.has(id)) return;
      markedViewedRef.current.add(id);
      await markViewed(id);
    },
    [isExec, markViewed],
  );

  const openReview = useCallback(
    (id: number) => {
      setReviewId(id);
      setSearchParams({ request: String(id) }, { replace: true });
      void markViewedOnce(id);
    },
    [markViewedOnce, setSearchParams],
  );

  const requestParam = searchParams.get("request");
  const requestIdFromUrl = requestParam ? parseInt(requestParam, 10) : 0;

  useEffect(() => {
    if (requestIdFromUrl < 1) {
      setReviewId(null);
      return;
    }
    if (requests.length === 0) return;
    setReviewId(requestIdFromUrl);
  }, [requestIdFromUrl, requests.length]);

  useEffect(() => {
    if (requestIdFromUrl < 1 || requests.length === 0 || !isExec) return;
    const req = requests.find(r => r.id === requestIdFromUrl);
    if (req?.executive_viewed_at || markedViewedRef.current.has(requestIdFromUrl)) return;
    void markViewedOnce(requestIdFromUrl);
  }, [requestIdFromUrl, requests.length, isExec, markViewedOnce, requests]);

  const closeReview = () => {
    setReviewId(null);
    setSearchParams({}, { replace: true });
  };

  const reviewRequest = reviewId ? requests.find(r => r.id === reviewId) : null;

  const handleReviewDone = async () => {
    closeReview();
    await refresh();
    await refreshNotifications();
  };

  const approveDiscount = async (id: number) => {
    if (!confirm("Approve this discount request?")) return;
    setActingDiscountId(id);
    setDiscountError(null);
    try {
      await apiPost("pos_discounts.php", "approve", { id });
      await refresh();
      await refreshNotifications();
    } catch (e) {
      setDiscountError(e instanceof ApiError ? e.message : "Could not approve request.");
    } finally {
      setActingDiscountId(null);
    }
  };

  const declineDiscount = async (id: number) => {
    const note = prompt("Reason for decline (optional):") ?? "";
    setActingDiscountId(id);
    setDiscountError(null);
    try {
      await apiPost("pos_discounts.php", "decline", { id, note });
      await refresh();
      await refreshNotifications();
    } catch (e) {
      setDiscountError(e instanceof ApiError ? e.message : "Could not decline request.");
    } finally {
      setActingDiscountId(null);
    }
  };

  const correctionTotal = requests.length;
  const correctionTotalPages = Math.max(1, Math.ceil(correctionTotal / ROWS_PER_PAGE));
  const correctionSafePage = Math.min(correctionPage, correctionTotalPages);
  const correctionStart = (correctionSafePage - 1) * ROWS_PER_PAGE;
  const pagedRequests = requests.slice(correctionStart, correctionStart + ROWS_PER_PAGE);

  const discountTotal = discountQueue.length;
  const discountTotalPages = Math.max(1, Math.ceil(discountTotal / ROWS_PER_PAGE));
  const discountSafePage = Math.min(discountPage, discountTotalPages);
  const discountStart = (discountSafePage - 1) * ROWS_PER_PAGE;
  const pagedDiscounts = discountQueue.slice(discountStart, discountStart + ROWS_PER_PAGE);

  const supplyRequests = useMemo(
    () => materialRequests.filter(r => r.category === "supply" && r.action === "issue"),
    [materialRequests],
  );
  const packagingRequests = useMemo(
    () => materialRequests.filter(r => r.category === "packaging" && r.action === "set_on_hand"),
    [materialRequests],
  );

  const supplyTotal = supplyRequests.length;
  const supplyTotalPages = Math.max(1, Math.ceil(supplyTotal / ROWS_PER_PAGE));
  const supplySafePage = Math.min(supplyPage, supplyTotalPages);
  const supplyStart = (supplySafePage - 1) * ROWS_PER_PAGE;
  const pagedSupplyRequests = supplyRequests.slice(supplyStart, supplyStart + ROWS_PER_PAGE);

  const packagingTotal = packagingRequests.length;
  const packagingTotalPages = Math.max(1, Math.ceil(packagingTotal / ROWS_PER_PAGE));
  const packagingSafePage = Math.min(packagingPage, packagingTotalPages);
  const packagingStart = (packagingSafePage - 1) * ROWS_PER_PAGE;
  const pagedPackagingRequests = packagingRequests.slice(packagingStart, packagingStart + ROWS_PER_PAGE);

  const correctionPending = useMemo(
    () => requests.filter(r => r.status === "pending").length,
    [requests],
  );
  const supplyPending = useMemo(
    () => supplyRequests.filter(r => r.status === "pending").length,
    [supplyRequests],
  );
  const packagingPending = useMemo(
    () => packagingRequests.filter(r => r.status === "pending").length,
    [packagingRequests],
  );

  const setSection = useCallback((id: string, open: boolean) => {
    setSectionOpen(prev => ({ ...prev, [id]: open }));
  }, []);

  useEffect(() => {
    if (loading || sectionsInitRef.current) return;
    sectionsInitRef.current = true;
    const hash = location.hash.replace("#", "");
    setSectionOpen({
      "inventory-corrections-section":
        hash === "inventory-corrections-section" || (!isHr && correctionPending > 0),
      "supply-requests-section":
        hash === "supply-requests-section" || supplyPending > 0 || (isHr && supplyTotal > 0),
      "packaging-requests-section":
        hash === "packaging-requests-section" || packagingPending > 0,
      "edit-requests-section": hash === "edit-requests-section",
      "discount-approvals-section":
        hash === "discount-approvals-section" || discountTotal > 0,
    });
  }, [
    loading,
    isHr,
    correctionPending,
    supplyPending,
    packagingPending,
    supplyTotal,
    discountTotal,
    location.hash,
  ]);

  useEffect(() => {
    const hash = location.hash.replace("#", "");
    if (!hash) return;
    setSectionOpen(prev => (prev[hash] ? prev : { ...prev, [hash]: true }));
    const el = document.getElementById(hash);
    if (el) {
      window.requestAnimationFrame(() => el.scrollIntoView({ behavior: "smooth", block: "start" }));
    }
  }, [location.hash]);

  const markMaterialViewedOnce = useCallback(
    async (id: number) => {
      if (!isExec || markedMaterialViewedRef.current.has(id)) return;
      markedMaterialViewedRef.current.add(id);
      await markMaterialViewed(id);
    },
    [isExec, markMaterialViewed],
  );

  const openSupplyReview = useCallback(
    (request: MaterialAdjustmentRequest) => {
      setSupplyReviewRequest(request);
      setSearchParams({ supply_request: String(request.id) }, { replace: true });
      if (request.status === "pending") {
        void markMaterialViewedOnce(request.id);
      }
    },
    [markMaterialViewedOnce, setSearchParams],
  );

  const closeSupplyReview = () => {
    setSupplyReviewRequest(null);
    const next = new URLSearchParams(searchParams);
    next.delete("supply_request");
    setSearchParams(next, { replace: true });
  };

  const supplyRequestParam = searchParams.get("supply_request");
  const supplyRequestIdFromUrl = supplyRequestParam ? parseInt(supplyRequestParam, 10) : 0;

  useEffect(() => {
    if (supplyRequestIdFromUrl < 1) {
      setSupplyReviewRequest(null);
      return;
    }
    setSectionOpen(prev => ({ ...prev, "supply-requests-section": true }));
    if (supplyRequests.length === 0) return;
    const req = supplyRequests.find(r => r.id === supplyRequestIdFromUrl);
    if (req) {
      setSupplyReviewRequest(req);
      if (req.status === "pending") void markMaterialViewedOnce(req.id);
    }
  }, [supplyRequestIdFromUrl, supplyRequests, markMaterialViewedOnce]);

  const approvePackaging = async (id: number) => {
    if (!confirm("Approve this packaging count request?")) return;
    setActingPackagingId(id);
    setMaterialError(null);
    try {
      await approveMaterialRequest(id);
      await refresh();
      await refreshNotifications();
    } catch (e) {
      setMaterialError(e instanceof ApiError ? e.message : "Could not approve request.");
    } finally {
      setActingPackagingId(null);
    }
  };

  const rejectPackaging = async (id: number) => {
    const note = prompt("Rejection note (required):") ?? "";
    if (!note.trim()) return;
    setActingPackagingId(id);
    setMaterialError(null);
    try {
      await rejectMaterialRequest(id, note.trim());
      await refresh();
      await refreshNotifications();
    } catch (e) {
      setMaterialError(e instanceof ApiError ? e.message : "Could not reject request.");
    } finally {
      setActingPackagingId(null);
    }
  };

  const colSpan = isExec && !readonly ? 6 : 5;
  const supplyColSpan = isExec && !readonly ? 7 : 6;
  const packagingColSpan = isExec && !readonly ? 6 : 5;

  const showEditRequests = isExec || isPm;

  const pageTitle = "Requests";
  const pageSubtitle = isExec
    ? "Review inventory corrections, edit requests (deliveries + more), and POS discount approvals"
    : isPm
      ? "Inventory corrections and your delivery edit/delete requests"
      : isHr
        ? "Submit packaging count corrections, supply issues for staff, and track approval status"
        : "Inventory correction requests you have submitted";

  return (
    <PageLayout
      title={pageTitle}
      subtitle={pageSubtitle}
      actions={
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setGuideOpen(true)}
            className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
          >
            <BookOpen size={13} /> Guide
          </button>
          {isPm && (
            <button
              onClick={() => setShowNew(true)}
              className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-1.5 rounded-lg"
              style={{ background: "#C8900A" }}
            >
              <Plus size={13} /> New request
            </button>
          )}
        </div>
      }
    >
      <div className="space-y-3">
        {!isHr && (
        <RequestsHubSection
          id="inventory-corrections-section"
          title="Inventory corrections"
          subtitle="Honey warehouse / production / packaging qty and metadata fixes"
          pendingCount={correctionPending}
          totalCount={correctionTotal}
          open={sectionOpen["inventory-corrections-section"]}
          onOpenChange={open => setSection("inventory-corrections-section", open)}
        >
          <div className="overflow-x-auto rounded-lg border border-border">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
                  {["Date", "Stage", "Change", "Reason", "Status", isExec && !readonly ? "Review" : ""].filter(Boolean).map(h => (
                    <th key={h} className="text-left px-4 py-3">
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr>
                    <td colSpan={colSpan} className="py-8 text-center">
                      <Loader2 className="inline animate-spin" size={14} />
                    </td>
                  </tr>
                )}
                {!loading && pagedRequests.length === 0 && (
                  <tr>
                    <td colSpan={colSpan} className="py-8 text-center text-sm text-muted-foreground">
                      No correction requests yet.
                    </td>
                  </tr>
                )}
                {!loading &&
                  pagedRequests.map(r => (
                    <tr
                      key={r.id}
                      className={`border-b cursor-pointer hover:bg-secondary/30 ${
                        r.status === "pending"
                          ? "border-l-4 border-l-amber-400"
                          : r.status === "approved"
                            ? "border-l-4 border-l-green-500"
                            : "border-l-4 border-l-red-400"
                      }`}
                      onClick={() => openReview(r.id)}
                    >
                      <td className="px-4 py-3 text-xs">{r.created_at}</td>
                      <td className="px-4 py-3 capitalize">{r.stage}</td>
                      <td className="px-4 py-3">{formatRequestLine(r)}</td>
                      <td className="px-4 py-3 max-w-xs truncate">{r.reason}</td>
                      <td className="px-4 py-3 capitalize">{r.status}</td>
                      {isExec && !readonly && (
                        <td className="px-4 py-3" onClick={e => e.stopPropagation()}>
                          {r.status === "pending" ? (
                            <button type="button" onClick={() => openReview(r.id)} className="text-xs text-[#C8900A] font-semibold">
                              Review
                            </button>
                          ) : (
                            <span className="text-xs text-muted-foreground">{r.reviewed_by_name ?? "—"}</span>
                          )}
                        </td>
                      )}
                    </tr>
                  ))}
              </tbody>
            </table>
          </div>
          {!loading && (
            <TablePagination
              page={correctionPage}
              setPage={setCorrectionPage}
              totalRows={correctionTotal}
              perPage={ROWS_PER_PAGE}
              noun="requests"
            />
          )}
        </RequestsHubSection>
        )}

        <RequestsHubSection
          id="supply-requests-section"
          title="Supply issue requests"
          subtitle={
            isHr
              ? "Submit deduct-for-use requests with requester name — executive approves before on-hand changes"
              : "HR supply issues awaiting executive approval"
          }
          pendingCount={supplyPending}
          totalCount={supplyTotal}
          open={sectionOpen["supply-requests-section"]}
          onOpenChange={open => setSection("supply-requests-section", open)}
          headerActions={
            isHr ? (
              <button
                type="button"
                onClick={() => setShowSupplyIssueRequest(true)}
                className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-1.5 rounded-lg shrink-0"
                style={{ background: "#C8900A" }}
              >
                <Plus size={13} /> Supply issue request
              </button>
            ) : undefined
          }
        >
          {materialError && (
            <div className="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700">{materialError}</div>
          )}
          <div className="overflow-x-auto rounded-lg border border-border">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
                  {["Date", "Item", "Qty", "Requester", "Reason", "Status", isExec && !readonly ? "Review" : ""].filter(Boolean).map(h => (
                    <th key={h} className="text-left px-4 py-3">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr>
                    <td colSpan={supplyColSpan} className="py-8 text-center">
                      <Loader2 className="inline animate-spin" size={14} />
                    </td>
                  </tr>
                )}
                {!loading && pagedSupplyRequests.length === 0 && (
                  <tr>
                    <td colSpan={supplyColSpan} className="py-8 text-center text-sm text-muted-foreground">
                      No supply issue requests yet.
                    </td>
                  </tr>
                )}
                {!loading && pagedSupplyRequests.map(r => (
                  <tr
                    key={r.id}
                    className={`border-b ${
                      isExec && r.status === "pending" ? "cursor-pointer hover:bg-secondary/30 border-l-4 border-l-amber-400" : ""
                    }`}
                    onClick={() => isExec && openSupplyReview(r)}
                  >
                    <td className="px-4 py-3 text-xs">{r.created_at}</td>
                    <td className="px-4 py-3">{r.stock_name}</td>
                    <td className="px-4 py-3">{r.amount} {r.unit}</td>
                    <td className="px-4 py-3 text-xs">{r.requester_name ?? "—"}</td>
                    <td className="px-4 py-3 max-w-xs truncate">{r.reason}</td>
                    <td className="px-4 py-3 capitalize">{r.status}</td>
                    {isExec && !readonly && (
                      <td className="px-4 py-3" onClick={e => e.stopPropagation()}>
                        {r.status === "pending" ? (
                          <button
                            type="button"
                            onClick={() => openSupplyReview(r)}
                            className="text-xs text-[#C8900A] font-semibold"
                          >
                            Review
                          </button>
                        ) : (
                          <span className="text-xs text-muted-foreground">{r.reviewed_by_name ?? "—"}</span>
                        )}
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {!loading && (
            <TablePagination
              page={supplyPage}
              setPage={setSupplyPage}
              totalRows={supplyTotal}
              perPage={ROWS_PER_PAGE}
              noun="requests"
            />
          )}
        </RequestsHubSection>

        <RequestsHubSection
          id="packaging-requests-section"
          title="Packaging count requests"
          subtitle="Packaging on-hand corrections submitted by HR — executive approves"
          pendingCount={packagingPending}
          totalCount={packagingTotal}
          open={sectionOpen["packaging-requests-section"]}
          onOpenChange={open => setSection("packaging-requests-section", open)}
          headerActions={
            isHr ? (
              <button
                type="button"
                onClick={() => setShowPackagingRequest(true)}
                className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary/50 shrink-0"
              >
                <Plus size={13} /> Packaging count
              </button>
            ) : undefined
          }
        >
          <div className="overflow-x-auto rounded-lg border border-border">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
                  {["Date", "Item", "Change", "Reason", "Status", isExec && !readonly ? "Review" : ""].filter(Boolean).map(h => (
                    <th key={h} className="text-left px-4 py-3">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr>
                    <td colSpan={packagingColSpan} className="py-8 text-center">
                      <Loader2 className="inline animate-spin" size={14} />
                    </td>
                  </tr>
                )}
                {!loading && pagedPackagingRequests.length === 0 && (
                  <tr>
                    <td colSpan={packagingColSpan} className="py-8 text-center text-sm text-muted-foreground">
                      No packaging count requests yet.
                    </td>
                  </tr>
                )}
                {!loading && pagedPackagingRequests.map(r => (
                  <tr key={r.id} className="border-b">
                    <td className="px-4 py-3 text-xs">{r.created_at}</td>
                    <td className="px-4 py-3">{r.stock_name}</td>
                    <td className="px-4 py-3">{r.summary ?? `Set on-hand → ${r.amount} ${r.unit}`}</td>
                    <td className="px-4 py-3 max-w-xs truncate">{r.reason}</td>
                    <td className="px-4 py-3 capitalize">{r.status}</td>
                    {isExec && !readonly && (
                      <td className="px-4 py-3">
                        {r.status === "pending" ? (
                          <div className="flex gap-2">
                            <button
                              type="button"
                              disabled={actingPackagingId === r.id}
                              onClick={() => void approvePackaging(r.id)}
                              className="text-xs font-semibold text-green-700"
                            >
                              Approve
                            </button>
                            <button
                              type="button"
                              disabled={actingPackagingId === r.id}
                              onClick={() => void rejectPackaging(r.id)}
                              className="text-xs font-semibold text-red-600"
                            >
                              Reject
                            </button>
                          </div>
                        ) : (
                          <span className="text-xs text-muted-foreground">{r.reviewed_by_name ?? "—"}</span>
                        )}
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {!loading && (
            <TablePagination
              page={packagingPage}
              setPage={setPackagingPage}
              totalRows={packagingTotal}
              perPage={ROWS_PER_PAGE}
              noun="requests"
            />
          )}
        </RequestsHubSection>

        {showEditRequests && (
          <EditRequestsSection
            isReviewer={isExec}
            readonly={readonly}
            onChanged={() => void refreshNotifications()}
            open={sectionOpen["edit-requests-section"]}
            onOpenChange={open => setSection("edit-requests-section", open)}
          />
        )}

        {isExec && (
          <RequestsHubSection
            id="discount-approvals-section"
            title="POS discount approvals"
            subtitle="POS cart discounts awaiting executive approval"
            pendingCount={discountTotal}
            open={sectionOpen["discount-approvals-section"]}
            onOpenChange={open => setSection("discount-approvals-section", open)}
          >
            <div className="rounded-lg border border-border overflow-hidden">
              {discountError && (
                <div className="px-4 py-2 text-xs text-red-700 bg-red-50 border-b border-red-200">{discountError}</div>
              )}
              {loading ? (
                <div className="py-6 text-center text-muted-foreground">
                  <Loader2 className="inline animate-spin" size={14} />
                </div>
              ) : discountQueue.length === 0 ? (
                <p className="px-4 py-6 text-sm text-muted-foreground">No pending discount approvals.</p>
              ) : (
                <div className="divide-y divide-border">
                  {pagedDiscounts.map(row => (
                    <div key={row.id} className="px-4 py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <p className="text-sm font-medium">
                          {row.request_no} · {row.discount_percent}% off · {row.buyer_name}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          Cashier: {row.cashier_label} · {row.item_count} item{row.item_count === 1 ? "" : "s"} ·
                          Subtotal ₱{row.subtotal_amount.toFixed(2)} → Total ₱{row.total_amount.toFixed(2)}
                        </p>
                      </div>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          disabled={actingDiscountId === row.id}
                          onClick={() => void approveDiscount(row.id)}
                          className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-green-300 bg-green-50 text-green-700 disabled:opacity-60"
                        >
                          <CheckCircle2 size={14} /> Approve
                        </button>
                        <button
                          type="button"
                          disabled={actingDiscountId === row.id}
                          onClick={() => void declineDiscount(row.id)}
                          className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-300 bg-red-50 text-red-700 disabled:opacity-60"
                        >
                          <XCircle size={14} /> Decline
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
            {!loading && (
              <TablePagination
                page={discountPage}
                setPage={setDiscountPage}
                totalRows={discountTotal}
                perPage={ROWS_PER_PAGE}
                noun="approvals"
              />
            )}
          </RequestsHubSection>
        )}
      </div>
      {showNew && <NewRequestModal onClose={() => setShowNew(false)} onDone={() => { setShowNew(false); refresh(); }} />}
      {showPackagingRequest && (
        <PackagingOnHandRequestModal
          onClose={() => setShowPackagingRequest(false)}
          onDone={() => { setShowPackagingRequest(false); void refresh(); }}
        />
      )}
      {showSupplyIssueRequest && (
        <SupplyIssueRequestModal
          onClose={() => setShowSupplyIssueRequest(false)}
          onDone={() => {
            setShowSupplyIssueRequest(false);
            void refresh();
            void refreshNotifications();
          }}
        />
      )}
      {reviewRequest && (
        <CorrectionReviewModal request={reviewRequest} onClose={closeReview} onDone={handleReviewDone} />
      )}
      {supplyReviewRequest && (
        <SupplyRequestReviewModal
          request={supplyReviewRequest}
          onClose={closeSupplyReview}
          onDone={async () => {
            closeSupplyReview();
            await refresh();
            await refreshNotifications();
          }}
        />
      )}
      <InventoryRequestsGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        role={user?.role}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runInventoryRequestsTour(user?.role));
        }}
      />
    </PageLayout>
  );
}

function NewRequestModal({ onClose, onDone }: { onClose: () => void; onDone: () => void }) {
  const [stage, setStage] = useState("warehouse");
  const [options, setOptions] = useState<{ id: number; label: string; row_table: string }[]>([]);
  const [rowId, setRowId] = useState("");
  const [rowTable, setRowTable] = useState("");
  const [correctionType, setCorrectionType] = useState<CorrectionType>("qty_delta");
  const [action, setAction] = useState<"add" | "deduct">("deduct");
  const [amount, setAmount] = useState("");
  const [setQty, setSetQty] = useState("");
  const [variant, setVariant] = useState("light");
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [packSize, setPackSize] = useState("250ml");
  const [packQty, setPackQty] = useState("");
  const [reason, setReason] = useState("");

  useEffect(() => {
    apiGet<{ options: typeof options }>("honey.php", "correction_row_options", { stage }).then(d => {
      setOptions(d.options);
      setRowId("");
    });
  }, [stage]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    const opt = options.find(o => String(o.id) === rowId);
    const payload: Record<string, unknown> = {
      stage,
      row_table: opt?.row_table || rowTable,
      row_id: parseInt(rowId, 10),
      correction_type: correctionType,
      reason,
    };
    if (correctionType === "qty_delta") {
      payload.action = action;
      payload.amount = parseFloat(amount);
    } else if (correctionType === "set_qty") {
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = setQty;
    } else if (correctionType === "variant") {
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = variant;
    } else if (correctionType === "supplier") {
      if (!supplier) return;
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = String(supplier.id);
    } else if (correctionType === "packaging_size") {
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = `${packSize}:${packQty}`;
      payload.size = packSize;
    }
    await apiPost("honey.php", "correction_submit", payload);
    onDone();
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="new-correction-modal-title">
      <AppModalHeader title="New correction request" titleId="new-correction-modal-title" onClose={onClose} />
      <AppModalBody>
        <form onSubmit={submit} className="space-y-3 text-sm">
          <select value={stage} onChange={e => setStage(e.target.value)} className="w-full border rounded-lg px-3 py-2">
            {["warehouse", "production", "packaging"].map(s => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
          <select
            value={rowId}
            onChange={e => {
              setRowId(e.target.value);
              const o = options.find(x => String(x.id) === e.target.value);
              if (o) setRowTable(o.row_table);
            }}
            className="w-full border rounded-lg px-3 py-2"
            required
          >
            <option value="">Select row…</option>
            {options.map(o => (
              <option key={o.id} value={o.id}>
                {o.label}
              </option>
            ))}
          </select>
          <select value={correctionType} onChange={e => setCorrectionType(e.target.value as CorrectionType)} className="w-full border rounded-lg px-3 py-2">
            <option value="qty_delta">Quantity — add or deduct</option>
            <option value="set_qty">Quantity — set to exact value</option>
            <option value="variant">Change variant</option>
            {stage === "warehouse" && <option value="supplier">Change supplier</option>}
            {stage === "packaging" && <option value="packaging_size">Set packaging size qty</option>}
          </select>

          {correctionType === "qty_delta" && (
            <>
              <select value={action} onChange={e => setAction(e.target.value as "add" | "deduct")} className="w-full border rounded-lg px-3 py-2">
                <option value="add">Add</option>
                <option value="deduct">Deduct</option>
              </select>
              <input type="number" step="0.01" value={amount} onChange={e => setAmount(e.target.value)} placeholder="Amount" className="w-full border rounded-lg px-3 py-2" required />
              {stage === "packaging" && (
                <select value={packSize} onChange={e => setPackSize(e.target.value)} className="w-full border rounded-lg px-3 py-2">
                  {HONEY_SIZES.map(s => (
                    <option key={s} value={s}>
                      {s}
                    </option>
                  ))}
                </select>
              )}
            </>
          )}
          {correctionType === "set_qty" && (
            <>
              <input type="number" step="0.01" value={setQty} onChange={e => setSetQty(e.target.value)} placeholder="New quantity" className="w-full border rounded-lg px-3 py-2" required />
              <p className="text-xs text-muted-foreground">
                Sets both current and initial to this value (full reset). Use add/deduct to change only what is on hand.
              </p>
            </>
          )}
          {correctionType === "variant" && (
            <select value={variant} onChange={e => setVariant(e.target.value)} className="w-full border rounded-lg px-3 py-2">
              {HONEY_VARIANTS.map(v => (
                <option key={v} value={v}>
                  {v}
                </option>
              ))}
            </select>
          )}
          {correctionType === "supplier" && (
            <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
          )}
          {correctionType === "packaging_size" && (
            <div className="flex gap-2">
              <select value={packSize} onChange={e => setPackSize(e.target.value)} className="border rounded-lg px-2 py-2 flex-1">
                {HONEY_SIZES.map(s => (
                  <option key={s} value={s}>
                    {s}
                  </option>
                ))}
              </select>
              <input type="number" step="0.01" value={packQty} onChange={e => setPackQty(e.target.value)} placeholder="Qty" className="border rounded-lg px-2 py-2 w-24" required />
            </div>
          )}

          <textarea value={reason} onChange={e => setReason(e.target.value)} placeholder="Reason (required)" className="w-full border rounded-lg px-3 py-2" rows={3} required />
          <button type="submit" className="w-full py-2.5 rounded-lg font-bold text-white" style={{ background: "#C8900A" }}>
            Submit request
          </button>
        </form>
      </AppModalBody>
    </AppModal>
  );
}
