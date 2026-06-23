import { useCallback, useEffect, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router";
import { Loader2, QrCode } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { AppSectionEnter } from "@/app/components/layout/appFeedbackMotion";
import { RtsLineBuilder } from "@/app/components/rts/RtsLineBuilder";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { cn } from "@/app/components/ui/utils";
import {
  FulfillmentErrorBanner,
  FulfillmentToast,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { ApiError } from "@/app/lib/api";
import {
  rtsAppendLines,
  rtsCreateCase,
  rtsGetCase,
  rtsGetCatalog,
  rtsTrackingPrefill,
  rtsUpdateCase,
  rtsUploadDocumentation,
  type RtsAttachment,
  type RtsCaseDisposition,
  type RtsLineItem,
  type RtsProductCatalog,
  type RtsTrackingPrefill,
} from "@/app/lib/rtsApi";

function todayIso() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function normalizeTracking(raw: string) {
  let t = raw.trim();
  if (/^https?:\/\//i.test(t)) {
    try {
      const u = new URL(t);
      const tr = u.searchParams.get("tracking") || u.searchParams.get("Tracking");
      if (tr) return decodeURIComponent(tr).trim();
      const parts = u.pathname.split("/").filter(Boolean);
      if (parts.length) return decodeURIComponent(parts[parts.length - 1]).trim();
    } catch {
      /* keep */
    }
  }
  return t;
}

function dispositionFromCase(c: { case_disposition?: string; damaged_reason?: string | null }): RtsCaseDisposition {
  if (c.case_disposition === "swapped" || c.case_disposition === "leak" || c.case_disposition === "standard") {
    return c.case_disposition;
  }
  if (c.damaged_reason === "leak") return "leak";
  if (c.damaged_reason === "item_replaced" || c.damaged_reason === "packaging_replaced") return "swapped";
  return "standard";
}

function prefillBanner(prefill: RtsTrackingPrefill | null): { tone: string; text: string } | null {
  if (!prefill) return null;
  const any = prefill.found.parcel || prefill.found.delivery || prefill.found.csr_line;
  if (!any) {
    return { tone: "bg-secondary/60 text-muted-foreground border-border", text: "No system data for this tracking — enter details manually." };
  }
  if (prefill.manual_required.lines || prefill.manual_required.shop) {
    return {
      tone: "bg-amber-50 text-amber-900 border-amber-200",
      text: "Partial data loaded — review shop, courier, and product lines before saving.",
    };
  }
  return {
    tone: "bg-green-50 text-green-900 border-green-200",
    text: "Loaded from Online Orders — confirm shop, courier, and lines, then save.",
  };
}

export function RTSRecordPage() {
  const [searchParams] = useSearchParams();
  const editTracking = searchParams.get("edit")?.trim() ?? "";
  const navigate = useNavigate();

  const [catalog, setCatalog] = useState<RtsProductCatalog | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [prefillBusy, setPrefillBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);

  const [tracking, setTracking] = useState("");
  const [shop, setShop] = useState("");
  const [courier, setCourier] = useState("");
  const [vipSub, setVipSub] = useState("");
  const [dateDelivered, setDateDelivered] = useState(todayIso());
  const [dateOpened, setDateOpened] = useState(todayIso());
  const [disposition, setDisposition] = useState<RtsCaseDisposition>("standard");
  const [attachments, setAttachments] = useState<RtsAttachment[]>([]);
  const [lines, setLines] = useState<RtsLineItem[]>([]);
  const [caseId, setCaseId] = useState(0);
  const [mode, setMode] = useState<"create" | "append" | "edit">("create");
  const [prefill, setPrefill] = useState<RtsTrackingPrefill | null>(null);
  const [scannerOpen, setScannerOpen] = useState(false);
  const scannerRef = useRef<{ clear: () => Promise<void> } | null>(null);
  const prefillSkipRef = useRef(false);

  const loadCatalog = useCallback(async () => {
    setLoading(true);
    try {
      const cat = await rtsGetCatalog();
      setCatalog(cat);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load RTS catalog.");
    } finally {
      setLoading(false);
    }
  }, []);

  const applyPrefill = useCallback((data: RtsTrackingPrefill) => {
    setPrefill(data);
    if (data.suggested.shop) setShop(data.suggested.shop);
    if (data.suggested.courier_final) setCourier(data.suggested.courier_final);
    if (data.suggested.date_delivered) setDateDelivered(data.suggested.date_delivered);
    if (data.suggested.lines.length > 0) {
      setLines(prev => (prev.length === 0 ? data.suggested.lines.map(l => ({ ...l, quantity: Number(l.quantity) })) : prev));
    }
  }, []);

  const loadCase = useCallback(async (tn: string) => {
    const data = await rtsGetCase(tn);
    if (!data.exists || !data.case) {
      setMode("create");
      return false;
    }
    const c = data.case;
    setCaseId(c.id);
    setShop(c.shop);
    setCourier(c.courier_final);
    setVipSub(c.vip_subsource ?? "");
    setDateDelivered(c.date_delivered);
    setDateOpened(c.date_opened);
    setDisposition(dispositionFromCase(c));
    setAttachments(c.documentation_attachments ?? []);
    prefillSkipRef.current = true;
    if (editTracking) {
      setMode("edit");
      setLines(data.lines.map(l => ({ ...l, quantity: Number(l.quantity) })));
    } else {
      setMode("append");
      setLines([]);
    }
    return true;
  }, [editTracking]);

  const runPrefill = useCallback(async (tn: string) => {
    const normalized = normalizeTracking(tn);
    if (!normalized || mode === "edit") return;
    setPrefillBusy(true);
    try {
      const data = await rtsTrackingPrefill(normalized);
      applyPrefill(data);
      if (data.existing_rts_case && !editTracking) {
        await loadCase(normalized);
      }
    } catch {
      setPrefill(null);
    } finally {
      setPrefillBusy(false);
    }
  }, [applyPrefill, editTracking, loadCase, mode]);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  useEffect(() => {
    if (!editTracking) return;
    setTracking(editTracking);
    void loadCase(editTracking).catch(e => setError(e instanceof ApiError ? e.message : "Lookup failed."));
  }, [editTracking, loadCase]);

  useEffect(() => {
    if (!scannerOpen) return;
    const script = document.createElement("script");
    script.src = "https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js";
    script.async = true;
    script.onload = () => {
      const Html5QrcodeScanner = (window as unknown as { Html5QrcodeScanner: new (id: string, cfg: object, verbose: boolean) => { render: (ok: (t: string) => void, fail: () => void) => void; clear: () => Promise<void> } }).Html5QrcodeScanner;
      if (!Html5QrcodeScanner) return;
      const scanner = new Html5QrcodeScanner("rts-qr-reader", { fps: 15, qrbox: 220 }, false);
      scannerRef.current = scanner;
      scanner.render(decoded => {
        void scanner.clear();
        scannerRef.current = null;
        setScannerOpen(false);
        const tn = normalizeTracking(decoded);
        setTracking(tn);
        void loadCase(tn).then(() => void runPrefill(tn)).catch(() => setMode("create"));
      }, () => {});
    };
    document.body.appendChild(script);
    return () => {
      void scannerRef.current?.clear();
      scannerRef.current = null;
      script.remove();
    };
  }, [scannerOpen, loadCase, runPrefill]);

  const allowedCouriers = shop && catalog ? catalog.shop_couriers[shop] ?? [] : [];
  const banner = prefillBanner(prefill);
  const showLines = disposition !== "swapped" && mode !== "append";

  const onUpload = async (file: File) => {
    if (!catalog || attachments.length >= catalog.attachments_max) return;
    setBusy(true);
    try {
      const res = await rtsUploadDocumentation(file);
      setAttachments(prev => [...prev, { kind: res.media_kind as "image" | "video", file: res.file_name }]);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Upload failed.");
    } finally {
      setBusy(false);
    }
  };

  const buildPayload = () => ({
    tracking_number: tracking.trim(),
    shop,
    courier_final: courier,
    vip_subsource: shop === "vip" ? vipSub || null : null,
    date_delivered: dateDelivered,
    date_opened: dateOpened,
    case_disposition: disposition,
    documentation_attachments: attachments,
    lines: disposition === "swapped" ? [] : lines,
    case_id: caseId,
  });

  const validate = () => {
    if (!tracking.trim()) return "Tracking required";
    if (!shop || !courier) return "Shop and courier required";
    if (!attachments.length) return "At least one documentation file required";
    if (disposition === "swapped") {
      const hasImage = attachments.some(a => a.kind === "image");
      const hasVideo = attachments.some(a => a.kind === "video");
      if (!hasImage) return "Swapped parcel requires at least one photo";
      if (!hasVideo) return "Swapped parcel requires at least one video";
      return null;
    }
    if (!lines.length) return "At least one product line required";
    return null;
  };

  const save = async (andScanNext: boolean) => {
    const v = validate();
    if (v) {
      setError(v);
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const payload = buildPayload();
      if (mode === "edit") {
        await rtsUpdateCase(payload);
      } else if (mode === "append") {
        await rtsAppendLines(tracking.trim(), lines);
      } else {
        await rtsCreateCase(payload);
      }
      setToast("RTS saved successfully");
      window.setTimeout(() => setToast(null), 2500);
      if (andScanNext) {
        setTracking("");
        setLines([]);
        setAttachments([]);
        setPrefill(null);
        setDisposition("standard");
        setMode("create");
        setCaseId(0);
      } else {
        navigate(disposition === "swapped" ? "/rts/swapped" : disposition === "leak" ? "/rts/leaks" : "/rts/records");
      }
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Save failed.";
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  const onTrackingBlur = () => {
    const tn = tracking.trim();
    if (!tn) return;
    void loadCase(tn)
      .then(() => {
        if (prefillSkipRef.current) {
          prefillSkipRef.current = false;
          return;
        }
        return runPrefill(tn);
      })
      .catch(() => {
        setMode("create");
        void runPrefill(tn);
      });
  };

  if (loading || !catalog) {
    return (
      <PageLayout title="Record RTS" subtitle="Loading…">
        <div className="py-12 text-center text-muted-foreground"><Loader2 className="inline animate-spin" /></div>
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title={mode === "edit" ? "Edit RTS case" : mode === "append" ? "Add products to RTS case" : "Record RTS"}
      subtitle="Scan tracking to load parcel data, then confirm details and parcel condition"
      actions={
        <Link to="/rts" className="text-xs text-[#C8900A] underline">RTS inventory</Link>
      }
    >
      <FulfillmentToast message={toast} />
      <FulfillmentErrorBanner message={error} />

      <div className="space-y-4 max-w-3xl">
        <AppSectionEnter>
          <div className="flex gap-2">
            <input
              value={tracking}
              onChange={e => setTracking(e.target.value)}
              onBlur={onTrackingBlur}
              placeholder="Tracking number"
              className="flex-1 border rounded-lg px-3 py-2 text-sm font-mono"
              disabled={mode === "edit"}
            />
            <Button type="button" variant="outline" onClick={() => setScannerOpen(true)} className="shrink-0">
              <QrCode size={16} /> Scan
            </Button>
          </div>
          {prefillBusy && <p className="text-xs text-muted-foreground mt-1"><Loader2 size={12} className="inline animate-spin" /> Looking up parcel…</p>}
          {banner && (
            <p className={cn("text-xs mt-2 rounded-lg border px-3 py-2", banner.tone)}>{banner.text}</p>
          )}
          {prefill?.suggested.lines_parse_warnings?.length ? (
            <ul className="text-xs text-amber-800 mt-1 list-disc pl-4">
              {prefill.suggested.lines_parse_warnings.map((w, i) => <li key={i}>{w}</li>)}
            </ul>
          ) : null}
        </AppSectionEnter>

        <AppSectionEnter index={1}>
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="text-xs">
              Shop
              <select value={shop} onChange={e => { setShop(e.target.value); setCourier(""); }} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                <option value="">— Select —</option>
                {catalog.shops.map(s => <option key={s} value={s}>{s}</option>)}
              </select>
            </label>
            <label className="text-xs">
              Courier
              <select value={courier} onChange={e => setCourier(e.target.value)} disabled={!shop} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                <option value="">— Select —</option>
                {allowedCouriers.map(c => <option key={c} value={c}>{catalog.couriers[c] ?? c}</option>)}
              </select>
            </label>
            {shop === "vip" && (
              <label className="text-xs">
                VIP source
                <select value={vipSub} onChange={e => setVipSub(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                  <option value="">—</option>
                  <option value="website">Website</option>
                  <option value="facebook">Facebook</option>
                </select>
              </label>
            )}
            <label className="text-xs">
              Date delivered
              <input type="date" value={dateDelivered} onChange={e => setDateDelivered(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm" />
            </label>
            <label className="text-xs">
              Date opened
              <input type="date" value={dateOpened} onChange={e => setDateOpened(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm" />
            </label>
          </div>
        </AppSectionEnter>

        {showLines && (
          <AppSectionEnter index={2}>
            <RtsLineBuilder catalog={catalog} lines={lines} onChange={setLines} />
          </AppSectionEnter>
        )}

        <AppSectionEnter index={3}>
          <div className="rounded-xl border border-border p-4 space-y-2">
            <p className="text-sm font-semibold">RTS documentation</p>
            <input type="file" accept="image/*,video/*" onChange={e => { const f = e.target.files?.[0]; if (f) void onUpload(f); e.target.value = ""; }} />
            <ul className="text-xs text-muted-foreground">
              {attachments.map((a, i) => (
                <li key={i}>{a.kind}: {a.file}</li>
              ))}
            </ul>
          </div>
        </AppSectionEnter>

        {mode !== "append" && (
          <AppSectionEnter index={4}>
            <p className="text-sm font-semibold mb-2">Parcel condition</p>
            <div className="flex flex-wrap gap-2">
              {catalog.dispositions.map(d => (
                <Button
                  key={d.id}
                  type="button"
                  variant={disposition === d.id ? "default" : "outline"}
                  className={disposition === d.id ? "bg-[#C8900A] hover:bg-[#C8900A]/90" : ""}
                  onClick={() => setDisposition(d.id)}
                >
                  {d.label}
                </Button>
              ))}
            </div>
            {(disposition === "swapped" || disposition === "leak") && (
              <p className="text-xs text-muted-foreground mt-2">
                Before-shipment photo will be attached automatically when found in Deliveries; otherwise N/A on the log.
                {prefill?.before_shipment.available && prefill.before_shipment.download_url ? (
                  <> <a href={prefill.before_shipment.download_url} target="_blank" rel="noreferrer" className="text-[#C8900A] underline">Preview</a></>
                ) : null}
              </p>
            )}
            {disposition === "swapped" && (
              <p className="text-xs text-amber-800 mt-1">Swapped parcels require at least one photo and one video. Product lines are not recorded.</p>
            )}
          </AppSectionEnter>
        )}

        <div className="flex flex-wrap gap-2 pt-2">
          <Button type="button" disabled={busy} onClick={() => void save(false)} className="bg-[#C8900A] hover:bg-[#C8900A]/90">
            {busy ? <Loader2 size={14} className="animate-spin" /> : null} Save
          </Button>
          {mode === "create" && (
            <Button type="button" variant="outline" disabled={busy} onClick={() => void save(true)}>
              Save & scan next
            </Button>
          )}
        </div>
      </div>

      <AppModal open={scannerOpen} onClose={() => setScannerOpen(false)} panelClassName="max-w-md w-full" labelledBy="rts-scan-title">
        <AppModalHeader title="Scan tracking" titleId="rts-scan-title" onClose={() => setScannerOpen(false)} />
        <AppModalBody>
          <div id="rts-qr-reader" />
        </AppModalBody>
      </AppModal>
    </PageLayout>
  );
}
