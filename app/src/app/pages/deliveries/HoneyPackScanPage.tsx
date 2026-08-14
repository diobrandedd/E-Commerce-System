import { useCallback, useEffect, useId, useRef, useState } from "react";
import { BookOpen, Camera, CheckCircle2, Loader2, XCircle } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  TrackingQrScannerModal,
  type ScannerDecodeFeedback,
} from "@/app/components/scan/TrackingQrScannerModal";
import { Button } from "@/app/components/ui/button";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
  appHeaderActionClass,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { bottleBarcodesApi, type HoneyPackSession } from "@/app/lib/bottleBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";

export function HoneyPackScanPage() {
  const inputRef = useRef<HTMLInputElement>(null);
  const [session, setSession] = useState<HoneyPackSession | null>(null);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "dup" | "bad" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const cameraTitleId = useId();

  const start = useCallback(async () => {
    setError(null);
    try {
      const res = await bottleBarcodesApi.packStart();
      setSession(res.session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start pack session.");
    }
  }, []);

  useEffect(() => {
    void start();
  }, [start]);

  useEffect(() => {
    inputRef.current?.focus();
  }, [session?.id]);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Pack session is not ready." };
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const res = await bottleBarcodesApi.packScan(session.id, value);
      setSession(res.session);
      if (res.result === "ok") {
        playScanBeep("ok");
        setFlash("ok");
        const label = `${res.lookup?.variant} ${res.lookup?.size_label}`;
        setMsg(label);
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "success", message: `Scanned: ${label}` };
      } else if (res.result === "duplicate_session") {
        playScanBeep("dup");
        setFlash("dup");
        setMsg("Already scanned in this session");
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "duplicate", message: "Duplicate: already scanned in this session." };
      } else if (res.result === "already_consumed") {
        playScanBeep("bad");
        setFlash("bad");
        setMsg("Already packed or sold earlier");
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "error", message: "Failed: bottle was already packed or sold." };
      } else {
        playScanBeep("bad");
        setFlash("bad");
        const reason = res.result === "unknown" ? "Unknown barcode" : res.result.replace(/_/g, " ");
        setMsg(reason);
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "error", message: `Failed: ${reason}.` };
      }
    } catch (e) {
      playScanBeep("bad");
      setFlash("bad");
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 650);
      inputRef.current?.focus();
      return { kind: "error", message: reason };
    }
  };

  const confirm = async () => {
    if (!session) return;
    setBusy(true);
    setError(null);
    try {
      const res = await bottleBarcodesApi.packConfirm(session.id);
      setOk(`Packed ${res.session.item_count} bottles.`);
      setConfirmOpen(false);
      await start();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Confirm failed.");
    } finally {
      setBusy(false);
    }
  };

  const discard = async () => {
    if (!session) return;
    await bottleBarcodesApi.packDiscard(session.id);
    setOk("Session discarded.");
    await start();
  };

  const flashClass =
    flash === "ok"
      ? "bg-green-100 border-green-300 text-green-800"
      : flash === "dup"
        ? "bg-amber-100 border-amber-300 text-amber-800"
        : flash === "bad"
          ? "bg-red-100 border-red-300 text-red-800"
          : "bg-secondary/30 border-border text-muted-foreground";

  return (
    <PageLayout
      title="Pack scan"
      subtitle="Batch-scan bottles to deduct honey before wrapping. Waybill scan no longer deducts stock."
      actions={
        <button type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
          <BookOpen size={13} /> Guide
        </button>
      }
    >
      <AppSectionEnter className="space-y-4">
        {error && <AppErrorBanner message={error} />}
        {ok && <AppSuccessNotice message={ok} />}
        <div className="flex gap-2">
          <input
            ref={inputRef}
            className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide"
            placeholder="Scan bottle barcode or type it…"
            value={code}
            onChange={e => setCode(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void onScan();
              }
            }}
          />
          <Button type="button" variant="outline" className="gap-1.5 shrink-0" onClick={() => setCameraOpen(true)}>
            <Camera size={16} /> Camera
          </Button>
        </div>
        <p className="text-xs text-muted-foreground">
          Use a 2D imager scanner, or tap Camera to scan with this device. You can also type the code printed under the barcode.
        </p>
        <div className={`h-20 rounded-xl border flex items-center justify-center gap-2 text-sm font-semibold ${flashClass}`}>
          {flash === "ok" && <CheckCircle2 size={22} />}
          {flash === "bad" && <XCircle size={22} />}
          {flash === "ok" ? "Scanned" : flash === "dup" ? "Already in this session" : flash === "bad" ? "Not accepted" : "Ready"}
        </div>
        {msg && <p className="text-sm">{msg}</p>}
        <p className="text-xl font-bold">{session?.item_count ?? 0} bottles in session</p>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
          {(session?.grouped ?? []).map(g => (
            <div key={`${g.variant}-${g.size}`} className="bg-card border rounded-xl px-3 py-2">
              <p className="text-xs text-muted-foreground capitalize">{g.variant} {g.size_label}</p>
              <p className="text-lg font-bold">{g.qty}</p>
            </div>
          ))}
        </div>
        <div className="flex flex-wrap gap-2">
          <Button type="button" onClick={() => setConfirmOpen(true)} disabled={!session || session.item_count < 1}>
            Done scanning
          </Button>
          <Button type="button" variant="outline" onClick={() => void discard()} disabled={!session || session.item_count < 1}>
            Discard session
          </Button>
        </div>
      </AppSectionEnter>
      <AppModal open={confirmOpen} onClose={() => setConfirmOpen(false)}>
        <AppModalHeader title="Confirm pack deduction" onClose={() => setConfirmOpen(false)} />
        <AppModalBody className="space-y-2">
          <p className="text-sm text-muted-foreground">These bottles leave packaging stock now.</p>
          {(session?.grouped ?? []).map(g => (
            <p key={`${g.variant}-${g.size}`} className="text-sm font-medium capitalize">
              {g.qty} × {g.variant} {g.size_label}
            </p>
          ))}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setConfirmOpen(false)}>Keep scanning</Button>
          <Button type="button" onClick={() => void confirm()} disabled={busy}>
            {busy ? <Loader2 className="animate-spin" size={16} /> : null}
            Confirm
          </Button>
        </AppModalFooter>
      </AppModal>
      <AppModal open={guideOpen} onClose={() => setGuideOpen(false)}>
        <AppModalHeader title="Pack scan" onClose={() => setGuideOpen(false)} />
        <AppModalBody className="space-y-3 text-sm text-muted-foreground">
          <p>Scan every honey bottle you are about to pack. Duplicates in this session are ignored. Bottles already packed or sold show a distinct warning.</p>
          <p>Done scanning opens a grouped confirmation. Cancel keeps the draft so you can keep scanning.</p>
        </AppModalBody>
      </AppModal>
      {cameraOpen ? (
        <TrackingQrScannerModal
          title="Scan bottle barcode"
          titleId={cameraTitleId}
          showManualEntry={false}
          continuous
          scanMode="barcode"
          scanPrompt="Point at each bottle barcode. Keep scanning until the batch is complete."
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => onScan(decoded)}
        />
      ) : null}
    </PageLayout>
  );
}
