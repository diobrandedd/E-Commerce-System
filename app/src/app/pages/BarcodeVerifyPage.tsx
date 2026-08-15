import { useId, useRef, useState } from "react";
import { Camera, Loader2, ScanLine } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import {
  barcodeVerifyApi,
  type BarcodeVerifyResult,
} from "@/app/lib/barcodeVerify";
import { playScanBeep } from "@/app/lib/scanBeep";

function formatWhen(raw: string | null | undefined) {
  if (!raw) return "—";
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

function statusLabel(status: string) {
  return status.replace(/_/g, " ");
}

export function BarcodeVerifyPage() {
  const inputRef = useRef<HTMLInputElement>(null);
  const cameraTitleId = useId();
  const [code, setCode] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<BarcodeVerifyResult | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);

  const lookup = async (raw?: string) => {
    const value = (raw ?? code).trim();
    if (!value) {
      setError("Enter or scan a barcode.");
      return;
    }
    setLoading(true);
    setError(null);
    setResult(null);
    try {
      const res = await barcodeVerifyApi.lookup(value);
      setResult(res.result);
      setCode(res.result.code);
      playScanBeep("ok");
    } catch (e) {
      const message = e instanceof ApiError ? e.message : "Lookup failed.";
      setError(message);
      playScanBeep(/unknown/i.test(message) ? "bad" : "bad");
    } finally {
      setLoading(false);
      inputRef.current?.focus();
    }
  };

  return (
    <PageLayout
      title="Verify barcode"
      subtitle="Check any honey or dept sticker — supplier/batch, product, and dates"
    >
      <AppSectionEnter className="space-y-4 max-w-xl">
        <p className="text-sm text-muted-foreground">
          Scan or type a sticker code to see what product it is, the batch label, bottling/batch date,
          and when it was scanned into inventory (if already stocked).
        </p>

        <div className="flex flex-wrap gap-2">
          <input
            ref={inputRef}
            className="flex-1 min-w-[12rem] border rounded-lg px-3 py-2 font-mono text-sm outline-none focus:ring-4 focus:ring-[#C8900A]/15"
            placeholder="Scan or type barcode"
            value={code}
            autoFocus
            onChange={e => setCode(e.target.value.toUpperCase())}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void lookup();
              }
            }}
          />
          <Button type="button" variant="outline" className="gap-1.5" onClick={() => setCameraOpen(true)}>
            <Camera size={16} /> Camera
          </Button>
          <Button type="button" className="gap-1.5" disabled={loading || !code.trim()} onClick={() => void lookup()}>
            {loading ? <Loader2 className="animate-spin" size={16} /> : <ScanLine size={16} />}
            Verify
          </Button>
        </div>

        {error && <AppErrorBanner message={error} />}

        {result && (
          <div className="rounded-xl border border-border bg-card p-4 space-y-3">
            <AppSuccessNotice message={`Found ${result.code}`} />
            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
              <div>
                <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Product</dt>
                <dd className="font-semibold mt-0.5">{result.product_label}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Status</dt>
                <dd className="mt-0.5 capitalize">{statusLabel(result.status)}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">
                  {result.kind === "honey" ? "Supplier code" : "Batch / supplier label"}
                </dt>
                <dd className="font-mono font-semibold mt-0.5">{result.supplier_code}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Batch</dt>
                <dd className="font-mono mt-0.5">{result.batch_no}</dd>
              </div>
              {result.honey && (
                <>
                  <div>
                    <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Variant</dt>
                    <dd className="capitalize mt-0.5">{result.honey.variant}</dd>
                  </div>
                  <div>
                    <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Size</dt>
                    <dd className="mt-0.5">{result.honey.size_label}</dd>
                  </div>
                </>
              )}
              {result.dept && (
                <div className="sm:col-span-2">
                  <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Unit</dt>
                  <dd className="mt-0.5 capitalize">
                    {result.dept.unit} ({result.product_label})
                  </dd>
                </div>
              )}
              <div>
                <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Batch / bottling date</dt>
                <dd className="mt-0.5">{result.batch_date || "—"}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Scanned to inventory</dt>
                <dd className="mt-0.5">{formatWhen(result.scanned_at)}</dd>
              </div>
              {result.scanned_by_name && (
                <div className="sm:col-span-2">
                  <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Scanned by</dt>
                  <dd className="mt-0.5">{result.scanned_by_name}</dd>
                </div>
              )}
              {result.unit_status && (
                <div>
                  <dt className="text-[10px] uppercase tracking-wide text-muted-foreground">Unit status</dt>
                  <dd className="mt-0.5 capitalize">{statusLabel(result.unit_status)}</dd>
                </div>
              )}
            </dl>
          </div>
        )}
      </AppSectionEnter>

      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title="Scan barcode to verify"
          titleId={cameraTitleId}
          continuous={false}
          scanPrompt="Point at a honey or pouch/jar sticker."
          onClose={() => setCameraOpen(false)}
          onDecoded={async decoded => {
            setCameraOpen(false);
            setCode(decoded);
            await lookup(decoded);
          }}
        />
      ) : null}
    </PageLayout>
  );
}
