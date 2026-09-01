import { useCallback, useEffect, useId, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router";
import { AnimatePresence } from "motion/react";
import { AlertCircle, Camera, Loader2, Trash2 } from "lucide-react";
import { POSHeader } from "@/app/components/pos/POSHeader";
import { POSGuideModal } from "@/app/components/pos/POSGuideModal";
import { POSReceiptView } from "@/app/components/pos/POSReceiptView";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import { runPosProductTour } from "@/app/components/tour/guides/posTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import type { HoneyCheckoutResult } from "@/app/lib/posHoney";
import {
  ActionFeedbackButton,
  FeedbackBurst,
  POSModal,
  POSPageTransition,
  CartLineMotion,
} from "@/app/components/pos/posMotion";
import { POSARCheckoutPanel, type POSARCheckoutResult } from "@/app/components/pos/POSARCheckoutPanel";
import {
  checkoutIdentityReady,
  POSCheckoutIdentityFields,
} from "@/app/components/pos/POSCheckoutIdentityFields";
import { POSStockSourceModal, type PosStockSource } from "@/app/components/pos/POSStockSourceModal";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { loadPosCatalogCached, readPosCache } from "@/app/lib/posCache";
import { deptPouchBarcodesApi } from "@/app/lib/deptPouchBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import {
  POS_DEPT_LABELS,
  posDeptProductKeyFromRoute,
  peso,
  type DeptCartLine,
  type DeptCheckoutResult,
  type DeptDiscountRequestResult,
  type DeptDiscountRequestStatus,
  type DeptPosProduct,
} from "@/app/lib/posDept";

export function POSDeptPage() {
  const { deptKey: routeKey = "" } = useParams<{ deptKey: string }>();
  const productKey = posDeptProductKeyFromRoute(routeKey) ?? "";
  const title = POS_DEPT_LABELS[productKey] ?? "Product";
  const navigate = useNavigate();
  const { logout } = useAuth();
  const cameraTitleId = useId();

  useEffect(() => {
    if (routeKey && !productKey) {
      navigate("/pos", { replace: true });
    }
  }, [routeKey, productKey, navigate]);

  const [product, setProduct] = useState<DeptPosProduct | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [scanCode, setScanCode] = useState("");
  const [cameraOpen, setCameraOpen] = useState(false);
  const { inputRef: scanRef, refocus, inputProps } = useScanWedgeField(cameraOpen, productKey);
  const [cart, setCart] = useState<DeptCartLine[]>([]);
  const [showCheckout, setShowCheckout] = useState(false);
  const [showStockSource, setShowStockSource] = useState(false);
  const [showARCheckout, setShowARCheckout] = useState(false);
  const [arNotice, setArNotice] = useState<string | null>(null);
  const [checkingOut, setCheckingOut] = useState(false);
  const [requestingDiscount, setRequestingDiscount] = useState<number | null>(null);
  const [pendingRequest, setPendingRequest] = useState<DeptDiscountRequestResult | null>(null);
  const [buyerName, setBuyerName] = useState("");
  const [sellerName, setSellerName] = useState("");
  const [buyerCreds, setBuyerCreds] = useState("");
  const [receipt, setReceipt] = useState<HoneyCheckoutResult | null>(null);
  const [feedback, setFeedback] = useState<"success" | "error" | null>(null);
  const [cartPulse, setCartPulse] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);

  const loadCatalog = useCallback(async () => {
    if (!productKey) return;
    setError(null);
    if (readPosCache("dept_catalog") === null) {
      setLoading(true);
    }
    let showedCache = false;
    try {
      await loadPosCatalogCached(
        "dept_catalog",
        () => apiGet<{ products: DeptPosProduct[] }>("pos.php", "dept_catalog"),
        (data, fromCache) => {
          const match = data.products.find(p => p.key === productKey) ?? null;
          setProduct(match);
          if (!match) {
            setError("Product not found in catalog.");
          }
          if (fromCache) {
            showedCache = true;
            setLoading(false);
          }
        },
      );
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load catalog.");
    } finally {
      if (!showedCache) setLoading(false);
    }
  }, [productKey]);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  const unitPrice = product?.selling_price ?? 0;
  const sellable = product?.sellable ?? false;
  const subtotal = cart.reduce((s, l) => s + l.unit_price * l.qty, 0);
  const itemCount = cart.reduce((s, l) => s + l.qty, 0);

  const removeLine = (key: string) => setCart(prev => prev.filter(l => l.key !== key));

  const handleARSuccess = (result: POSARCheckoutResult) => {
    setShowARCheckout(false);
    setShowCheckout(false);
    setCart([]);
    setBuyerName("");
    setSellerName("");
    setBuyerCreds("");
    setArNotice(`AR recorded ${result.receipt_no} for ${result.debtor_name}`);
    setFeedback("success");
    void loadCatalog();
    window.setTimeout(() => setArNotice(null), 4000);
  };

  const arLines = useMemo(
    () =>
      cart.map(l => ({
        key: l.key,
        label: l.label,
        qty: l.qty,
        unitPrice: l.unit_price,
        productId: l.product_id,
        productKey: l.product_key,
        pouchUnitIds: l.pouch_unit_ids,
      })),
    [cart],
  );

  const cartPayload = cart.map(l => ({
    product_key: l.product_key,
    product_id: l.product_id,
    qty: l.qty,
    pouch_unit_ids: l.pouch_unit_ids ?? [],
  }));

  const mapReceipt = (result: DeptCheckoutResult): HoneyCheckoutResult => ({
    sale_id: result.sale_id,
    receipt_no: result.receipt_no,
    subtotal: result.subtotal,
    discount_percent: result.discount_percent,
    discount_amount: result.discount_amount,
    total: result.total,
    buyer_name: result.buyer_name,
    seller_name: result.seller_name,
    items: result.items.map(i => ({
      product_id: i.product_id,
      qty: i.qty,
      list_unit_price: i.list_unit_price,
      unit_price: i.unit_price,
      line_subtotal: i.line_subtotal,
      line_total: i.line_total,
      name: i.name,
    })),
  });

  const handleExit = async () => {
    await logout();
    navigate("/", { replace: true });
  };

  const addScannedUnit = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? scanCode).trim();
    if (!value) return;
    setScanCode("");
    setError(null);
    if (!product || !sellable || product.product_id == null) {
      const reason = "Product is not sellable yet.";
      playScanBeep("bad");
      setError(reason);
      setFeedback("error");
      return { kind: "error", message: reason };
    }
    try {
      const res = await deptPouchBarcodesApi.posLookup(value);
      const barcode = res.barcode;
      if (barcode.product_key !== productKey) {
        const reason = `This sticker is ${barcode.product_label}, not ${title}.`;
        playScanBeep("bad");
        setError(reason);
        setFeedback("error");
        return { kind: "error", message: reason };
      }
      if (cart.some(l => l.pouch_unit_ids?.includes(barcode.unit_id))) {
        const reason = "That sticker is already in the cart.";
        playScanBeep("dup");
        setError(reason);
        setFeedback("error");
        return { kind: "duplicate", message: reason };
      }
      const key = `u${barcode.unit_id}`;
      setCart(prev => [
        ...prev,
        {
          key,
          product_key: productKey,
          product_id: product.product_id!,
          label: product.label,
          qty: 1,
          unit_price: unitPrice,
          pouch_unit_ids: [barcode.unit_id],
          code: barcode.code,
          batch_no: barcode.batch_no,
        },
      ]);
      setCartPulse(true);
      setTimeout(() => setCartPulse(false), 500);
      playScanBeep("ok");
      setFeedback("success");
      refocus();
      return { kind: "success", message: `Added ${barcode.code}` };
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      const isDup = /already|duplicate/i.test(reason);
      playScanBeep(isDup ? "dup" : "bad");
      setError(reason);
      setFeedback("error");
      refocus();
      return { kind: isDup ? "duplicate" : "error", message: reason };
    }
  };

  const checkout = async (stockSource: PosStockSource) => {
    if (cart.length === 0) return;
    if (stockSource === "rts") {
      setError(
        "RTS walk-in requires scanning unit barcodes into the cart. Qty-only RTS is disabled — use Non-RTS for packaging stock, or scan RTS stickers first.",
      );
      setShowStockSource(false);
      return;
    }
    setCheckingOut(true);
    setError(null);
    try {
      const result = await apiPost<DeptCheckoutResult>("pos.php", "dept_checkout", {
        items: cartPayload,
        buyer_name: buyerName.trim(),
        seller_name: sellerName.trim(),
        buyer_credentials: buyerCreds.trim(),
        stock_source: stockSource,
      });
      setShowStockSource(false);
      setShowCheckout(false);
      setFeedback("success");
      setTimeout(() => {
        setFeedback(null);
        setReceipt(mapReceipt(result));
        setCart([]);
    setBuyerName("");
    setSellerName("");
    setBuyerCreds("");
        void loadCatalog();
      }, 650);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Checkout failed.");
      setFeedback("error");
      setTimeout(() => setFeedback(null), 700);
    } finally {
      setCheckingOut(false);
    }
  };

  const submitDiscountRequest = async (discountPercent: 10 | 20) => {
    if (cart.length === 0) return;
    if (!checkoutIdentityReady(buyerName, sellerName)) {
      setError("Customer name and seller are required for discount approval.");
      return;
    }
    setRequestingDiscount(discountPercent);
    setError(null);
    try {
      const result = await apiPost<DeptDiscountRequestResult>("pos.php", "discount_request_create", {
        items: cartPayload,
        buyer_name: buyerName.trim(),
        buyer_credentials: buyerCreds.trim(),
        discount_percent: discountPercent,
      });
      setShowCheckout(false);
      setPendingRequest(result);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not submit discount request.");
    } finally {
      setRequestingDiscount(null);
    }
  };

  useEffect(() => {
    if (!pendingRequest) return;
    let active = true;
    const tick = async () => {
      try {
        const status = await apiGet<DeptDiscountRequestStatus>("pos.php", "discount_request_status", {
          request_id: String(pendingRequest.request_id),
        });
        if (!active) return;
        if (status.status === "approved" && status.receipt) {
          setPendingRequest(null);
          setCart([]);
    setBuyerName("");
    setSellerName("");
    setBuyerCreds("");
          setReceipt(mapReceipt(status.receipt));
          void loadCatalog();
          return;
        }
        if (status.status === "declined") {
          setPendingRequest(null);
          setError(status.decline_note ? `Discount declined: ${status.decline_note}` : "Discount request was declined.");
        }
      } catch {
        // Keep polling unless backend confirms status.
      }
    };
    void tick();
    const timer = window.setInterval(() => {
      void tick();
    }, 3000);
    return () => {
      active = false;
      window.clearInterval(timer);
    };
  }, [pendingRequest, loadCatalog]);

  if (receipt) {
    return (
      <POSReceiptView
        receipt={receipt}
        onNewSale={() => {
          setReceipt(null);
          navigate("/pos", { replace: true });
        }}
      />
    );
  }

  return (
    <POSPageTransition className="min-h-screen bg-background flex flex-col pb-24 [font-family:'DM_Sans',sans-serif]">
      <POSHeader
        title={title}
        subtitle="Walk-in sale — scan stickers"
        onExit={handleExit}
        onBack={() => navigate("/pos")}
        onGuide={() => setGuideOpen(true)}
      />

      <div className="px-4 pt-2">
        <div className="w-full max-w-xl mx-auto flex gap-2">
          <input
            ref={scanRef}
            {...inputProps}
            className="flex-1 border rounded-lg px-3 py-2 text-sm font-mono"
            placeholder="Scan sticker barcode to add to cart"
            value={scanCode}
            onChange={e => setScanCode(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void addScannedUnit();
              }
            }}
            disabled={!sellable}
          />
          <ActionFeedbackButton
            variant="cancel"
            flashOnClick={false}
            onClick={() => setCameraOpen(true)}
            disabled={!sellable}
            className="shrink-0 px-3 py-2 text-sm flex items-center gap-1.5"
          >
            <Camera size={16} /> Camera
          </ActionFeedbackButton>
        </div>
      </div>

      {error && (
        <div className="bg-red-50 border-b border-red-200 px-4 py-2 text-sm text-red-700 flex items-center gap-2">
          <AlertCircle size={14} className="flex-shrink-0" />
          <span>{error}</span>
        </div>
      )}
      {arNotice && (
        <div className="bg-red-50 border-b border-red-200 px-4 py-2 text-sm text-red-700 font-medium text-center">
          {arNotice}
        </div>
      )}

      <section
        data-tour="pos-cart"
        className={`border-b border-border px-4 py-2 transition-colors duration-300 ${cartPulse ? "bg-[#C8900A]/10" : "bg-card"}`}
      >
        <div className="max-w-xs mx-auto text-center">
          <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Cart</p>
          {cart.length === 0 ? (
            <p className="text-xs text-muted-foreground">Scan each sticker — remove if customer changes mind</p>
          ) : (
            <div className="space-y-1 max-h-36 overflow-y-auto">
              <AnimatePresence initial={false}>
                {cart.map(line => (
                  <CartLineMotion key={line.key}>
                    <div className="flex items-center justify-center gap-2 text-xs bg-secondary/40 rounded-lg px-2 py-1.5">
                      <span className="font-medium font-mono">{line.code ?? line.label}</span>
                      <span className="font-semibold text-[#C8900A]">{peso(line.unit_price * line.qty)}</span>
                      <button
                        type="button"
                        onClick={() => removeLine(line.key)}
                        className="text-red-400 hover:text-red-600 p-0.5 transition-colors active:scale-90"
                        aria-label="Remove"
                      >
                        <Trash2 size={12} />
                      </button>
                    </div>
                  </CartLineMotion>
                ))}
              </AnimatePresence>
              <div className="flex justify-center gap-2 pt-0.5 text-xs font-bold">
                <span>Subtotal</span>
                <span style={{ color: "#C8900A" }}>{peso(subtotal)}</span>
              </div>
            </div>
          )}
        </div>
      </section>

      <main className="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 max-w-sm mx-auto w-full">
        {loading && (
          <div className="flex justify-center py-12">
            <Loader2 className="animate-spin text-muted-foreground" size={24} />
          </div>
        )}
        {product && !loading && (
          <div data-tour="pos-product-grid" className="w-full space-y-4 text-center">
            <div className="rounded-2xl border bg-card p-5">
              <p className="text-lg font-bold text-foreground">{product.label}</p>
              <p className="text-sm text-muted-foreground mt-1">
                On hand: <strong>{product.available_qty}</strong> {product.unit}
              </p>
              {sellable ? (
                <p className="text-2xl font-bold mt-3" style={{ color: "#C8900A" }}>
                  {peso(unitPrice)}
                </p>
              ) : (
                <p className="text-xs text-amber-700 mt-3">
                  Not sellable — executive must set selling price in Costing.
                </p>
              )}
              <p className="text-xs text-muted-foreground mt-3">
                Packed or already sold stickers are rejected. Tap trash on a cart line to remove it.
              </p>
            </div>
          </div>
        )}
      </main>

      <div
        data-tour="pos-checkout-bar"
        className="fixed bottom-0 left-0 right-0 z-40 p-4 pt-3 bg-background/95 border-t border-border backdrop-blur-sm"
        style={{ paddingBottom: "max(1rem, env(safe-area-inset-bottom))" }}
      >
        <div className="max-w-sm mx-auto">
          <button
            type="button"
            disabled={cart.length === 0}
            onClick={() => setShowCheckout(true)}
            className="w-full py-3.5 rounded-xl text-sm font-bold text-white disabled:opacity-40 transition-transform active:scale-[0.98]"
            style={{ background: "#C8900A" }}
          >
            Checkout{itemCount > 0 ? ` (${itemCount} item${itemCount === 1 ? "" : "s"})` : ""}
          </button>
        </div>
      </div>

      <POSModal open={showCheckout} onClose={() => setShowCheckout(false)} panelClassName="max-w-sm">
        <div className="bg-card rounded-2xl border w-full p-5 shadow-lg">
          <h3 className="font-bold text-lg mb-4 text-center">Confirm order</h3>
          <div className="space-y-2 mb-4">
            <POSCheckoutIdentityFields
              buyerName={buyerName}
              onBuyerNameChange={setBuyerName}
              sellerName={sellerName}
              onSellerNameChange={setSellerName}
              buyerCreds={buyerCreds}
              onBuyerCredsChange={setBuyerCreds}
              labelClassName="block text-xs uppercase tracking-wide text-muted-foreground font-semibold"
              inputClassName="mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-sm"
            />
          </div>
          <div className="space-y-2 text-sm mb-4 text-center max-h-32 overflow-y-auto">
            {cart.map(line => (
              <div key={line.key} className="flex justify-center gap-2">
                <span className="font-mono text-xs">{line.code}</span>
                <span className="font-semibold">{peso(line.unit_price * line.qty)}</span>
              </div>
            ))}
          </div>
          <div className="flex justify-center gap-2 font-bold text-base border-t pt-3 mb-4">
            <span>TOTAL</span>
            <span style={{ color: "#C8900A" }}>{peso(subtotal)}</span>
          </div>
          <div className="flex gap-2 mb-2">
            <ActionFeedbackButton variant="cancel" onClick={() => setShowCheckout(false)}>
              Cancel
            </ActionFeedbackButton>
            <ActionFeedbackButton
              variant="confirm"
              disabled={checkingOut || !checkoutIdentityReady(buyerName, sellerName)}
              flashOnClick={false}
              onClick={() => {
                setShowCheckout(false);
                setShowStockSource(true);
              }}
              className="font-bold"
            >
              Confirm
            </ActionFeedbackButton>
          </div>
          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              disabled={requestingDiscount !== null || checkingOut || !checkoutIdentityReady(buyerName, sellerName)}
              onClick={() => void submitDiscountRequest(10)}
              className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 disabled:opacity-50 transition-transform active:scale-[0.98]"
            >
              {requestingDiscount === 10 ? "Sending 10%..." : "Request 10% off"}
            </button>
            <button
              type="button"
              disabled={requestingDiscount !== null || checkingOut || !checkoutIdentityReady(buyerName, sellerName)}
              onClick={() => void submitDiscountRequest(20)}
              className="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 disabled:opacity-50 transition-transform active:scale-[0.98]"
            >
              {requestingDiscount === 20 ? "Sending 20%..." : "Request 20% off"}
            </button>
          </div>
          <p className="mt-2 text-[11px] text-muted-foreground text-center">
            Discounted checkouts require executive approval before the receipt is released.
          </p>
          <button
            type="button"
            disabled={checkingOut || requestingDiscount !== null}
            onClick={() => {
              setShowCheckout(false);
              setShowARCheckout(true);
            }}
            className="mt-3 w-full rounded-lg border border-red-400 bg-red-50 px-3 py-2.5 text-xs font-semibold text-red-700 transition-transform active:scale-[0.98] hover:bg-red-100 disabled:opacity-50"
          >
            Accounts Receivable
          </button>
        </div>
      </POSModal>

      <POSStockSourceModal
        open={showStockSource}
        busy={checkingOut}
        onClose={() => setShowStockSource(false)}
        onPick={source => void checkout(source)}
      />

      <POSModal open={showARCheckout} onClose={() => setShowARCheckout(false)} panelClassName="max-w-sm">
        <POSARCheckoutPanel
          mode="dept"
          lines={arLines}
          onCancel={() => setShowARCheckout(false)}
          onSuccess={handleARSuccess}
        />
      </POSModal>

      <POSModal open={pendingRequest !== null} onClose={() => {}} panelClassName="max-w-sm">
        {pendingRequest && (
          <div className="bg-card rounded-2xl border w-full p-5 shadow-lg">
            <h3 className="font-bold text-lg mb-2 text-center">Waiting for approval</h3>
            <p className="text-sm text-muted-foreground text-center">
              Request <span className="font-semibold text-foreground">{pendingRequest.request_no}</span> for{" "}
              <span className="font-semibold text-foreground">{pendingRequest.discount_percent}%</span> discount is
              pending executive review.
            </p>
            <div className="mt-4 text-xs text-muted-foreground flex items-center justify-center gap-2">
              <Loader2 size={14} className="animate-spin" />
              Checking status every 3 seconds...
            </div>
          </div>
        )}
      </POSModal>

      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title={`Scan ${title} barcode`}
          titleId={cameraTitleId}
          continuous
          scanPrompt="Point at each sticker. Packed or sold codes are rejected."
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => addScannedUnit(decoded)}
        />
      ) : null}

      <FeedbackBurst show={feedback === "success"} kind="success" />
      <FeedbackBurst show={feedback === "error"} kind="error" />

      <POSGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        context="product"
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runPosProductTour());
        }}
      />
    </POSPageTransition>
  );
}
