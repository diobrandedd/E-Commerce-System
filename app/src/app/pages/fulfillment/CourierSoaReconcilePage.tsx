import { useState } from "react";
import { BookOpen, CheckCircle2, Download, FileSpreadsheet, Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { FileZone } from "@/app/components/csr/FileZone";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
  FulfillmentSuccessPanel,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { AppSuccessNotice, appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { CourierSoaGuideModal } from "@/app/components/fulfillment/CourierSoaGuideModal";
import {
  courierSoaReconcile,
  courierSoaStashUpload,
  triggerCourierSoaDownload,
  type CourierSoaReconcileResult,
} from "@/app/lib/courierSoaReconcileApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

function StatCard({
  label,
  value,
  tone = "default",
}: {
  label: string;
  value: number;
  tone?: "default" | "good" | "bad" | "muted";
}) {
  return (
    <div
      className={cn(
        "rounded-xl border px-4 py-3",
        tone === "good" && "border-green-200 bg-green-50",
        tone === "bad" && "border-red-200 bg-red-50",
        tone === "muted" && "border-border/70 bg-muted/30",
        tone === "default" && "border-border bg-card",
      )}
    >
      <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</p>
      <p className="text-2xl font-bold tabular-nums mt-1">{value.toLocaleString()}</p>
    </div>
  );
}

export function CourierSoaReconcilePage() {
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<CourierSoaReconcileResult | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);

  const runReconcile = async () => {
    if (!file) {
      setError("Upload a J&T or SPX SOA Excel first.");
      return;
    }
    if (!dateFrom || !dateTo) {
      setError("Ship date from and to are required.");
      return;
    }
    setBusy(true);
    setError(null);
    setResult(null);
    try {
      const token = await courierSoaStashUpload(file);
      const data = await courierSoaReconcile({
        token,
        date_from: dateFrom,
        date_to: dateTo,
      });
      setResult(data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not reconcile courier SOA.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <PageLayout
      title="Courier SOA"
      subtitle="Compare J&T or SPX SOA Excel tracking numbers to shipped Online Parcels (green = matched, red = not in our set)."
      actions={
        <button type="button" className={appHeaderActionClass} onClick={() => setGuideOpen(true)}>
          <BookOpen size={14} />
          Guide
        </button>
      }
    >
      <CourierSoaGuideModal open={guideOpen} onClose={() => setGuideOpen(false)} />

      <FulfillmentSectionEnter className="max-w-4xl space-y-6">
        <div className="grid gap-4 sm:grid-cols-2">
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Ship date from</span>
            <input
              type="date"
              value={dateFrom}
              disabled={busy}
              onChange={e => setDateFrom(e.target.value)}
              className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-card"
            />
          </label>
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Ship date to</span>
            <input
              type="date"
              value={dateTo}
              disabled={busy}
              onChange={e => setDateTo(e.target.value)}
              className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-card"
            />
          </label>
        </div>

        <p className="text-xs text-muted-foreground">
          Date range filters only our <strong className="font-semibold text-foreground">shipped</strong> parcels for the
          detected courier (J&amp;T or SPX). The whole SOA file is compared — amounts are ignored.
        </p>

        <FileZone
          label="Courier SOA Excel"
          hint="J&T: Waybill Number column · SPX: Tracking Number column"
          accept=".xlsx,.xls"
          icon={FileSpreadsheet}
          files={file ? [file] : []}
          disabled={busy}
          multiple={false}
          onFiles={fs => {
            setFile(fs[0] ?? null);
            setResult(null);
            setError(null);
          }}
        />

        <FulfillmentPressable
          className="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-primary-foreground bg-primary disabled:opacity-50"
          disabled={busy || !file || !dateFrom || !dateTo}
          onClick={() => void runReconcile()}
        >
          {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
          Run reconciliation
        </FulfillmentPressable>

        <FulfillmentErrorBanner message={error} />

        {result && (
          <div className="space-y-4">
            <AppSuccessNotice>
              Detected <strong>{result.courier_label}</strong> · sheet <strong>{result.sheet}</strong> ·{" "}
              {result.date_from} → {result.date_to}
            </AppSuccessNotice>

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <StatCard label="Matched" value={result.summary.matched} tone="good" />
              <StatCard label="Missing from SOA" value={result.summary.missing_from_soa} tone="bad" />
              <StatCard label="Extra in SOA" value={result.summary.extra_in_soa} tone="bad" />
              <StatCard label="SOA unique TNs" value={result.summary.soa_unique} />
              <StatCard label="Our shipped in range" value={result.summary.our_shipped} />
              <StatCard label="SOA rows (w/ TN)" value={result.summary.soa_rows} tone="muted" />
            </div>

            {result.missing_from_soa.length > 0 && (
              <div className="rounded-xl border border-border overflow-hidden">
                <p className="text-xs font-semibold px-3 py-2 bg-muted/40 border-b border-border">
                  Missing from SOA (we shipped; not on file) — preview
                </p>
                <div className="overflow-x-auto max-h-64 overflow-y-auto">
                  <table className="w-full text-xs">
                    <thead>
                      <tr className="text-left text-muted-foreground border-b border-border">
                        <th className="px-3 py-2">Tracking</th>
                        <th className="px-3 py-2">Ship date</th>
                        <th className="px-3 py-2">Order no</th>
                        <th className="px-3 py-2">Courier</th>
                      </tr>
                    </thead>
                    <tbody>
                      {result.missing_from_soa.map(row => (
                        <tr key={row.tracking_number} className="border-b border-border/60 last:border-0">
                          <td className="px-3 py-2 font-mono">{row.tracking_number}</td>
                          <td className="px-3 py-2 tabular-nums">{row.ship_date ?? "—"}</td>
                          <td className="px-3 py-2 font-mono">{row.order_no || "—"}</td>
                          <td className="px-3 py-2">{row.courier || "—"}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {result.extra_in_soa.length > 0 && (
              <div className="rounded-xl border border-border overflow-hidden">
                <p className="text-xs font-semibold px-3 py-2 bg-muted/40 border-b border-border">
                  Extra in SOA (on file; not in our shipped set) — preview
                </p>
                <div className="overflow-x-auto max-h-64 overflow-y-auto">
                  <table className="w-full text-xs">
                    <thead>
                      <tr className="text-left text-muted-foreground border-b border-border">
                        <th className="px-3 py-2">Sheet row</th>
                        <th className="px-3 py-2">Tracking</th>
                      </tr>
                    </thead>
                    <tbody>
                      {result.extra_in_soa.map(row => (
                        <tr key={`${row.row}-${row.tracking_number}`} className="border-b border-border/60 last:border-0">
                          <td className="px-3 py-2 tabular-nums">{row.row}</td>
                          <td className="px-3 py-2 font-mono">{row.tracking_number}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            <FulfillmentSuccessPanel>
              <div className="flex flex-wrap items-center gap-3">
                <p className="text-sm text-green-800 flex-1 min-w-[12rem]">
                  Download the same SOA with tracking cells highlighted green (matched) or red (not in our shipped set).
                </p>
                <FulfillmentPressable
                  className="inline-flex items-center gap-2 px-4 py-2 font-semibold text-primary-foreground bg-primary"
                  onClick={() => triggerCourierSoaDownload(result.download_token)}
                >
                  <Download size={16} />
                  Download Excel
                </FulfillmentPressable>
              </div>
            </FulfillmentSuccessPanel>
          </div>
        )}
      </FulfillmentSectionEnter>
    </PageLayout>
  );
}
