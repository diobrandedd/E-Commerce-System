import { useCallback, useEffect, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router";
import { Camera, ImagePlus, Loader2, QrCode, ScanLine, Video, CheckCircle2, X } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { TrackingQrScannerModal } from "@/app/components/scan/TrackingQrScannerModal";
import { usePreloadQrScanner } from "@/app/hooks/usePreloadQrScanner";
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
import { compressDeliveryPhoto } from "@/app/lib/compressDeliveryPhoto";
import { playScanBeep } from "@/app/lib/scanBeep";
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

type DocAttachment = RtsAttachment & {
  localId?: string;
  previewUrl?: string;
  uploadStatus?: "uploading" | "ready" | "error";
  uploadError?: string;
};

function revokeDocPreviews(list: DocAttachment[]) {
  for (const a of list) {
    if (a.previewUrl?.startsWith("blob:")) {
      URL.revokeObjectURL(a.previewUrl);
    }
  }
}

function readyAttachments(list: DocAttachment[]): RtsAttachment[] {
  return list
    .filter(a => a.uploadStatus === "ready" || a.uploadStatus === undefined)
    .map(({ kind, file }) => ({ kind, file }));
}

export function RTSRecordPage() {
  usePreloadQrScanner();
  const [searchParams] = useSearchParams();
  const editTracking = searchParams.get("edit")?.trim() ?? "";
  const navigate = useNavigate();

  const [catalog, setCatalog] = useState<RtsProductCatalog | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [lookupBusy, setLookupBusy] = useState(false);
  const [landingError, setLandingError] = useState<string | null>(null);
  const [modalError, setModalError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);

  const [landingTracking, setLandingTracking] = useState("");
  const [tracking, setTracking] = useState("");
  const [shop, setShop] = useState("");
  const [courier, setCourier] = useState("");
  const [vipSub, setVipSub] = useState("");
  const [dateDelivered, setDateDelivered] = useState(todayIso());
  const [dateOpened, setDateOpened] = useState(todayIso());
  const [disposition, setDisposition] = useState<RtsCaseDisposition>("standard");
  const [attachments, setAttachments] = useState<DocAttachment[]>([]);
  const [lines, setLines] = useState<RtsLineItem[]>([]);
  const [damagedLines, setDamagedLines] = useState<RtsLineItem[]>([]);
  const [bottleCodes, setBottleCodes] = useState<string[]>([]);
  const [bottleScan, setBottleScan] = useState("");
  const [caseId, setCaseId] = useState(0);
  const [mode, setMode] = useState<"create" | "append" | "edit">("create");
  const [prefill, setPrefill] = useState<RtsTrackingPrefill | null>(null);
  const [caseModalOpen, setCaseModalOpen] = useState(false);
  const [scannerOpen, setScannerOpen] = useState(false);
  const photoInputRef = useRef<HTMLInputElement>(null);
  const videoInputRef = useRef<HTMLInputElement>(null);
  const galleryInputRef = useRef<HTMLInputElement>(null);
  const editBootstrapped = useRef(false);

  const loadCatalog = useCallback(async () => {
    setLoading(true);
    try {
      const cat = await rtsGetCatalog();
      setCatalog(cat);
    } catch (e) {
      setLandingError(e instanceof ApiError ? e.message : "Could not load RTS catalog.");
    } finally {
      setLoading(false);
    }
  }, []);

  const resetCaseFields = useCallback(() => {
    setShop("");
    setCourier("");
    setVipSub("");
    setDateDelivered(todayIso());
    setDateOpened(todayIso());
    setDisposition("standard");
    setAttachments(prev => {
      revokeDocPreviews(prev);
      return [];
    });
    setLines([]);
    setDamagedLines([]);
    setBottleCodes([]);
    setBottleScan("");
    setCaseId(0);
    setMode("create");
    setPrefill(null);
    setModalError(null);
  }, []);

  const applyPrefill = useCallback((data: RtsTrackingPrefill) => {
    setPrefill(data);
    if (data.suggested.shop) setShop(data.suggested.shop);
    if (data.suggested.courier_final) setCourier(data.suggested.courier_final);
    if (data.suggested.date_delivered) setDateDelivered(data.suggested.date_delivered);
    if (data.suggested.lines.length > 0) {
      setLines(data.suggested.lines.map(l => ({ ...l, quantity: Number(l.quantity) })));
    }
  }, []);

  const loadCase = useCallback(async (tn: string, isEdit: boolean, docBase: string) => {
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
    setAttachments((c.documentation_attachments ?? []).map(a => ({
      ...a,
      previewUrl: a.kind === "image" && docBase ? `${docBase}${a.file}` : undefined,
      uploadStatus: "ready" as const,
    })));
    if (isEdit) {
      setMode("edit");
      const reusable = data.lines.filter(l => l.line_role !== "damaged");
      const damaged = data.lines.filter(l => l.line_role === "damaged");
      setLines(reusable.map(l => ({ ...l, quantity: Number(l.quantity) })));
      setDamagedLines(damaged.map(l => ({ ...l, quantity: Number(l.quantity) })));
    } else {
      setMode("append");
      setLines([]);
      setAttachments([]);
    }
    return true;
  }, []);

  const beginLookup = useCallback(async (rawTn: string, opts?: { isEdit?: boolean; skipReset?: boolean }) => {
    const tn = normalizeTracking(rawTn);
    if (!tn) {
      playScanBeep("bad");
      setLandingError("Enter or scan a tracking number first.");
      return;
    }
    if (!catalog) return;
    const docBase = catalog.doc_url_base ?? "";
    setLookupBusy(true);
    setLandingError(null);
    setModalError(null);
    setLandingTracking(tn);
    setTracking(tn);
    if (!opts?.skipReset) {
      resetCaseFields();
    }
    try {
      const isEdit = Boolean(opts?.isEdit);
      const probe = await rtsGetCase(tn);
      if (probe.exists && probe.case && !isEdit) {
        const disp = dispositionFromCase(probe.case);
        if (disp === "swapped" || disp === "leak") {
          const tab = disp === "swapped" ? "Swapped parcels" : "Leaks";
          playScanBeep("dup");
          setLandingError(
            `Tracking ${tn} is already recorded (${tab}). Open ${tab} or RTS Records — you cannot create a second case for the same TN.`,
          );
          return;
        }
        const loaded = await loadCase(tn, false, docBase);
        if (loaded) {
          playScanBeep("ok");
          setCaseModalOpen(true);
        } else {
          playScanBeep("bad");
        }
        return;
      }
      if (isEdit) {
        await loadCase(tn, true, docBase);
      } else {
        setMode("create");
        const data = await rtsTrackingPrefill(tn);
        applyPrefill(data);
      }
      if (!isEdit) playScanBeep("ok");
      setCaseModalOpen(true);
    } catch (e) {
      if (!opts?.isEdit) playScanBeep("bad");
      setLandingError(e instanceof ApiError ? e.message : "Lookup failed.");
    } finally {
      setLookupBusy(false);
    }
  }, [applyPrefill, catalog, loadCase, resetCaseFields]);

  useEffect(() => {
    void loadCatalog();
  }, [loadCatalog]);

  useEffect(() => {
    return () => revokeDocPreviews(attachments);
  }, [attachments]);

  useEffect(() => {
    if (!editTracking || !catalog || editBootstrapped.current) return;
    editBootstrapped.current = true;
    setLandingTracking(editTracking);
    void beginLookup(editTracking, { isEdit: true, skipReset: true });
  }, [editTracking, catalog, beginLookup]);

  const closeCaseModal = () => {
    setCaseModalOpen(false);
    setModalError(null);
    if (!editTracking) {
      resetCaseFields();
    }
  };

  const allowedCouriers = shop && catalog ? catalog.shop_couriers[shop] ?? [] : [];
  const banner = prefillBanner(prefill);
  const showLines = disposition !== "swapped" && mode !== "append";

  const modalTitle =
    mode === "edit" ? "Edit RTS case" : mode === "append" ? "Add products to case" : "Record RTS case";

  const onUpload = async (file: File, kind: "image" | "video") => {
    if (!catalog) return;

    const localId = `local-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    let previewUrl = URL.createObjectURL(file);
    let accepted = false;
    setAttachments(prev => {
      if (prev.length >= catalog.attachments_max) {
        return prev;
      }
      accepted = true;
      return [...prev, { kind, file: localId, localId, previewUrl, uploadStatus: "uploading" as const }];
    });
    if (!accepted) {
      URL.revokeObjectURL(previewUrl);
      setModalError(`Maximum ${catalog.attachments_max} photos/videos per case.`);
      return;
    }
    setModalError(null);

    try {
      let uploadFile = file;
      if (kind === "image") {
        uploadFile = await compressDeliveryPhoto(file);
        if (uploadFile !== file) {
          URL.revokeObjectURL(previewUrl);
          previewUrl = URL.createObjectURL(uploadFile);
          setAttachments(prev =>
            prev.map(a =>
              a.localId === localId ? { ...a, previewUrl } : a,
            ),
          );
        }
      }

      const res = await rtsUploadDocumentation(uploadFile);
      const mediaKind = res.media_kind as "image" | "video";
      URL.revokeObjectURL(previewUrl);
      setAttachments(prev =>
        prev.map(a =>
          a.localId === localId
            ? {
                kind: mediaKind,
                file: res.file_name,
                previewUrl: res.file_url,
                uploadStatus: "ready" as const,
                localId: undefined,
                uploadError: undefined,
              }
            : a,
        ),
      );
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Upload failed.";
      setAttachments(prev =>
        prev.map(a =>
          a.localId === localId
            ? { ...a, uploadStatus: "error" as const, uploadError: msg }
            : a,
        ),
      );
    }
  };

  const inferMediaKind = (file: File): "image" | "video" =>
    file.type.startsWith("video/") ? "video" : "image";

  const onMediaPicked = (file: File | null | undefined, kind?: "image" | "video") => {
    if (!file) return;
    void onUpload(file, kind ?? inferMediaKind(file));
  };

  const onGalleryPicked = (files: FileList | null) => {
    if (!files?.length) return;
    for (const file of Array.from(files)) {
      void onUpload(file, inferMediaKind(file));
    }
  };

  const removeAttachment = (index: number) => {
    setAttachments(prev => {
      const next = [...prev];
      const removed = next[index];
      if (removed?.previewUrl?.startsWith("blob:")) {
        URL.revokeObjectURL(removed.previewUrl);
      }
      next.splice(index, 1);
      return next;
    });
  };

  const docBase = catalog?.doc_url_base ?? "";
  const readyAtt = readyAttachments(attachments);
  const photoCount = readyAtt.filter(a => a.kind === "image").length;
  const videoCount = readyAtt.filter(a => a.kind === "video").length;
  const atAttachmentLimit = catalog ? attachments.length >= catalog.attachments_max : false;
  const uploadsInFlight = attachments.some(a => a.uploadStatus === "uploading");
  const videoMaxMb = catalog?.video_max_mb ?? 512;

  const buildPayload = () => ({
    tracking_number: tracking.trim(),
    shop,
    courier_final: courier,
    vip_subsource: shop === "vip" ? vipSub || null : null,
    date_delivered: dateDelivered,
    date_opened: dateOpened,
    case_disposition: disposition,
    documentation_attachments: readyAtt,
    lines: disposition === "swapped" ? [] : lines,
    damaged_lines: disposition === "leak" ? damagedLines : undefined,
    bottle_codes: bottleCodes,
    case_id: caseId,
  });

  const selectDisposition = (next: RtsCaseDisposition) => {
    if (next === "leak" && disposition !== "leak" && damagedLines.length === 0 && lines.length > 0) {
      setDamagedLines(lines);
      setLines([]);
    }
    setDisposition(next);
  };

  const validate = () => {
    if (!tracking.trim()) return "Tracking required";
    if (mode === "append") {
      if (!lines.length) return "Add at least one product line";
      return null;
    }
    if (!shop || !courier) return "Shop and courier required";
    if (uploadsInFlight) return "Wait for photo/video uploads to finish";
    if (attachments.some(a => a.uploadStatus === "error")) {
      return "One or more uploads failed — remove or add again";
    }
    if (!readyAtt.length) return "At least one documentation file required";
    if (disposition === "swapped") {
      const hasImage = readyAtt.some(a => a.kind === "image");
      const hasVideo = readyAtt.some(a => a.kind === "video");
      if (!hasImage) return "Swapped parcel requires at least one photo";
      if (!hasVideo) return "Swapped parcel requires at least one video";
      return null;
    }
    if (disposition === "leak") {
      if (!lines.length && !damagedLines.length) {
        return "Add at least one damaged or reusable product line";
      }
      if (!damagedLines.length && lines.length > 0) {
        return "Put leaked items under Damaged / leaked — reusable lines are for the case record only; scan barcodes on RTS inventory for sellable stock.";
      }
      return null;
    }
    if (!lines.length) return "At least one product line required";
    return null;
  };

  const finishScanNext = () => {
    setCaseModalOpen(false);
    setLandingTracking("");
    setTracking("");
    resetCaseFields();
    setScannerOpen(true);
  };

  const isDuplicateTrackingError = (e: unknown) => {
    if (!(e instanceof ApiError)) return false;
    const payload = e.data as { error?: string } | null;
    return payload?.error === "duplicate_tracking" || /already exists/i.test(e.message);
  };

  const save = async (andScanNext: boolean) => {
    const v = validate();
    if (v) {
      setModalError(v);
      return;
    }
    setBusy(true);
    setModalError(null);
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
        finishScanNext();
      } else {
        navigate(disposition === "swapped" ? "/rts/swapped" : disposition === "leak" ? "/rts/leaks" : "/rts/records");
      }
    } catch (e) {
      if (andScanNext && isDuplicateTrackingError(e)) {
        setToast("Already saved — scan next parcel");
        window.setTimeout(() => setToast(null), 2500);
        finishScanNext();
        return;
      }
      const msg = e instanceof ApiError ? e.message : "Save failed.";
      setModalError(msg);
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
      title="Record RTS"
      subtitle="Scan or enter tracking to start"
      actions={
        <Link to="/rts" className="text-xs text-[#C8900A] underline">RTS inventory</Link>
      }
    >
      <FulfillmentToast message={toast} />
      <FulfillmentErrorBanner message={landingError} />

      <AppSectionEnter className="max-w-md mx-auto mt-4">
        <div className="rounded-2xl border border-border bg-card p-6 space-y-4 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#C8900A]/15 text-[#C8900A]">
              <ScanLine className="h-5 w-5" />
            </div>
            <div>
              <p className="text-sm font-semibold">Tracking number</p>
              <p className="text-xs text-muted-foreground">Scan QR or type the waybill TN</p>
            </div>
          </div>

          <input
            value={landingTracking}
            onChange={e => setLandingTracking(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") void beginLookup(landingTracking);
            }}
            placeholder="Tracking number"
            className="w-full border rounded-lg px-3 py-3 text-sm font-mono"
            autoComplete="off"
            disabled={lookupBusy || Boolean(editTracking)}
          />

          <div className="flex flex-col sm:flex-row gap-2">
            <Button
              type="button"
              className="flex-1 bg-[#C8900A] hover:bg-[#C8900A]/90"
              disabled={lookupBusy || !landingTracking.trim()}
              onClick={() => void beginLookup(landingTracking)}
            >
              {lookupBusy ? <Loader2 size={16} className="animate-spin" /> : null}
              Continue
            </Button>
            <Button
              type="button"
              variant="outline"
              className="flex-1"
              disabled={lookupBusy}
              onClick={() => setScannerOpen(true)}
            >
              <QrCode size={16} /> Scan
            </Button>
          </div>
        </div>
      </AppSectionEnter>

      <AppModal
        open={caseModalOpen}
        onClose={closeCaseModal}
        panelClassName="max-w-lg w-full"
        labelledBy="rts-case-title"
      >
        <AppModalHeader
          title={modalTitle}
          titleId="rts-case-title"
          subtitle={<span className="font-mono">{tracking}</span>}
          onClose={closeCaseModal}
        />
        <AppModalBody className="space-y-4">
          {modalError && <FulfillmentErrorBanner message={modalError} />}
          <p className="text-xs rounded-lg border border-amber-200 bg-amber-50 text-amber-950 px-3 py-2">
            Saving this case does <strong className="font-semibold">not</strong> add sellable RTS stock.
            After record, scan product barcodes on the{" "}
            <Link to="/rts" className="underline font-semibold text-[#8a5c00]">RTS inventory</Link> page.
          </p>
          {lookupBusy && (
            <p className="text-xs text-muted-foreground flex items-center gap-2">
              <Loader2 size={14} className="animate-spin" /> Loading parcel data…
            </p>
          )}
          {banner && (
            <p className={cn("text-xs rounded-lg border px-3 py-2", banner.tone)}>{banner.text}</p>
          )}
          {prefill?.suggested.lines_parse_warnings?.length ? (
            <ul className="text-xs text-amber-800 list-disc pl-4">
              {prefill.suggested.lines_parse_warnings.map((w, i) => <li key={i}>{w}</li>)}
            </ul>
          ) : null}

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
              <label className="text-xs sm:col-span-2">
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

          {mode === "append" && (
            <p className="text-xs rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-3 py-2">
              This tracking number already has an RTS case — you are adding more product lines only.
            </p>
          )}

          {showLines && disposition === "leak" && (
            <div className="space-y-3">
              <RtsLineBuilder
                catalog={catalog}
                lines={damagedLines}
                onChange={setDamagedLines}
                title="Damaged / leaked"
                description="What was damaged or leaked — goes to the damaged RTS pool (not sellable)."
              />
              <RtsLineBuilder
                catalog={catalog}
                lines={lines}
                onChange={setLines}
                title="Reusable (RTS sellable)"
                description="What can go back to sellable RTS after inspection — scan barcodes on RTS inventory to add sellable stock (saving the case does not)."
              />
            </div>
          )}

          {showLines && disposition !== "leak" && (
            <RtsLineBuilder catalog={catalog} lines={lines} onChange={setLines} />
          )}

          {mode !== "append" && (
            <div className="rounded-xl border border-border p-4 space-y-2">
              <p className="text-sm font-semibold">Product barcodes (optional)</p>
              <p className="text-xs text-muted-foreground">
                Scan honey or Paragis / BRC / Cacao / Comb stickers on this return for case history.
                Saving the case does not add sellable RTS stock — after record, scan barcodes on the RTS inventory page.
              </p>
              <input
                className="w-full border rounded-lg px-2 py-2 text-sm"
                placeholder="Scan product barcode and press Enter"
                value={bottleScan}
                onChange={e => setBottleScan(e.target.value)}
                onKeyDown={e => {
                  if (e.key !== "Enter") return;
                  e.preventDefault();
                  const v = bottleScan.trim().toUpperCase();
                  if (!v) return;
                  setBottleCodes(prev => {
                    if (prev.includes(v)) {
                      playScanBeep("dup");
                      return prev;
                    }
                    playScanBeep("ok");
                    return [...prev, v];
                  });
                  setBottleScan("");
                }}
              />
              {bottleCodes.length > 0 && (
                <ul className="text-xs space-y-1">
                  {bottleCodes.map(c => (
                    <li key={c} className="flex justify-between gap-2">
                      <span className="font-mono">{c}</span>
                      <button type="button" className="text-muted-foreground" onClick={() => setBottleCodes(prev => prev.filter(x => x !== c))}>Remove</button>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          )}

          {mode !== "append" && (
          <div className="rounded-xl border border-border p-4 space-y-3">
            <div>
              <p className="text-sm font-semibold">RTS documentation</p>
              <p className="text-xs text-muted-foreground mt-0.5">
                {disposition === "swapped"
                  ? "Add at least one photo and one video (camera or gallery)."
                  : "Add photos and videos of the opened return — all attach to this case."}
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                Up to {catalog.attachments_max} files · videos up to ~{videoMaxMb}MB each
              </p>
            </div>

            <input
              ref={photoInputRef}
              type="file"
              accept="image/*"
              capture="environment"
              className="hidden"
              onChange={e => {
                onMediaPicked(e.target.files?.[0], "image");
                e.target.value = "";
              }}
            />
            <input
              ref={videoInputRef}
              type="file"
              accept="video/*"
              capture="environment"
              className="hidden"
              onChange={e => {
                onMediaPicked(e.target.files?.[0], "video");
                e.target.value = "";
              }}
            />
            <input
              ref={galleryInputRef}
              type="file"
              accept="image/*,video/*"
              multiple
              className="hidden"
              onChange={e => {
                onGalleryPicked(e.target.files);
                e.target.value = "";
              }}
            />

            <div className="grid grid-cols-2 gap-2">
              <Button
                type="button"
                variant="outline"
                className="h-auto py-3 flex flex-col gap-1.5"
                disabled={atAttachmentLimit}
                onClick={() => photoInputRef.current?.click()}
              >
                <Camera size={18} />
                <span className="text-xs font-semibold">Take photo</span>
              </Button>
              <Button
                type="button"
                variant="outline"
                className="h-auto py-3 flex flex-col gap-1.5"
                disabled={atAttachmentLimit}
                onClick={() => videoInputRef.current?.click()}
              >
                <Video size={18} />
                <span className="text-xs font-semibold">Record video</span>
              </Button>
            </div>
            <Button
              type="button"
              variant="outline"
              className="w-full h-auto py-3 flex flex-col gap-1.5"
              disabled={atAttachmentLimit}
              onClick={() => galleryInputRef.current?.click()}
            >
              <ImagePlus size={18} />
              <span className="text-xs font-semibold">Add photo or video</span>
              <span className="text-[10px] font-normal text-muted-foreground">Gallery · multiple files</span>
            </Button>

            {(photoCount > 0 || videoCount > 0) && (
              <div className="flex flex-wrap gap-2 text-xs">
                {photoCount > 0 && (
                  <span className="inline-flex items-center gap-1 text-green-800 bg-green-50 border border-green-200 rounded-full px-2 py-0.5">
                    <CheckCircle2 size={12} /> {photoCount} photo{photoCount === 1 ? "" : "s"} saved
                  </span>
                )}
                {videoCount > 0 && (
                  <span className="inline-flex items-center gap-1 text-green-800 bg-green-50 border border-green-200 rounded-full px-2 py-0.5">
                    <CheckCircle2 size={12} /> {videoCount} video{videoCount === 1 ? "" : "s"} saved
                  </span>
                )}
              </div>
            )}

            {attachments.length > 0 && (
              <div className="grid grid-cols-2 gap-2">
                {attachments.map((a, i) => {
                  const preview = a.previewUrl ?? (a.kind === "image" && docBase ? `${docBase}${a.file}` : undefined);
                  const label = a.kind === "image" ? "Photo" : "Video";
                  return (
                    <div
                      key={a.localId ?? `${a.file}-${i}`}
                      className={cn(
                        "relative rounded-lg border overflow-hidden bg-secondary/30",
                        a.uploadStatus === "error" && "border-red-300",
                      )}
                    >
                      {a.kind === "image" && preview && (
                        <img src={preview} alt={label} className="w-full h-28 object-cover" />
                      )}
                      {a.kind === "video" && preview && (
                        <video src={preview} className="w-full h-28 object-cover bg-black" controls playsInline />
                      )}
                      {a.kind === "video" && !preview && a.uploadStatus === "ready" && (
                        <div className="h-28 flex items-center justify-center text-xs text-muted-foreground px-2 text-center">
                          <Video size={20} className="mr-1 shrink-0" /> Video saved
                        </div>
                      )}
                      {a.uploadStatus === "uploading" && (
                        <div className="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-xs gap-1">
                          <Loader2 size={16} className="animate-spin" /> Uploading…
                        </div>
                      )}
                      {a.uploadStatus === "ready" && (
                        <div className="absolute top-1.5 right-1.5 rounded-full bg-green-600 text-white p-0.5">
                          <CheckCircle2 size={14} />
                        </div>
                      )}
                      {a.uploadStatus === "error" && (
                        <div className="p-2 text-[10px] text-red-700 bg-red-50 border-t border-red-200">
                          {a.uploadError ?? "Upload failed"}
                        </div>
                      )}
                      {(a.uploadStatus === "ready" || a.uploadStatus === "error") && (
                        <button
                          type="button"
                          className="absolute top-1.5 left-1.5 rounded-full bg-black/55 text-white p-0.5 hover:bg-black/75"
                          aria-label="Remove"
                          onClick={e => {
                            e.stopPropagation();
                            removeAttachment(i);
                          }}
                        >
                          <X size={14} />
                        </button>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>
          )}

          {mode !== "append" && (
            <div>
              <p className="text-sm font-semibold mb-2">Parcel condition</p>
              <div className="flex flex-wrap gap-2">
                {catalog.dispositions.map(d => (
                  <Button
                    key={d.id}
                    type="button"
                    size="sm"
                    variant={disposition === d.id ? "default" : "outline"}
                    className={disposition === d.id ? "bg-[#C8900A] hover:bg-[#C8900A]/90" : ""}
                    onClick={() => selectDisposition(d.id)}
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
                <p className="text-xs text-amber-800 mt-1">Swapped parcels need at least one photo and one video in the list above.</p>
              )}
              {disposition === "leak" && (
                <p className="text-xs text-muted-foreground mt-1">
                  Record damaged/leaked and reusable lines separately. Damaged can sync to the damaged pool;
                  sellable on-hand still requires barcode scan on RTS inventory.
                </p>
              )}
            </div>
          )}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" disabled={busy || uploadsInFlight} onClick={closeCaseModal}>
            Cancel
          </Button>
          <Button type="button" disabled={busy || uploadsInFlight} onClick={() => void save(false)} className="bg-[#C8900A] hover:bg-[#C8900A]/90">
            {busy ? <Loader2 size={14} className="animate-spin" /> : null} Save
          </Button>
          {mode === "create" && (
            <Button type="button" variant="outline" disabled={busy || uploadsInFlight} onClick={() => void save(true)}>
              Save & scan next
            </Button>
          )}
        </AppModalFooter>
      </AppModal>

      {scannerOpen ? (
        <TrackingQrScannerModal
          onClose={() => setScannerOpen(false)}
          title="Scan tracking"
          titleId="rts-scan-title"
          onDecoded={decoded => void beginLookup(decoded)}
        />
      ) : null}
    </PageLayout>
  );
}
