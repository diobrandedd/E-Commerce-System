import { useCallback, useEffect, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router";
import { Loader2, QrCode } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { RtsLineBuilder } from "@/app/components/rts/RtsLineBuilder";
import { PageLayout } from "@/app/components/PageLayout";
import { FulfillmentErrorBanner, FulfillmentToast } from "@/app/components/csr/onlineFulfillmentMotion";
import { ApiError } from "@/app/lib/api";
import {
  rtsAppendLines,
  rtsCreateCase,
  rtsDeliveryMatch,
  rtsGetCase,
  rtsGetCatalog,
  rtsUpdateCase,
  rtsUploadDocumentation,
  type RtsAttachment,
  type RtsLineItem,
  type RtsProductCatalog,
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

export function RTSRecordPage() {
  const [searchParams] = useSearchParams();
  const editTracking = searchParams.get("edit")?.trim() ?? "";
  const navigate = useNavigate();

  const [catalog, setCatalog] = useState<RtsProductCatalog | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);

  const [tracking, setTracking] = useState("");
  const [shop, setShop] = useState("");
  const [courier, setCourier] = useState("");
  const [vipSub, setVipSub] = useState("");
  const [dateDelivered, setDateDelivered] = useState(todayIso());
  const [dateOpened, setDateOpened] = useState(todayIso());
  const [damaged, setDamaged] = useState(false);
  const [damagedReason, setDamagedReason] = useState("");
  const [attachments, setAttachments] = useState<RtsAttachment[]>([]);
  const [lines, setLines] = useState<RtsLineItem[]>([]);
  const [caseId, setCaseId] = useState(0);
  const [mode, setMode] = useState<"create" | "append" | "edit">("create");
  const [deliveryMatch, setDeliveryMatch] = useState(false);
  const [scannerOpen, setScannerOpen] = useState(false);
  const scannerRef = useRef<{ clear: () => Promise<void> } | null>(null);

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

  const loadCase = useCallback(async (tn: string) => {
    const data = await rtsGetCase(tn);
    if (!data.exists || !data.case) {
      setMode("create");
      return;
    }
    const c = data.case;
    setCaseId(c.id);
    setShop(c.shop);
    setCourier(c.courier_final);
    setVipSub(c.vip_subsource ?? "");
    setDateDelivered(c.date_delivered);
    setDateOpened(c.date_opened);
    setDamaged(Boolean(c.damaged_reason));
    setDamagedReason(c.damaged_reason ?? "");
    setAttachments(c.documentation_attachments ?? []);
    if (editTracking) {
      setMode("edit");
      setLines(data.lines.map(l => ({ ...l, quantity: Number(l.quantity) })));
    } else {
      setMode("append");
      setLines([]);
    }
  }, [editTracking]);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  useEffect(() => {
    if (!editTracking) return;
    setTracking(editTracking);
    void loadCase(editTracking).catch(e => setError(e instanceof ApiError ? e.message : "Lookup failed."));
  }, [editTracking, loadCase]);

  useEffect(() => {
    if (!tracking.trim()) {
      setDeliveryMatch(false);
      return;
    }
    const t = setTimeout(() => {
      void rtsDeliveryMatch(tracking.trim())
        .then(r => setDeliveryMatch(r.match))
        .catch(() => setDeliveryMatch(false));
    }, 400);
    return () => clearTimeout(t);
  }, [tracking, damaged]);

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
        void loadCase(tn).catch(() => setMode("create"));
      }, () => {});
    };
    document.body.appendChild(script);
    return () => {
      void scannerRef.current?.clear();
      scannerRef.current = null;
      script.remove();
    };
  }, [scannerOpen, loadCase]);

  const allowedCouriers = shop && catalog ? catalog.shop_couriers[shop] ?? [] : [];

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
    damaged_reason: damaged ? damagedReason : "",
    documentation_attachments: attachments,
    lines,
    case_id: caseId,
  });

  const validate = () => {
    if (!tracking.trim()) return "Tracking required";
    if (!shop || !courier) return "Shop and courier required";
    if (!lines.length) return "At least one product line required";
    if (!attachments.length) return "At least one documentation file required";
    if (damaged && !damagedReason) return "Damage reason required";
    if (damaged && !deliveryMatch) return "Pre-delivery photo required for damaged cases";
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
        setMode("create");
        setCaseId(0);
      } else {
        navigate("/rts/records");
      }
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Save failed.";
      setError(msg);
    } finally {
      setBusy(false);
    }
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
      subtitle="Scan or enter tracking, add documentation and product lines"
      actions={
        <Link to="/rts" className="text-xs text-[#C8900A] underline">RTS inventory</Link>
      }
    >
      <FulfillmentToast message={toast} />
      <FulfillmentErrorBanner message={error} />

      <div className="space-y-4 max-w-3xl">
        <div className="flex gap-2">
          <input
            value={tracking}
            onChange={e => setTracking(e.target.value)}
            onBlur={() => {
              const tn = tracking.trim();
              if (tn) void loadCase(tn).catch(() => setMode("create"));
            }}
            placeholder="Tracking number"
            className="flex-1 border rounded-lg px-3 py-2 text-sm font-mono"
            disabled={mode === "edit"}
          />
          <button type="button" onClick={() => setScannerOpen(true)} className="border rounded-lg px-3 py-2 text-sm">
            <QrCode size={16} className="inline" /> Scan
          </button>
        </div>

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

        <div className="rounded-xl border border-border p-4 space-y-2">
          <p className="text-sm font-semibold">Documentation</p>
          <input type="file" accept="image/*,video/*" onChange={e => { const f = e.target.files?.[0]; if (f) void onUpload(f); e.target.value = ""; }} />
          <ul className="text-xs text-muted-foreground">
            {attachments.map((a, i) => (
              <li key={i}>{a.kind}: {a.file}</li>
            ))}
          </ul>
        </div>

        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={damaged} onChange={e => setDamaged(e.target.checked)} />
          Mark as damaged
        </label>
        {damaged && (
          <select value={damagedReason} onChange={e => setDamagedReason(e.target.value)} className="border rounded-lg px-2 py-1.5 text-sm">
            <option value="">— Damage reason —</option>
            {catalog.damaged_reasons.map(d => <option key={d.id} value={d.id}>{d.label}</option>)}
          </select>
        )}
        {damaged && !deliveryMatch && tracking.trim() && (
          <p className="text-xs text-amber-800">No matching pre-delivery photo found in Deliveries for this tracking.</p>
        )}

        <RtsLineBuilder catalog={catalog} lines={lines} onChange={setLines} />

        <div className="flex flex-wrap gap-2 pt-2">
          <button type="button" disabled={busy} onClick={() => void save(false)} className="text-sm font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {busy ? <Loader2 size={14} className="inline animate-spin" /> : null} Save
          </button>
          {mode === "create" && (
            <button type="button" disabled={busy} onClick={() => void save(true)} className="text-sm font-medium px-4 py-2 rounded-lg border border-border">
              Save & scan next
            </button>
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
