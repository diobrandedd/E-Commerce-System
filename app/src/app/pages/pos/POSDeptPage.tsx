import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router";
import { AnimatePresence } from "motion/react";
import { AlertCircle, Loader2, Minus, Plus } from "lucide-react";
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
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import {
  POS_DEPT_LABELS,
  POS_DEPT_ROUTE_KEYS,
  peso,
  type DeptCartLine,
  type DeptCheckoutResult,
  type DeptPosProduct,
} from "@/app/lib/posDept";

export function POSDeptPage() {
  const { deptKey: routeKey } = useParams();
  const productKey = POS_DEPT_ROUTE_KEYS[routeKey ?? ""] ?? routeKey ?? "";
  const title = POS_DEPT_LABELS[productKey] ?? "Product";
  const navigate = useNavigate();
  const { logout } = useAuth();

  const [product, setProduct] = useState<DeptPosProduct | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [qty, setQty] = useState(1);
  const [cart, setCart] = useState<DeptCartLine[]>([]);
  const [showCheckout, setShowCheckout] = useState(false);
  const [checkingOut, setCheckingOut] = useState(false);
  const [buyerName, setBuyerName] = useState("");
  const [buyerCreds, setBuyerCreds] = useState("");
  const [receipt, setReceipt] = useState<HoneyCheckoutResult | null>(null);
  const [feedback, setFeedback] = useState<"success" | "error" | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);

  const loadCatalog = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await apiGet<{ products: DeptPosProduct[] }>("pos.php", "dept_catalog");
      const match = data.products.find(p => p.key === productKey) ?? null;
      setProduct(match);
      if (!match) {
        setError("Product not found in catalog.");
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load catalog.");
    } finally {
      setLoading(false);
    }
  }, [productKey]);

  useEffect(() => { void loadCatalog(); }, [loadCatalog]);

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
  const cartTotal = cart.reduce((s, l) => s + l.unit_price * l.qty, 0);

  const addToCart = () => {
    if (!product || !sellable || qty < 1 || qty > maxQty) return;
    setCart(prev => {
      const existing = prev.find(l => l.product_key === productKey);
      if (existing) {
        return prev.map(l =>
          l.product_key === productKey
            ? { ...l, qty: l.qty + qty }
            : l,
        );
      }
      return [
        ...prev,
        {
          product_key: productKey,
          label: product.label,
          qty,
          unit_price: unitPrice,
        },
      ];
    });
    setQty(1);
    setFeedback("success");
    setTimeout(() => setFeedback(null), 600);
  };

  const handleExit = async () => {
    await logout();
    navigate("/", { replace: true });
  };

  const checkout = async () => {
    setCheckingOut(true);
    setError(null);
    try {
      const result = await apiPost<DeptCheckoutResult>("pos.php", "dept_checkout", {
        items: cart.map(l => ({ product_key: l.product_key, qty: l.qty })),
        buyer_name: buyerName,
        buyer_credentials: buyerCreds,
      });
      setReceipt({
        sale_id: result.sale_id,
        receipt_no: result.receipt_no,
        total: result.total,
        buyer_name: result.buyer_name,
        items: result.items.map(i => ({
          product_id: 0,
          qty: i.qty,
          unit_price: i.unit_price,
          line_total: i.line_total,
          name: i.name,
        })),
      });
      setCart([]);
      setShowCheckout(false);
      await loadCatalog();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Checkout failed.");
      setFeedback("error");
    } finally {
      setCheckingOut(false);
    }
  };

  if (receipt) {
    return (
      <POSReceiptView
        receipt={receipt}
        onNewSale={() => { setReceipt(null); navigate("/pos", { replace: true }); }}
      />
    );
  }

  return (
    <POSPageTransition className="min-h-screen bg-background flex flex-col" style={{ fontFamily: "'DM Sans', sans-serif" }}>
      <POSHeader
        title={title}
        subtitle="Walk-in sale"
        onExit={handleExit}
        onBack={() => navigate("/pos")}
        onGuide={() => setGuideOpen(true)}
      />

      <main className="flex-1 p-4 sm:p-6 max-w-lg mx-auto w-full flex flex-col gap-4">
        {loading && (
          <div className="flex justify-center py-12">
            <Loader2 className="animate-spin text-muted-foreground" size={24} />
          </div>
        )}
        {error && !loading && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5" /> <span>{error}</span>
          </div>
        )}
        {product && !loading && (
          <div data-tour="pos-product-grid" className="space-y-4">
            <div className="rounded-2xl border bg-card p-5 text-center">
              <p className="text-lg font-bold text-foreground">{product.label}</p>
              <p className="text-sm text-muted-foreground mt-1">
                On hand: <strong>{product.available_qty}</strong> {product.unit}
              </p>
              {sellable ? (
                <p className="text-2xl font-bold mt-3" style={{ color: "#C8900A" }}>{peso(unitPrice)}</p>
              ) : (
                <p className="text-xs text-amber-700 mt-3">Not sellable — executive must set selling price in Costing.</p>
              )}
            </div>

            {sellable && maxQty > 0 && (
              <div className="flex items-center justify-center gap-4">
                <PressableRoundButton type="button" onClick={() => setQty(q => Math.max(1, q - 1))} disabled={qty <= 1}>
                  <Minus size={20} />
                </PressableRoundButton>
                <AnimatedQuantity value={qty} className="text-3xl font-bold w-12 text-center" />
                <PressableRoundButton type="button" onClick={() => setQty(q => Math.min(maxQty, q + 1))} disabled={qty >= maxQty}>
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

        {cart.length > 0 && (
          <div data-tour="pos-cart" className="mt-auto border-t pt-4 space-y-2">
            <p className="text-xs font-semibold text-muted-foreground uppercase">Cart</p>
            <AnimatePresence>
              {cart.map(line => (
                <CartLineMotion key={line.product_key}>
                  <div className="flex justify-between items-center py-2">
                    <span className="font-medium">{line.label} × {line.qty}</span>
                    <span className="font-bold">{peso(line.unit_price * line.qty)}</span>
                  </div>
                </CartLineMotion>
              ))}
            </AnimatePresence>
            <div className="flex justify-between items-center pt-2 border-t">
              <span className="font-bold">Total</span>
              <span className="text-xl font-bold" style={{ color: "#C8900A" }}>{peso(cartTotal)}</span>
            </div>
            <button
              type="button"
              data-tour="pos-checkout-bar"
              onClick={() => setShowCheckout(true)}
              className="w-full py-3 rounded-xl text-white font-bold"
              style={{ background: "#1a1a1a" }}
            >
              Checkout
            </button>
          </div>
        )}
      </main>

      <FeedbackBurst show={feedback === "success"} kind="success" />
      <FeedbackBurst show={feedback === "error"} kind="error" />

      {showCheckout && (
        <POSModal title="Checkout" onClose={() => setShowCheckout(false)}>
          <div className="space-y-3">
            <input
              placeholder="Buyer name (optional)"
              value={buyerName}
              onChange={e => setBuyerName(e.target.value)}
              className="w-full border rounded-lg px-3 py-2 text-sm"
            />
            <input
              placeholder="ID / credentials (optional)"
              value={buyerCreds}
              onChange={e => setBuyerCreds(e.target.value)}
              className="w-full border rounded-lg px-3 py-2 text-sm"
            />
            <button
              type="button"
              disabled={checkingOut}
              onClick={() => void checkout()}
              className="w-full py-3 rounded-xl text-white font-bold disabled:opacity-50"
              style={{ background: "#C8900A" }}
            >
              {checkingOut ? "Processing…" : `Pay ${peso(cartTotal)}`}
            </button>
          </div>
        </POSModal>
      )}

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
