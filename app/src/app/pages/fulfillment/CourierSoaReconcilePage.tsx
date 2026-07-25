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
  const [files, setFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<CourierSoaReconcileResult | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);

  const runReconcile = async () => {
    if (files.length === 0) {
      setError("Upload one or more J&T / SPX SOA Excel files first.");
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
      const token = await courierSoaStashUpload(files);
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

  const downloadFiles = result?.files?.length
    ? result.files
    : result?.download_token
      ? [
          {
            original_name: result.download_filename || "courier-soa.xlsx",
            format: result.format,
            courier_label: result.courier_label,
            sheet: result.sheet,
            soa_rows: result.summary.soa_rows,
            download_token: result.download_token,
            download_filename: result.download_filename,
          },
        ]
      : [];

  return (
    <PageLayout
      title="Courier SOA"
      subtitle="Compare one or more J&T / SPX SOA Excel files to shipped Online Parcels (green = matched, red = not in our set). Mix couriers in one run."
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
          Date range filters our <strong className="font-semibold text-foreground">shipped</strong> parcels for every
          courier detected in the uploads (J&amp;T and/or SPX). All selected SOA files are merged — amounts are ignored.
        </p>

        <FileZone
          label="Courier SOA Excel"
          hint="Upload one or many · J&T: Waybill Number · SPX: Tracking Number · mix both in one run"
          accept=".xlsx,.xls"
          icon={FileSpreadsheet}
          files={files}
          disabled={busy}
          multiple
          onFiles={fs => {
            setFiles(fs);
            setResult(null);
            setError(null);
          }}
        />

        <FulfillmentPressable
          className="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-primary-foreground bg-primary disabled:opacity-50"
          disabled={busy || files.length === 0 || !dateFrom || !dateTo}
          onClick={() => void runReconcile()}
        >
          {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
          Run reconciliation
        </FulfillmentPressable>

        <FulfillmentErrorBanner message={error} />

        {result && (
          <div className="space-y-4">
            <AppSuccessNotice
              message={`Detected ${result.courier_label} · ${result.summary.file_count ?? downloadFiles.length} file(s) · ${result.date_from} → ${result.date_to}`}
            />

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <StatCard label="Matched" value={result.summary.matched} tone="good" />
              <StatCard label="Missing from SOA" value={result.summary.missing_from_soa} tone="bad" />
              <StatCard label="Extra in SOA" value={result.summary.extra_in_soa} tone="bad" />
              <StatCard label="SOA unique TNs" value={result.summary.soa_unique} />
              <StatCard label="Our shipped in range" value={result.summary.our_shipped} />
              <StatCard label="SOA rows (w/ TN)" value={result.summary.soa_rows} tone="muted" />
            </div>

            {downloadFiles.length > 1 && (
              <div className="rounded-xl border border-border overflow-hidden">
                <p className="text-xs font-semibold px-3 py-2 bg-muted/40 border-b border-border">
                  Uploaded files ({downloadFiles.length})
                </p>
                <ul className="divide-y divide-border text-xs">
                  {downloadFiles.map(f => (
                    <li key={f.download_token} className="px-3 py-2 flex flex-wrap items-center gap-2 justify-between">
                      <span>
                        <span className="font-medium text-foreground">{f.original_name}</span>
                        <span className="text-muted-foreground">
                          {" "}
                          · {f.courier_label}
                          {f.sheet ? ` · ${f.sheet}` : ""} · {f.soa_rows.toLocaleString()} TN rows
                        </span>
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            )}

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
                        <th className="px-3 py-2">File</th>
                        <th className="px-3 py-2">Sheet row</th>
                        <th className="px-3 py-2">Tracking</th>
                      </tr>
                    </thead>
                    <tbody>
                      {result.extra_in_soa.map(row => (
                        <tr
                          key={`${row.source_file ?? ""}-${row.row}-${row.tracking_number}`}
                          className="border-b border-border/60 last:border-0"
                        >
                          <td className="px-3 py-2">{row.source_file || "—"}</td>
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
              <div className="space-y-3">
                <p className="text-sm text-green-800">
                  Download highlighted copies — tracking / waybill cells are green when matched and red when not in our
                  shipped set.
                </p>
                <div className="flex flex-wrap gap-2">
                  {downloadFiles.map(f => (
                    <FulfillmentPressable
                      key={f.download_token}
                      className="inline-flex items-center gap-2 px-4 py-2 font-semibold text-primary-foreground bg-primary"
                      onClick={() => triggerCourierSoaDownload(f.download_token)}
                    >
                      <Download size={16} />
                      {downloadFiles.length === 1 ? "Download Excel" : f.original_name}
                    </FulfillmentPressable>
                  ))}
                </div>
              </div>
            </FulfillmentSuccessPanel>
          </div>
        )}
      </FulfillmentSectionEnter>
    </PageLayout>
  );
}
