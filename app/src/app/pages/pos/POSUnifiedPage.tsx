import { useId, useMemo, useState } from "react";
import { useNavigate } from "react-router";
import { AnimatePresence } from "motion/react";
import { AlertCircle, Camera, Loader2, Trash2 } from "lucide-react";
import { POSHeader } from "@/app/components/pos/POSHeader";
import { POSGuideModal } from "@/app/components/pos/POSGuideModal";
import { POSReceiptView } from "@/app/components/pos/POSReceiptView";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import { runPosProductTour } from "@/app/components/tour/guides/posTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import {
  ActionFeedbackButton,
  FeedbackBurst,
  POSModal,
  POSPageTransition,
  CartLineMotion,
} from "@/app/components/pos/posMotion";
import { POSARCheckoutPanel, type POSARCheckoutResult } from "@/app/components/pos/POSARCheckoutPanel";
import { useAuth } from "@/app/context/AuthContext";
import { ApiError } from "@/app/lib/api";
import { playScanBeep } from "@/app/lib/scanBeep";
import { peso, type HoneyCheckoutResult } from "@/app/lib/posHoney";
import {
  posUnifiedApi,
  unifiedCartPayload,
  type UnifiedCartLine,
  type UnifiedLookupKind,
  type UnifiedPosLookup,
} from "@/app/lib/posUnified";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";

function lookupKindOf(b: UnifiedPosLookup): UnifiedLookupKind {
  if (b.lookup_kind) return b.lookup_kind;
  if (b.requires_qty || (b.sku_id != null && b.sku_id > 0)) return "sku";
  return b.kind === "honey" ? "unique_honey" : "unique_dept";
}

export function POSUnifiedPage() {
  const navigate = useNavigate();
  const { logout, mode } = useAuth();
  const cameraTitleId = useId();

  const [scanCode, setScanCode] = useState("");
  const [cameraOpen, setCameraOpen] = useState(false);
  const [cart, setCart] = useState<UnifiedCartLine[]>([]);
  const [showCheckout, setShowCheckout] = useState(false);
  const [showARCheckout, setShowARCheckout] = useState(false);
  const [arNotice, setArNotice] = useState<string | null>(null);
  const [checkingOut, setCheckingOut] = useState(false);
  const [buyerName, setBuyerName] = useState("");
  const [buyerCreds, setBuyerCreds] = useState("");
  const [receipt, setReceipt] = useState<HoneyCheckoutResult | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [feedback, setFeedback] = useState<"success" | "error" | null>(null);
  const [cartPulse, setCartPulse] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);
  const { inputRef: scanRef, refocus, inputProps } = useScanWedgeField(cameraOpen);

  const pricedCart = useMemo(() => cart.every(l => l.list_unit_price > 0), [cart]);
  const subtotal = cart.reduce((s, l) => s + l.list_unit_price * l.qty, 0);
  const itemCount = cart.reduce((s, l) => s + l.qty, 0);

  const removeLine = (key: string) => setCart(prev => prev.filter(l => l.key !== key));

  const setLinePrice = (key: string, raw: string) => {
    const n = parseFloat(raw);
    setCart(prev =>
      prev.map(l => (l.key === key ? { ...l, list_unit_price: Number.isFinite(n) && n >= 0 ? n : 0 } : l)),
    );
  };

  const setLineQty = (key: string, raw: string) => {
    const n = parseInt(raw, 10);
    setCart(prev =>
      prev.map(l =>
        l.key === key && l.lookup_kind === "sku"
          ? { ...l, qty: Number.isFinite(n) && n >= 1 ? n : 1 }
          : l,
      ),
    );
  };

  const addFromLookup = (b: UnifiedPosLookup): ScannerDecodeFeedback => {
    const lk = lookupKindOf(b);
    if (lk !== "sku" && b.unit_id != null && cart.some(l => l.unit_id === b.unit_id)) {
      const reason = "That sticker is already in the cart.";
      playScanBeep("dup");
      setError(reason);
      setFeedback("error");
      return { kind: "duplicate", message: reason };
    }

    if (lk === "sku") {
      const key = `sku|${b.sku_id ?? b.code}`;
      setCart(prev => {
        const existing = prev.find(l => l.key === key);
        if (existing) {
          return prev.map(l => (l.key === key ? { ...l, qty: l.qty + 1 } : l));
        }
        return [
          ...prev,
          {
            key,
            kind: b.kind,
            lookup_kind: "sku",
            label: b.label,
            code: b.code,
            batch_no: b.batch_no ?? "",
            product_id: b.product_id,
            variant: b.variant ?? undefined,
            size: b.size ?? undefined,
            product_key: b.product_key ?? undefined,
            unit_id: null,
            sku_id: b.sku_id ?? null,
            list_unit_price: b.suggested_price ?? 0,
            qty: 1,
            pool: b.pool,
          },
        ];
      });
    } else {
      const key = `${b.kind}|u${b.unit_id}`;
      setCart(prev => [
        ...prev,
        {
          key,
          kind: b.kind,
          lookup_kind: lk,
          label: b.label,
          code: b.code,
          batch_no: b.batch_no ?? "",
          product_id: b.product_id,
          variant: b.variant ?? undefined,
          size: b.size ?? undefined,
          product_key: b.product_key ?? undefined,
          unit_id: b.unit_id,
          list_unit_price: b.suggested_price ?? 0,
          qty: 1,
          pool: b.pool,
        },
      ]);
    }

    setCartPulse(true);
    setTimeout(() => setCartPulse(false), 500);
    playScanBeep("ok");
    setFeedback("success");
    refocus();
    return { kind: "success", message: `Added: ${b.label}` };
  };

  const addScanned = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? scanCode).trim();
    if (!value) return;
    setScanCode("");
    setError(null);
    try {
      const res = await posUnifiedApi.lookup(value);
      return addFromLookup(res.barcode);
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

  const handleExit = async () => {
    if (mode === "pos") {
      await logout();
      navigate("/", { replace: true });
      return;
    }
    navigate("/dashboard", { replace: true });
  };

  const checkout = async (discountPercent = 0) => {
    if (cart.length === 0 || !pricedCart) return;
    setCheckingOut(true);
    setError(null);
    try {
      const result = await posUnifiedApi.checkout({
        items: unifiedCartPayload(cart),
        buyer_name: buyerName.trim() || "Walk-in",
        buyer_credentials: buyerCreds.trim(),
        discount_percent: discountPercent,
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
    } finally {
      setCheckingOut(false);
    }
  };

  const handleARSuccess = (result: POSARCheckoutResult) => {
    setShowARCheckout(false);
    setShowCheckout(false);
    setCart([]);
    setBuyerName("");
    setBuyerCreds("");
    setArNotice(`AR recorded ${result.receipt_no} for ${result.debtor_name}`);
    setFeedback("success");
    window.setTimeout(() => setArNotice(null), 4000);
  };

  const arLines = useMemo(
    () =>
      cart.map(l => ({
        key: l.key,
        label: `${l.label} · ${l.code}`,
        qty: l.qty,
        unitPrice: l.list_unit_price,
        kind: l.kind,
        productId: l.product_id,
        variant: l.variant,
        size: l.size,
        productKey: l.product_key,
        lookupKind: l.lookup_kind,
        skuId: l.sku_id ?? undefined,
        bottleUnitIds:
          l.lookup_kind !== "sku" && l.kind === "honey" && l.unit_id != null ? [l.unit_id] : undefined,
        pouchUnitIds:
          l.lookup_kind !== "sku" && l.kind === "dept" && l.unit_id != null ? [l.unit_id] : undefined,
      })),
    [cart],
  );

  if (receipt) {
    return (
      <POSReceiptView
        receipt={receipt}
        onNewSale={() => {
          setReceipt(null);
          refocus();
        }}
      />
    );
  }

  return (
    <POSPageTransition className="min-h-screen bg-background flex flex-col pb-24 [font-family:'DM_Sans',sans-serif]">
      <POSHeader title="Pyx POS" subtitle="Scan stickers — any product" onExit={handleExit} onGuide={() => setGuideOpen(true)} />

      <div className="px-4 pt-2">
        <div className="w-full max-w-xl mx-auto flex gap-2">
          <input
            ref={scanRef}
            {...inputProps}
            className="flex-1 border rounded-lg px-3 py-2 text-sm font-mono"
            placeholder="Scan product barcode (honey or dept)"
            value={scanCode}
            onChange={e => setScanCode(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void addScanned();
              }
            }}
          />
          <ActionFeedbackButton
            variant="cancel"
            flashOnClick={false}
            onClick={() => setCameraOpen(true)}
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
        <div className="bg-red-50 border-b border-red-200 px-4 py-2 text-sm text-red-700 font-medium text-center">{arNotice}</div>
      )}

      <section
        data-tour="pos-cart"
        className={`border-b border-border px-4 py-3 transition-colors duration-300 ${cartPulse ? "bg-[#C8900A]/10" : "bg-card"}`}
      >
        <div className="max-w-lg mx-auto">
          <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-2 text-center">Cart</p>
          {cart.length === 0 ? (
            <p className="text-xs text-muted-foreground text-center">Scan stickers — enter price per line before checkout</p>
          ) : (
            <div className="space-y-2 max-h-48 overflow-y-auto">
              <AnimatePresence initial={false}>
                {cart.map(line => (
                  <CartLineMotion key={line.key}>
                    <div className="flex items-center gap-2 text-xs bg-secondary/40 rounded-lg px-2 py-2">
                      <div className="flex-1 min-w-0 text-left">
                        <p className="font-medium truncate">{line.label}</p>
                        <p className="font-mono text-muted-foreground">{line.code}</p>
                      </div>
                      {line.lookup_kind === "sku" ? (
                        <input
                          type="number"
                          min={1}
                          className="w-14 border rounded px-2 py-1 text-right"
                          value={line.qty}
                          aria-label="Quantity"
                          onChange={e => setLineQty(line.key, e.target.value)}
                        />
                      ) : (
                        <span className="w-8 text-center text-muted-foreground">×1</span>
                      )}
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        className="w-20 border rounded px-2 py-1 text-right"
                        value={line.list_unit_price || ""}
                        placeholder="Price"
                        onChange={e => setLinePrice(line.key, e.target.value)}
                      />
                      <button
                        type="button"
                        onClick={() => removeLine(line.key)}
                        className="text-red-400 hover:text-red-600 p-0.5 transition-colors active:scale-90 shrink-0"
                        aria-label="Remove"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  </CartLineMotion>
                ))}
              </AnimatePresence>
              <div className="flex justify-between pt-1 text-xs font-bold px-1">
                <span>Subtotal</span>
                <span style={{ color: "#C8900A" }}>{peso(subtotal)}</span>
              </div>
            </div>
          )}
        </div>
      </section>

      <main className="flex-1 flex items-center justify-center p-6">
        <p className="text-sm text-muted-foreground text-center max-w-sm">
          SKU stickers add with qty you can edit. Unique leftover stickers stay one unit per scan. Enter each line price manually.
        </p>
      </main>

      <div
        data-tour="pos-checkout-bar"
        className="fixed bottom-0 left-0 right-0 z-40 p-4 pt-3 bg-background/95 border-t border-border backdrop-blur-sm"
        style={{ paddingBottom: "max(1rem, env(safe-area-inset-bottom))" }}
      >
        <div className="max-w-sm mx-auto">
          <button
            type="button"
            disabled={cart.length === 0 || !pricedCart}
            onClick={() => setShowCheckout(true)}
            className="w-full py-3.5 rounded-xl text-sm font-bold text-white disabled:opacity-40 transition-transform active:scale-[0.98]"
            style={{ background: "#C8900A" }}
          >
            Checkout{itemCount > 0 ? ` (${itemCount} item${itemCount === 1 ? "" : "s"})` : ""}
          </button>
          {cart.length > 0 && !pricedCart && (
            <p className="text-[10px] text-center text-muted-foreground mt-2">Enter a price for every scanned item</p>
          )}
        </div>
      </div>

      <FeedbackBurst kind={feedback} />

      <POSModal open={showCheckout} onClose={() => setShowCheckout(false)} panelClassName="max-w-md">
        <div className="bg-card rounded-2xl border w-full p-5 space-y-4">
          <h3 className="font-bold text-lg">Checkout</h3>
          <label className="block text-xs text-muted-foreground">
            Customer name
            <input
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
              value={buyerName}
              onChange={e => setBuyerName(e.target.value)}
              placeholder="Walk-in"
            />
          </label>
          <label className="block text-xs text-muted-foreground">
            Credentials (optional)
            <input
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
              value={buyerCreds}
              onChange={e => setBuyerCreds(e.target.value)}
            />
          </label>
          <p className="text-sm font-semibold">Total: {peso(subtotal)}</p>
          <div className="flex flex-wrap gap-2">
            <ActionFeedbackButton onClick={() => void checkout(0)} disabled={checkingOut} className="flex-1 py-2.5 text-sm font-bold text-white rounded-xl" style={{ background: "#C8900A" }}>
              {checkingOut ? <Loader2 className="animate-spin mx-auto" size={18} /> : "Pay full price"}
            </ActionFeedbackButton>
            <ActionFeedbackButton onClick={() => void checkout(10)} disabled={checkingOut} className="px-3 py-2 text-xs border rounded-lg">
              10% off · {peso(subtotal * 0.9)}
            </ActionFeedbackButton>
            <ActionFeedbackButton onClick={() => void checkout(20)} disabled={checkingOut} className="px-3 py-2 text-xs border rounded-lg">
              20% off · {peso(subtotal * 0.8)}
            </ActionFeedbackButton>
          </div>
          <button type="button" className="w-full text-xs text-muted-foreground underline" onClick={() => { setShowCheckout(false); setShowARCheckout(true); }}>
            Record as AR (accounts receivable)
          </button>
        </div>
      </POSModal>

      <POSModal open={showARCheckout} onClose={() => setShowARCheckout(false)} panelClassName="max-w-md">
        <POSARCheckoutPanel mode="unified" lines={arLines} onCancel={() => setShowARCheckout(false)} onSuccess={handleARSuccess} />
      </POSModal>

      {cameraOpen ? (
        <BarcodeCameraScannerModal
          titleId={cameraTitleId}
          title="Scan product barcode"
          continuous
          scanPrompt="Point at a honey or pouch/jar sticker."
          onClose={() => setCameraOpen(false)}
          onDecoded={addScanned}
        />
      ) : null}

      <POSGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        context="unified"
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runPosProductTour("unified"));
        }}
      />
    </POSPageTransition>
  );
}
