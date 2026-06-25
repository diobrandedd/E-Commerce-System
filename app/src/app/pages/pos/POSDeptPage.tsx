import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router";
import { AnimatePresence } from "motion/react";
import { AlertCircle, Loader2, Minus, Plus, Trash2 } from "lucide-react";
import { POSHeader } from "@/app/components/pos/POSHeader";
import { POSGuideModal } from "@/app/components/pos/POSGuideModal";
import { POSReceiptView } from "@/app/components/pos/POSReceiptView";
import { runPosProductTour } from "@/app/components/tour/guides/posTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import type { HoneyCheckoutResult } from "@/app/lib/posHoney";
import {
  ActionFeedbackButton,
  AnimatedQuantity,
  FeedbackBurst,
  POSModal,
  POSPageTransition,
  PressableRoundButton,
  CartLineMotion,
} from "@/app/components/pos/posMotion";
import { POSARCheckoutPanel, type POSARCheckoutResult } from "@/app/components/pos/POSARCheckoutPanel";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { loadPosCatalogCached, readPosCache } from "@/app/lib/posCache";
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

  useEffect(() => {
    if (routeKey && !productKey) {
      navigate("/pos", { replace: true });
    }
  }, [routeKey, productKey, navigate]);

  const [product, setProduct] = useState<DeptPosProduct | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [qty, setQty] = useState(1);
  const [cart, setCart] = useState<DeptCartLine[]>([]);
  const [showCheckout, setShowCheckout] = useState(false);
  const [showARCheckout, setShowARCheckout] = useState(false);
  const [arNotice, setArNotice] = useState<string | null>(null);
  const [checkingOut, setCheckingOut] = useState(false);
  const [requestingDiscount, setRequestingDiscount] = useState<number | null>(null);
  const [pendingRequest, setPendingRequest] = useState<DeptDiscountRequestResult | null>(null);
  const [buyerName, setBuyerName] = useState("");
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

  const cartQty = useMemo(
    () => cart.filter(l => l.product_key === productKey).reduce((s, l) => s + l.qty, 0),
    [cart, productKey],
  );

  const maxQty = useMemo(() => {
    if (!product) return 0;
    return Math.max(0, Math.floor(product.available_qty - cartQty));
  }, [product, cartQty]);

  const unitPrice = product?.selling_price ?? 0;
  const sellable = product?.sellable ?? false;
  const lineTotal = unitPrice * qty;
  const subtotal = cart.reduce((s, l) => s + l.unit_price * l.qty, 0);
  const itemCount = cart.reduce((s, l) => s + l.qty, 0);

  const addToCart = () => {
    if (!product || !sellable || product.product_id == null || qty < 1 || qty > maxQty) return;
    const productId = product.product_id;
    setCart(prev => {
      const existing = prev.find(l => l.product_key === productKey);
      if (existing) {
        return prev.map(l =>
          l.product_key === productKey ? { ...l, qty: l.qty + qty } : l,
        );
      }
      return [
        ...prev,
        {
          product_key: productKey,
          product_id: productId,
          label: product.label,
          qty,
          unit_price: unitPrice,
        },
      ];
    });
    setQty(1);
    setCartPulse(true);
    setTimeout(() => setCartPulse(false), 500);
    setFeedback("success");
    setTimeout(() => setFeedback(null), 600);
  };

  const removeLine = (key: string) => setCart(prev => prev.filter(l => l.product_key !== key));

  const handleARSuccess = (result: POSARCheckoutResult) => {
    setShowARCheckout(false);
    setShowCheckout(false);
    setCart([]);
    setBuyerName("");
    setBuyerCreds("");
    setArNotice(`AR recorded ${result.receipt_no} for ${result.debtor_name}`);
    setFeedback("success");
    void loadCatalog();
    window.setTimeout(() => setArNotice(null), 4000);
  };

  const arLines = useMemo(
    () =>
      cart.map(l => ({
        key: l.product_key,
        label: l.label,
        qty: l.qty,
        unitPrice: l.unit_price,
        productId: l.product_id,
        productKey: l.product_key,
      })),
    [cart],
  );

  const cartPayload = cart.map(l => ({
    product_key: l.product_key,
    product_id: l.product_id,
    qty: l.qty,
  }));

  const mapReceipt = (result: DeptCheckoutResult): HoneyCheckoutResult => ({
    sale_id: result.sale_id,
    receipt_no: result.receipt_no,
    subtotal: result.subtotal,
    discount_percent: result.discount_percent,
    discount_amount: result.discount_amount,
    total: result.total,
    buyer_name: result.buyer_name,
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

  const checkout = async () => {
    if (cart.length === 0) return;
    setCheckingOut(true);
    setError(null);
    try {
      const result = await apiPost<DeptCheckoutResult>("pos.php", "dept_checkout", {
        items: cartPayload,
        buyer_name: buyerName.trim() || "Walk-in",
        buyer_credentials: buyerCreds.trim(),
      });
      setShowCheckout(false);
      setFeedback("success");
      setTimeout(() => {
        setFeedback(null);
        setReceipt(mapReceipt(result));
        setCart([]);
        setBuyerName("");
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
    if (!buyerName.trim()) {
      setError("Customer name is required for discount approval.");
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
        subtitle="Walk-in sale"
        onExit={handleExit}
        onBack={() => navigate("/pos")}
        onGuide={() => setGuideOpen(true)}
      />

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

      {/* Compact centered cart */}
      <section
        data-tour="pos-cart"
        className={`border-b border-border px-4 py-2 transition-colors duration-300 ${cartPulse ? "bg-[#C8900A]/10" : "bg-card"}`}
      >
        <div className="max-w-xs mx-auto text-center">
          <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Cart</p>
          {cart.length === 0 ? (
            <p className="text-xs text-muted-foreground">No items yet</p>
          ) : (
            <div className="space-y-1">
              <AnimatePresence initial={false}>
                {cart.map(line => (
                  <CartLineMotion key={line.product_key}>
                    <div className="flex items-center justify-center gap-2 text-xs bg-secondary/40 rounded-lg px-2 py-1.5">
                      <span className="font-medium">
                        {line.label} ×{line.qty}
                      </span>
                      <span className="font-semibold text-[#C8900A]">{peso(line.unit_price * line.qty)}</span>
                      <button
                        type="button"
                        onClick={() => removeLine(line.product_key)}
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
            </div>

            {sellable && maxQty > 0 && (
              <div className="flex items-center justify-center gap-4">
                <PressableRoundButton onClick={() => setQty(q => Math.max(1, q - 1))} disabled={qty <= 1}>
                  <Minus size={20} />
                </PressableRoundButton>
                <AnimatedQuantity value={qty} className="text-3xl font-bold w-12 text-center" />
                <PressableRoundButton
                  onClick={() => setQty(q => Math.min(maxQty, q + 1))}
                  disabled={qty >= maxQty}
                >
                  <Plus size={20} />
                </PressableRoundButton>
              </div>
            )}

            {sellable && (
              <ActionFeedbackButton
                variant="primary"
                disabled={maxQty < 1}
                onClick={addToCart}
                className="w-full py-4 rounded-2xl font-bold text-lg disabled:opacity-40"
              >
                Add {qty} to cart · {peso(lineTotal)}
              </ActionFeedbackButton>
            )}
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
            <label className="block text-xs uppercase tracking-wide text-muted-foreground font-semibold">
              Customer name
              <input
                value={buyerName}
                onChange={e => setBuyerName(e.target.value)}
                placeholder="Walk-in customer"
                className="mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-sm"
              />
            </label>
            <label className="block text-xs uppercase tracking-wide text-muted-foreground font-semibold">
              Credentials (optional)
              <input
                value={buyerCreds}
                onChange={e => setBuyerCreds(e.target.value)}
                placeholder="ID / note"
                className="mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-sm"
              />
            </label>
          </div>
          <div className="space-y-2 text-sm mb-4 text-center">
            {cart.map(line => (
              <div key={line.product_key} className="flex justify-center gap-2">
                <span>
                  {line.label} ×{line.qty}
                </span>
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
              disabled={checkingOut}
              flashOnClick={false}
              onClick={() => void checkout()}
              className="font-bold"
            >
              {checkingOut ? "Processing…" : "Confirm"}
            </ActionFeedbackButton>
          </div>
          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              disabled={requestingDiscount !== null || checkingOut || !buyerName.trim()}
              onClick={() => void submitDiscountRequest(10)}
              className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 disabled:opacity-50 transition-transform active:scale-[0.98]"
            >
              {requestingDiscount === 10 ? "Sending 10%..." : "Request 10% off"}
            </button>
            <button
              type="button"
              disabled={requestingDiscount !== null || checkingOut || !buyerName.trim()}
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
            <div className="mt-4 space-y-1 text-sm max-w-xs mx-auto">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Customer</span>
                <span className="font-medium">{pendingRequest.buyer_name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Subtotal</span>
                <span className="font-medium">{peso(pendingRequest.subtotal)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Discount</span>
                <span className="font-medium text-red-600">-{peso(pendingRequest.discount_amount)}</span>
              </div>
              <div className="flex justify-between border-t pt-2 font-semibold">
                <span>Total after discount</span>
                <span style={{ color: "#C8900A" }}>{peso(pendingRequest.total)}</span>
              </div>
            </div>
            <div className="mt-4 text-xs text-muted-foreground flex items-center justify-center gap-2">
              <Loader2 size={14} className="animate-spin" />
              Checking status every 3 seconds...
            </div>
          </div>
        )}
      </POSModal>

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
