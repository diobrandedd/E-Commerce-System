import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router";
import { AnimatePresence } from "motion/react";
import { AlertCircle, Loader2, Minus, Plus, Trash2, X } from "lucide-react";
import { POSHeader } from "@/app/components/pos/POSHeader";
import { POSGuideModal } from "@/app/components/pos/POSGuideModal";
import { POSReceiptView } from "@/app/components/pos/POSReceiptView";
import { runPosProductTour } from "@/app/components/tour/guides/posTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import {
  ActionFeedbackButton,
  AnimatedQuantity,
  FeedbackBurst,
  POSModal,
  POSPageTransition,
  PressableRoundButton,
  VariantCardMotion,
  CartLineMotion,
} from "@/app/components/pos/posMotion";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import type { HoneyBottleSize } from "@/app/lib/honey";
import {
  cartLineKey,
  peso,
  variantLabel,
  type HoneyCartLine,
  type HoneyCatalogVariant,
  type HoneyCheckoutResult,
  type HoneyDiscountRequestResult,
  type HoneyDiscountRequestStatus,
} from "@/app/lib/posHoney";

export function POSHoneyPage() {
  const navigate = useNavigate();
  const { logout } = useAuth();
  const [variants, setVariants] = useState<HoneyCatalogVariant[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [cart, setCart] = useState<HoneyCartLine[]>([]);
  const [activeVariant, setActiveVariant] = useState<HoneyCatalogVariant | null>(null);
  const [selectedSize, setSelectedSize] = useState<HoneyBottleSize | null>(null);
  const [qty, setQty] = useState(1);
  const [showCheckout, setShowCheckout] = useState(false);
  const [checkingOut, setCheckingOut] = useState(false);
  const [requestingDiscount, setRequestingDiscount] = useState<number | null>(null);
  const [pendingRequest, setPendingRequest] = useState<HoneyDiscountRequestResult | null>(null);
  const [buyerName, setBuyerName] = useState("");
  const [buyerCreds, setBuyerCreds] = useState("");
  const [receipt, setReceipt] = useState<HoneyCheckoutResult | null>(null);
  const [feedback, setFeedback] = useState<"success" | "error" | null>(null);
  const [cartPulse, setCartPulse] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);

  const loadCatalog = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await apiGet<{ variants: HoneyCatalogVariant[] }>("pos.php", "honey_catalog");
      setVariants(data.variants);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load honey catalog.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  const cartQtyFor = useCallback(
    (variant: string, size: string) =>
      cart.filter(l => l.variant === variant && l.size === size).reduce((s, l) => s + l.qty, 0),
    [cart],
  );

  const selectedSizeInfo = useMemo(() => {
    if (!activeVariant || !selectedSize) return null;
    return activeVariant.sizes.find(s => s.size === selectedSize) ?? null;
  }, [activeVariant, selectedSize]);

  const maxQty = useMemo(() => {
    if (!selectedSizeInfo) return 0;
    const inCart = activeVariant ? cartQtyFor(activeVariant.variant, selectedSize!) : 0;
    return Math.max(0, Math.floor(selectedSizeInfo.available_qty - inCart));
  }, [selectedSizeInfo, activeVariant, selectedSize, cartQtyFor]);

  const unitPrice = selectedSizeInfo?.unit_price ?? 0;
  const lineTotal = unitPrice * qty;

  const noSellableSizes = useMemo(
    () => (activeVariant ? activeVariant.sizes.every(s => !s.sellable) : false),
    [activeVariant],
  );

  const openVariant = (v: HoneyCatalogVariant) => {
    setActiveVariant(v);
    const firstSellable = v.sizes.find(s => s.sellable && s.available_qty > 0);
    const firstAny = v.sizes.find(s => s.available_qty > 0);
    const pick = firstSellable ?? firstAny ?? v.sizes[0];
    setSelectedSize(pick?.size ?? "250ml");
    setQty(1);
  };

  const closeModal = () => {
    setActiveVariant(null);
    setSelectedSize(null);
  };

  const addToCart = () => {
    if (!activeVariant || !selectedSize || !selectedSizeInfo?.sellable || !selectedSizeInfo.product_id) return;
    if (qty < 1 || qty > maxQty) return;

    const key = cartLineKey(activeVariant.variant, selectedSize);
    setCart(prev => {
      const existing = prev.find(l => l.key === key);
      if (existing) {
        return prev.map(l =>
          l.key === key ? { ...l, qty: l.qty + qty } : l,
        );
      }
      return [
        ...prev,
        {
          key,
          variant: activeVariant.variant,
          size: selectedSize,
          sizeLabel: selectedSizeInfo.label,
          productId: selectedSizeInfo.product_id,
          unitPrice: selectedSizeInfo.unit_price ?? 0,
          qty,
        },
      ];
    });
    setCartPulse(true);
    setTimeout(() => setCartPulse(false), 500);
    closeModal();
    setFeedback("success");
  };

  useEffect(() => {
    if (!feedback) return;
    const t = window.setTimeout(() => setFeedback(null), 750);
    return () => window.clearTimeout(t);
  }, [feedback]);

  const removeLine = (key: string) => setCart(prev => prev.filter(l => l.key !== key));

  const subtotal = cart.reduce((s, l) => s + l.unitPrice * l.qty, 0);
  const itemCount = cart.reduce((s, l) => s + l.qty, 0);

  const handleCheckout = async () => {
    if (cart.length === 0) return;
    setCheckingOut(true);
    setError(null);
    try {
      const result = await apiPost<HoneyCheckoutResult>("pos.php", "honey_checkout", {
        items: cart.map(l => ({
          product_id: l.productId,
          variant: l.variant,
          size: l.size,
          qty: l.qty,
        })),
        buyer_name: buyerName.trim() || "Walk-in",
        buyer_credentials: buyerCreds.trim(),
      });
      setShowCheckout(false);
      setFeedback("success");
      setTimeout(() => {
        setReceipt(result);
        setCart([]);
        setBuyerName("");
        setBuyerCreds("");
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
      const result = await apiPost<HoneyDiscountRequestResult>("pos.php", "discount_request_create", {
        items: cart.map(l => ({
          product_id: l.productId,
          variant: l.variant,
          size: l.size,
          qty: l.qty,
        })),
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
        const status = await apiGet<HoneyDiscountRequestStatus>("pos.php", "discount_request_status", {
          request_id: String(pendingRequest.request_id),
        });
        if (!active) return;
        if (status.status === "approved" && status.receipt) {
          setPendingRequest(null);
          setCart([]);
          setBuyerName("");
          setBuyerCreds("");
          setReceipt(status.receipt);
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
  }, [pendingRequest]);

  const handleExit = async () => {
    await logout();
    navigate("/", { replace: true });
  };

  return (
    <AnimatePresence mode="wait">
      {receipt ? (
        <POSReceiptView
          key="receipt"
          receipt={receipt}
          onNewSale={() => {
            setReceipt(null);
            void loadCatalog();
          }}
        />
      ) : (
        <POSPageTransition key="shop" className="min-h-screen bg-background flex flex-col pb-24" style={{ fontFamily: "'DM Sans', sans-serif" }}>
          <POSHeader
            title="Honey"
            subtitle="Walk-in sales"
            onBack={() => navigate("/pos")}
            onExit={handleExit}
            onGuide={() => setGuideOpen(true)}
          />

          {error && (
            <div className="bg-red-50 border-b border-red-200 px-4 py-2 text-sm text-red-700 flex items-center gap-2">
              <AlertCircle size={14} className="flex-shrink-0" />
              <span>{error}</span>
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
                <p className="text-xs text-muted-foreground">No items yet — tap a variant below</p>
              ) : (
                <div className="space-y-1 max-h-28 overflow-y-auto">
                  <AnimatePresence initial={false}>
                    {cart.map(line => (
                      <CartLineMotion key={line.key}>
                        <div className="flex items-center justify-center gap-2 text-xs bg-secondary/40 rounded-lg px-2 py-1.5">
                          <span className="font-medium capitalize">
                            {variantLabel(line.variant)} · {line.sizeLabel} ×{line.qty}
                          </span>
                          <span className="font-semibold text-[#C8900A]">{peso(line.unitPrice * line.qty)}</span>
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

          {/* Variant cards */}
          <main className="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 min-h-0">
            <p className="text-sm text-muted-foreground mb-3 text-center">Tap a variant to add</p>
            {loading ? (
              <div className="flex justify-center py-12 text-muted-foreground">
                <Loader2 className="animate-spin mr-2" size={18} />
                Loading…
              </div>
            ) : (
              <div
                className="grid grid-cols-3 gap-2 sm:gap-3 md:gap-5 w-full max-w-[18rem] sm:max-w-md md:max-w-2xl lg:max-w-3xl mx-auto"
                data-tour="pos-product-grid"
              >
                {variants.map(v => (
                  <VariantCardMotion
                    key={v.variant}
                    onClick={() => openVariant(v)}
                    className="group relative rounded-xl overflow-hidden border border-border aspect-[3/4] max-h-[9.5rem] sm:max-h-[11rem] md:max-h-none text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C8900A] focus-visible:ring-offset-2"
                  >
                    <img
                      src={v.image}
                      alt={variantLabel(v.variant)}
                      className="absolute inset-0 w-full h-full object-cover transition-transform duration-150 group-hover:scale-[1.03]"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent" />
                    <div className="absolute bottom-0 left-0 right-0 p-2 sm:p-2.5 md:p-3 text-white">
                      <p className="text-sm sm:text-base md:text-lg font-bold leading-tight">{variantLabel(v.variant)}</p>
                      <p className="text-[10px] sm:text-xs md:text-sm text-white/80 mt-0.5">
                        {Math.round(v.total_on_hand)} on hand
                      </p>
                    </div>
                  </VariantCardMotion>
                ))}
              </div>
            )}
          </main>

          {/* Fixed checkout — hidden while add modal is open */}
          {!activeVariant && (
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
          )}

          {/* Add modal */}
          <POSModal open={!!activeVariant} onClose={closeModal} panelClassName="max-w-md">
            {activeVariant && (
              <div
                className="bg-card rounded-2xl border w-full flex flex-col max-h-[min(90vh,calc(100vh-2rem))] shadow-lg"
              >
                <div className="flex-shrink-0 flex items-center justify-between p-5 pb-3">
                  <h3 className="font-bold text-lg">Add — {variantLabel(activeVariant.variant)}</h3>
                  <button
                    type="button"
                    onClick={closeModal}
                    className="p-1 text-muted-foreground hover:text-foreground rounded-full transition-colors active:scale-90"
                    aria-label="Close"
                  >
                    <X size={20} />
                  </button>
                </div>

                <div className="flex-1 min-h-0 overflow-y-auto px-5 pb-3">
                  {noSellableSizes && (
                    <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
                      Set selling price in Costing (Products) for this variant and size.
                    </p>
                  )}

                  <p className="text-xs text-muted-foreground mb-2">Size</p>
                  <div className="grid grid-cols-2 gap-2">
                    {activeVariant.sizes.map(s => {
                      const inCart = cartQtyFor(activeVariant.variant, s.size);
                      const remaining = Math.max(0, Math.floor(s.available_qty - inCart));
                      const disabled = !s.sellable || remaining <= 0;
                      const selected = selectedSize === s.size;
                      return (
                        <button
                          key={s.size}
                          type="button"
                          disabled={disabled}
                          onClick={() => {
                            setSelectedSize(s.size);
                            setQty(1);
                          }}
                          className={`rounded-xl border p-3 text-left text-sm transition-all duration-150 active:scale-[0.97] ${
                            selected ? "border-[#C8900A] bg-[#C8900A]/10 ring-2 ring-[#C8900A]/30" : "border-border"
                          } ${disabled ? "opacity-45 cursor-not-allowed" : "hover:border-[#C8900A]/50"}`}
                        >
                          <p className="font-semibold">{s.label}</p>
                          <p className="text-xs text-muted-foreground mt-0.5">
                            {!s.sellable ? "Not for sale" : remaining <= 0 ? "Out of stock" : `${remaining} on hand`}
                          </p>
                          {s.sellable && s.unit_price != null && (
                            <p className="text-xs font-medium mt-1" style={{ color: "#C8900A" }}>
                              {peso(s.unit_price)}
                            </p>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>

                {selectedSizeInfo?.sellable && maxQty > 0 && (
                  <div
                    className="flex-shrink-0 border-t border-border bg-card px-5 pt-3 pb-4"
                    style={{ paddingBottom: "max(1rem, env(safe-area-inset-bottom))" }}
                  >
                    <div className="flex justify-between text-sm mb-3">
                      <span className="text-muted-foreground">Unit price</span>
                      <span className="font-semibold">{peso(unitPrice)}</span>
                    </div>
                    <p className="text-xs text-muted-foreground mb-2">Quantity (max {maxQty})</p>
                    <div className="flex items-center justify-center gap-4 mb-4">
                      <PressableRoundButton
                        ariaLabel="Decrease quantity"
                        onClick={() => setQty(q => Math.max(1, q - 1))}
                        disabled={qty <= 1}
                      >
                        <Minus size={16} />
                      </PressableRoundButton>
                      <AnimatedQuantity value={qty} />
                      <PressableRoundButton
                        ariaLabel="Increase quantity"
                        onClick={() => setQty(q => Math.min(maxQty, q + 1))}
                        disabled={qty >= maxQty}
                      >
                        <Plus size={16} />
                      </PressableRoundButton>
                    </div>
                    <div className="flex justify-between text-sm font-bold mb-4">
                      <span>Line total</span>
                      <span style={{ color: "#C8900A" }}>{peso(lineTotal)}</span>
                    </div>
                    <button
                      type="button"
                      onClick={addToCart}
                      className="w-full py-3 rounded-xl text-sm font-bold text-white transition-transform active:scale-[0.98]"
                      style={{ background: "#C8900A" }}
                    >
                      Add to cart
                    </button>
                  </div>
                )}
              </div>
            )}
          </POSModal>

          {/* Checkout confirm */}
          <POSModal open={showCheckout} onClose={() => setShowCheckout(false)} panelClassName="max-w-sm">
            <div className="bg-card rounded-2xl border w-full p-5 shadow-lg">
              <h3 className="font-bold text-lg mb-4">Confirm order</h3>
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
              <div className="space-y-2 text-sm mb-4 max-h-48 overflow-y-auto">
                {cart.map(line => (
                  <div key={line.key} className="flex justify-between gap-2">
                    <span>
                      {variantLabel(line.variant)} · {line.sizeLabel} ×{line.qty}
                    </span>
                    <span className="font-semibold">{peso(line.unitPrice * line.qty)}</span>
                  </div>
                ))}
              </div>
              <div className="flex justify-between font-bold text-base border-t pt-3 mb-4">
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
                  onClick={() => void handleCheckout()}
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
                  className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 disabled:opacity-50"
                >
                  {requestingDiscount === 10 ? "Sending 10%..." : "Request 10% off"}
                </button>
                <button
                  type="button"
                  disabled={requestingDiscount !== null || checkingOut || !buyerName.trim()}
                  onClick={() => void submitDiscountRequest(20)}
                  className="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 disabled:opacity-50"
                >
                  {requestingDiscount === 20 ? "Sending 20%..." : "Request 20% off"}
                </button>
              </div>
              <p className="mt-2 text-[11px] text-muted-foreground">
                Discounted checkouts require executive approval before the receipt is released.
              </p>
            </div>
          </POSModal>

          <POSModal open={pendingRequest !== null} onClose={() => {}} panelClassName="max-w-sm">
            {pendingRequest && (
              <div className="bg-card rounded-2xl border w-full p-5 shadow-lg">
                <h3 className="font-bold text-lg mb-2">Waiting for approval</h3>
                <p className="text-sm text-muted-foreground">
                  Request <span className="font-semibold text-foreground">{pendingRequest.request_no}</span> for{" "}
                  <span className="font-semibold text-foreground">{pendingRequest.discount_percent}%</span> discount is pending executive review.
                </p>
                <div className="mt-4 space-y-1 text-sm">
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
                <div className="mt-4 text-xs text-muted-foreground flex items-center gap-2">
                  <Loader2 size={14} className="animate-spin" />
                  Checking status every 3 seconds...
                </div>
              </div>
            )}
          </POSModal>

          <FeedbackBurst kind="success" show={feedback === "success"} />
          <FeedbackBurst kind="error" show={feedback === "error"} />

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
      )}
    </AnimatePresence>
  );
}
