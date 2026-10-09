import { useState } from "react";
import { Loader2, ScanLine } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner, AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import { Button } from "@/app/components/ui/button";
import { ApiError } from "@/app/lib/api";
import {
  lookupPackagingBarcode,
  reportPackagingDamage,
  type MaterialStockRow,
} from "@/app/lib/materials";

type Step = "scan" | "confirm";

interface PackagingReportDamageModalProps {
  open: boolean;
  onClose: () => void;
  onDone: () => void;
  persistOpen?: boolean;
}

const INPUT_CLASS =
  "mt-1 w-full rounded-lg border-2 border-border bg-card px-3 py-2.5 text-sm " +
  "outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-shadow";

export function PackagingReportDamageModal({
  open,
  onClose,
  onDone,
  persistOpen = false,
}: PackagingReportDamageModalProps) {
  const [step, setStep] = useState<Step>("scan");
  const [cameraOpen, setCameraOpen] = useState(false);
  const [manualCode, setManualCode] = useState("");
  const [stock, setStock] = useState<MaterialStockRow | null>(null);
  const [qty, setQty] = useState("1");
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const reset = () => {
    setStep("scan");
    setCameraOpen(false);
    setManualCode("");
    setStock(null);
    setQty("1");
    setReason("");
    setBusy(false);
    setError(null);
    setOk(null);
  };

  const handleClose = () => {
    reset();
    onClose();
  };

  const resolveCode = async (code: string) => {
    const trimmed = code.trim();
    if (!trimmed) {
      setError("Enter or scan a barcode.");
      return;
    }
    setBusy(true);
    setError(null);
    setOk(null);
    try {
      const data = await lookupPackagingBarcode(trimmed);
      if (!data.permissions.can_deduct) {
        setError("You do not have permission to report packaging damage.");
        return;
      }
      setStock(data.stock);
      setQty("1");
      setReason("");
      setStep("confirm");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not look up barcode.");
    } finally {
      setBusy(false);
    }
  };

  const submitDamage = async () => {
    if (!stock) return;
    const onHand = Number(stock.on_hand ?? 0);
    const qtyNum = parseFloat(qty);
    if (!Number.isFinite(qtyNum) || qtyNum <= 0) {
      setError("Enter a valid quantity (greater than 0).");
      return;
    }
    if (stock.unit === "roll" && Math.abs(qtyNum - Math.round(qtyNum)) > 0.0001) {
      setError("Roll quantity must be a whole number (e.g. 1 roll).");
      return;
    }
    if (qtyNum > onHand) {
      setError(`Only ${onHand.toLocaleString()} ${stock.unit} on hand.`);
      return;
    }
    if (!reason.trim()) {
      setError("Reason is required (e.g. Torn pouch, wet roll).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await reportPackagingDamage({
        stock_id: stock.id,
        qty: qtyNum,
        reason: reason.trim(),
      });
      setOk(`Reported ${qtyNum} ${stock.unit} damaged from ${stock.name}.`);
      onDone();
      if (persistOpen) {
        setTimeout(() => {
          reset();
          setOk(`Reported ${qtyNum} ${stock.unit} damaged. Scan another when ready.`);
        }, 700);
      } else {
        setTimeout(() => {
          reset();
          onClose();
        }, 900);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not report damage.");
    } finally {
      setBusy(false);
    }
  };

  if (!open) return null;

  return (
    <>
      <AppModal open onClose={handleClose} panelClassName="max-w-md w-full">
        <AppModalHeader
          title="Report damage"
          subtitle="Scan a packaging barcode, then enter quantity and reason. Deducts on-hand and logs the damaged pool."
          onClose={handleClose}
        />
        <AppModalBody className="space-y-4">
          {error && <AppErrorBanner message={error} />}
          {ok && <AppSuccessNotice message={ok} />}

          {step === "scan" && (
            <div className="space-y-4">
              <div className="rounded-xl border border-border bg-secondary/30 px-4 py-4 space-y-3">
                <div className="flex size-10 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
                  <ScanLine size={20} strokeWidth={1.75} aria-hidden />
                </div>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Point the camera at a packaging material barcode, or type the code manually.
                </p>
                <Button type="button" className="w-full min-h-11" onClick={() => setCameraOpen(true)} disabled={busy}>
                  <ScanLine className="size-4" /> Open camera
                </Button>
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground" htmlFor="packaging-dmg-scan-barcode">
                  Barcode
                </label>
                <input
                  id="packaging-dmg-scan-barcode"
                  value={manualCode}
                  onChange={e => setManualCode(e.target.value)}
                  onKeyDown={e => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      void resolveCode(manualCode);
                    }
                  }}
                  placeholder="PKG000123"
                  className={`${INPUT_CLASS} font-mono tabular-nums`}
                  autoFocus
                />
              </div>
            </div>
          )}

          {step === "confirm" && stock && (
            <div className="space-y-4">
              <div className="rounded-xl border border-border bg-secondary/30 px-3.5 py-3">
                <p className="text-sm font-semibold text-foreground leading-snug">{stock.name}</p>
                <p className="text-xs text-muted-foreground mt-1 tabular-nums">
                  {Number(stock.on_hand ?? 0).toLocaleString()} {stock.unit} on hand
                  {stock.barcode ? ` · ${stock.barcode}` : ""}
                </p>
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground" htmlFor="packaging-dmg-scan-qty">
                  Quantity ({stock.unit})
                </label>
                <input
                  id="packaging-dmg-scan-qty"
                  type="number"
                  min="0"
                  step={stock.unit === "roll" ? "1" : "any"}
                  value={qty}
                  onChange={e => setQty(e.target.value)}
                  className={INPUT_CLASS}
                />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground" htmlFor="packaging-dmg-scan-reason">
                  Reason
                </label>
                <input
                  id="packaging-dmg-scan-reason"
                  value={reason}
                  onChange={e => setReason(e.target.value)}
                  placeholder="e.g. Torn during packing"
                  className={INPUT_CLASS}
                />
              </div>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="px-0 h-auto text-muted-foreground"
                onClick={() => {
                  setStep("scan");
                  setStock(null);
                  setError(null);
                }}
              >
                Scan a different barcode
              </Button>
            </div>
          )}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={handleClose} disabled={busy}>
            {persistOpen ? "Done" : "Cancel"}
          </Button>
          {step === "scan" ? (
            <Button type="button" onClick={() => void resolveCode(manualCode)} disabled={busy || !manualCode.trim()}>
              {busy ? <Loader2 className="size-4 animate-spin" /> : null}
              Look up
            </Button>
          ) : (
            <Button type="button" variant="destructive" onClick={() => void submitDamage()} disabled={busy}>
              {busy ? <Loader2 className="size-4 animate-spin" /> : null}
              Report damage
            </Button>
          )}
        </AppModalFooter>
      </AppModal>

      <BarcodeCameraScannerModal
        open={cameraOpen}
        onClose={() => setCameraOpen(false)}
        title="Scan packaging barcode"
        titleId="packaging-report-damage-camera"
        scanPrompt="Point at a packaging material barcode"
        onDecoded={async decoded => {
          setCameraOpen(false);
          setManualCode(decoded);
          await resolveCode(decoded);
        }}
      />
    </>
  );
}
