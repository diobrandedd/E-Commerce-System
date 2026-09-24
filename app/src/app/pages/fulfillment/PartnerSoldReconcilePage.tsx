import { useState } from "react";
import { CheckCircle2, Download, FileSpreadsheet, Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { FileZone } from "@/app/components/csr/FileZone";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
  FulfillmentSuccessPanel,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { partnerSellerLabel } from "@/app/lib/duplicateTrackingUtils";
import {
  partnerSoldReconcile,
  partnerSoldStashUpload,
  triggerPartnerSoldDownload,
  type PartnerSeller,
  type PartnerSoldDirection,
  type PartnerSoldReconcileResult,
} from "@/app/lib/partnerSoldReconcileApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

const PARTNER_OPTIONS: { value: PartnerSeller; label: string }[] = [
  { value: "ian", label: "Ian" },
  { value: "jono", label: "Jono" },
  { value: "joven", label: "Joven" },
];

const DIRECTION_OPTIONS: { value: PartnerSoldDirection; label: string; hint: string }[] = [
  {
    value: "ours_in_theirs",
    label: "Our list in theirs",
    hint: "Check each of our Online Parcels against their sold-list Excel",
  },
  {
    value: "theirs_in_ours",
    label: "Their list in ours",
    hint: "Check each row on their Excel against our Online Parcels",
  },
];

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

export function PartnerSoldReconcilePage() {
  const [partner, setPartner] = useState<PartnerSeller>("ian");
  const [direction, setDirection] = useState<PartnerSoldDirection>("ours_in_theirs");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<PartnerSoldReconcileResult | null>(null);

  const runReconcile = async () => {
    if (!file) {
      setError("Upload the partner sold list Excel first.");
      return;
    }
    setBusy(true);
    setError(null);
    setResult(null);
    try {
      const token = await partnerSoldStashUpload(file);
      const data = await partnerSoldReconcile({
        token,
        partner_seller: partner,
        direction,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
      });
      setResult(data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not reconcile sold list.");
    } finally {
      setBusy(false);
    }
  };

  const existsRate =
    result && result.summary.checked > 0
      ? Math.round((result.summary.matched / result.summary.checked) * 100)
      : null;

  const resultDirection = result?.direction ?? direction;
  const oursInTheirs = resultDirection === "ours_in_theirs";

  return (
    <PageLayout
      title="Partner sold list"
      subtitle="Compare our Online Parcels with a partner sold-list Excel (green = Exists, red = Not found)."
    >
      <FulfillmentSectionEnter className="max-w-4xl space-y-6">
        <div className="grid gap-4 sm:grid-cols-2">
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Partner seller</span>
            <select
              value={partner}
              disabled={busy}
              onChange={e => {
                setPartner(e.target.value as PartnerSeller);
                setResult(null);
              }}
              className="w-full border border-border rounded-lg px-3 py-2.5 text-sm bg-card"
            >
              {PARTNER_OPTIONS.map(o => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </label>
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Direction</span>
            <select
              value={direction}
              disabled={busy}
              onChange={e => {
                setDirection(e.target.value as PartnerSoldDirection);
                setResult(null);
              }}
              className="w-full border border-border rounded-lg px-3 py-2.5 text-sm bg-card"
            >
              {DIRECTION_OPTIONS.map(o => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </label>
        </div>

        <div className="grid grid-cols-2 gap-3 max-w-md">
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Date from (optional)</span>
            <input
              type="date"
              value={dateFrom}
              disabled={busy}
              onChange={e => setDateFrom(e.target.value)}
              className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-card"
            />
          </label>
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Date to (optional)</span>
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
          {direction === "ours_in_theirs" ? (
            <>
              Checks whether each of <strong className="font-semibold text-foreground">our</strong> Online Parcels for{" "}
              {partnerSellerLabel(partner)} appears on their sold list (any status, including packing). Download is our
              parcel list with an Exists column.
            </>
          ) : (
            <>
              Checks whether each row on <strong className="font-semibold text-foreground">their</strong> Excel exists in
              our Online Parcels for {partnerSellerLabel(partner)}. Download highlights their sheet.
            </>
          )}{" "}
          Optional dates use ship date when set, or created date for packing.
        </p>

        <FileZone
          label="Partner sold list Excel"
          hint="Columns: Order Date, Order No, Customer, … Tracking Number (same layout Ian sends monthly)"
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
          disabled={busy || !file}
          onClick={() => void runReconcile()}
        >
          {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
          Run reconciliation
        </FulfillmentPressable>

        <FulfillmentErrorBanner message={error} />

        {result && (
          <div className="space-y-4">
            <AppSuccessNotice>
              {oursInTheirs ? (
                <>
                  Our parcels for <strong>{partnerSellerLabel(result.partner_seller)}</strong> vs sheet{" "}
                  <strong>{result.sheet}</strong> — {result.summary.checked.toLocaleString()} checked
                  {existsRate !== null ? ` · ${existsRate}% on their list` : ""}
                </>
              ) : (
                <>
                  Sheet <strong>{result.sheet}</strong> — {result.summary.checked.toLocaleString()} order lines checked
                  {existsRate !== null ? ` · ${existsRate}% exist` : ""}
                </>
              )}
            </AppSuccessNotice>

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <StatCard label="Exists" value={result.summary.matched} tone="good" />
              <StatCard label="Not found" value={result.summary.not_found} tone="bad" />
              <StatCard label="Checked" value={result.summary.checked} />
              {oursInTheirs ? (
                <StatCard label="Our parcels" value={result.summary.our_parcels ?? result.summary.checked} />
              ) : (
                <StatCard label="Excluded (canceled / failed)" value={result.summary.excluded_status} tone="muted" />
              )}
            </div>

            <div className="grid gap-3 sm:grid-cols-3 text-xs text-muted-foreground">
              <p>Their Excel rows: {result.summary.total_rows.toLocaleString()}</p>
              <p>Their rows with order no.: {result.summary.rows_with_order_no.toLocaleString()}</p>
              {!oursInTheirs && (
                <p>Skipped (no order no.): {result.summary.skipped_no_order_no.toLocaleString()}</p>
              )}
            </div>

            {result.preview.length > 0 && (
              <div className="rounded-xl border border-border overflow-hidden">
                <p className="text-xs font-semibold px-3 py-2 bg-muted/40 border-b border-border">
                  Preview ({oursInTheirs ? "our parcels" : "their rows"}, first)
                </p>
                <div className="overflow-x-auto">
                  <table className="w-full text-xs">
                    <thead>
                      <tr className="text-left text-muted-foreground border-b border-border">
                        <th className="px-3 py-2">#</th>
                        <th className="px-3 py-2">Order No</th>
                        {oursInTheirs && <th className="px-3 py-2">Tracking</th>}
                        {oursInTheirs && <th className="px-3 py-2">Status</th>}
                        <th className="px-3 py-2">Exists</th>
                      </tr>
                    </thead>
                    <tbody>
                      {result.preview.map(row => (
                        <tr key={`${row.row}-${row.order_no ?? ""}`} className="border-b border-border/60 last:border-0">
                          <td className="px-3 py-2 tabular-nums">{row.row}</td>
                          <td className="px-3 py-2 font-mono">{row.order_no ?? "—"}</td>
                          {oursInTheirs && (
                            <td className="px-3 py-2 font-mono">{row.tracking_number ?? "—"}</td>
                          )}
                          {oursInTheirs && (
                            <td className="px-3 py-2">{row.parcel_status ?? "—"}</td>
                          )}
                          <td className="px-3 py-2">
                            {row.status === "matched" ? (
                              <span className="text-green-700 font-medium">Exists</span>
                            ) : row.status === "missing" ? (
                              <span className="text-red-700 font-medium">Not found</span>
                            ) : (
                              <span className="text-muted-foreground">Skipped</span>
                            )}
                          </td>
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
                  {oursInTheirs
                    ? "Download our parcel list with Exists / Not found (green / red) for each order."
                    : "Download their workbook with Order No highlighted green (Exists) or red (Not found), plus an Exists column."}
                </p>
                <FulfillmentPressable
                  className="inline-flex items-center gap-2 px-4 py-2 font-semibold text-primary-foreground bg-primary"
                  onClick={() => triggerPartnerSoldDownload(result.download_token)}
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
