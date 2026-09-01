import { useEffect, useState } from "react";
import { motion, useReducedMotion } from "motion/react";
import { Check, Printer } from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";
import { peso, type HoneyCheckoutResult } from "@/app/lib/posHoney";
import { variantLabel } from "@/app/lib/posHoney";
import { printPosThermalReceipt } from "@/app/lib/posThermalPrint";
import { POSPageTransition } from "@/app/components/pos/posMotion";

export function POSReceiptView({
  receipt,
  onNewSale,
}: {
  receipt: HoneyCheckoutResult;
  onNewSale: () => void;
}) {
  const reduce = useReducedMotion();
  const [showCheck, setShowCheck] = useState(true);

  useEffect(() => {
    if (reduce) {
      setShowCheck(false);
      return;
    }
    const t = window.setTimeout(() => setShowCheck(false), 900);
    return () => window.clearTimeout(t);
  }, [reduce]);

  return (
    <POSPageTransition
      className="min-h-screen bg-background flex items-center justify-center p-4"
      style={{ fontFamily: "'DM Mono', monospace" }}
    >
      {showCheck && !reduce && (
        <motion.div
          className="fixed inset-0 z-50 flex items-center justify-center pointer-events-none"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          <motion.div
            initial={{ scale: 0.3, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ type: "spring", stiffness: 400, damping: 22 }}
            className="w-24 h-24 rounded-full bg-green-500 text-white flex items-center justify-center shadow-xl"
          >
            <Check size={48} strokeWidth={3} />
          </motion.div>
        </motion.div>
      )}

      <motion.div
        className="bg-card rounded-xl border border-border p-8 w-full max-w-sm"
        style={{ boxShadow: "0 4px 24px rgba(0,0,0,0.1)" }}
        initial={reduce ? false : { opacity: 0, y: 24, scale: 0.96 }}
        animate={{ opacity: 1, y: 0, scale: 1 }}
        transition={{ delay: reduce ? 0 : 0.35, duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
      >
        <div className="text-center mb-6">
          <motion.img
            src={pyxLogo}
            alt="Pyx"
            className="w-16 h-16 object-contain mx-auto mb-3"
            initial={reduce ? false : { scale: 0.8, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ delay: 0.45, type: "spring", stiffness: 300, damping: 20 }}
          />
          <p className="text-sm font-bold text-foreground">Pyx Food Products</p>
          <p className="text-xs text-muted-foreground">Walk-in Sales Receipt</p>
          <p className="text-xs text-muted-foreground mt-1">{receipt.receipt_no}</p>
          <p className="text-xs text-muted-foreground">{new Date().toLocaleString("en-PH")}</p>
        </div>
        <div className="border-t border-dashed border-border pt-4 mb-4">
          <p className="text-xs text-muted-foreground mb-1">
            Buyer: <span className="text-foreground font-semibold">{receipt.buyer_name}</span>
          </p>
          {receipt.seller_name?.trim() ? (
            <p className="text-xs text-muted-foreground mb-3">
              Seller: <span className="text-foreground font-semibold">{receipt.seller_name.trim()}</span>
            </p>
          ) : (
            <div className="mb-3" />
          )}
          <div className="space-y-2">
            {receipt.items.map((item, idx) => (
              <motion.div
                key={idx}
                className="flex justify-between text-xs gap-2"
                initial={reduce ? false : { opacity: 0, x: -8 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.5 + idx * 0.06 }}
              >
                <span className="text-foreground">
                  {item.variant ? `${variantLabel(item.variant)} · ${item.size}` : item.name} ×{item.qty}
                </span>
                <span className="font-semibold text-foreground">
                  {item.line_subtotal != null && item.line_subtotal > item.line_total ? (
                    <>
                      <span className="line-through text-muted-foreground mr-1">{peso(item.line_subtotal)}</span>
                      {peso(item.line_total)}
                    </>
                  ) : (
                    peso(item.line_total)
                  )}
                </span>
              </motion.div>
            ))}
          </div>
        </div>
        {receipt.discount_percent != null && receipt.discount_percent > 0 && (
          <div className="border-t border-dashed border-border pt-3 mb-4 text-xs space-y-1">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Subtotal</span>
              <span className="text-foreground">{peso(receipt.subtotal ?? receipt.total)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Discount ({receipt.discount_percent}%)</span>
              <span className="text-red-600">-{peso(receipt.discount_amount ?? 0)}</span>
            </div>
          </div>
        )}
        <div className="border-t-2 border-border pt-3 flex justify-between items-center mb-6">
          <span className="font-bold text-foreground">TOTAL</span>
          <span className="text-xl font-bold" style={{ color: "#C8900A" }}>
            {peso(receipt.total)}
          </span>
        </div>
        <div className="flex gap-3">
          <button
            type="button"
            onClick={() => printPosThermalReceipt(receipt, pyxLogo)}
            className="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg text-sm font-semibold text-white transition-transform active:scale-[0.97]"
            style={{ background: "#C8900A" }}
          >
            <Printer size={15} /> Print
          </button>
          <button
            type="button"
            onClick={onNewSale}
            className="flex-1 py-3 rounded-lg text-sm font-semibold border border-border hover:bg-secondary transition-all active:scale-[0.97] text-muted-foreground"
          >
            New Sale
          </button>
        </div>
      </motion.div>
    </POSPageTransition>
  );
}
