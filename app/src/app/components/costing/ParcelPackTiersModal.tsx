import { useCallback, useEffect, useMemo, useState } from "react";
import { AlertCircle, Calculator, Info, Loader2, Package, Settings2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { ApiError } from "@/app/lib/api";
import { peso } from "@/app/lib/costingDisplay";
import {
  fetchParcelTiers,
  PARCEL_FAMILY_LABELS,
  previewParcelCost,
  saveParcelTierLine,
  type ParcelCostPreview,
  type ParcelPackTier,
  type ParcelPackTierLine,
} from "@/app/lib/parcelPackCosting";
import { cn } from "@/app/components/ui/utils";

const inputCls =
  "w-full rounded-md border border-border bg-background px-2 py-1.5 text-sm tabular-nums text-right focus:outline-none focus:border-[#C8900A] focus:ring-2 focus:ring-[#C8900A]/10";

const thCls =
  "text-[10px] font-semibold uppercase tracking-wide text-muted-foreground text-left py-2 px-3";
const thRightCls = cn(thCls, "text-right");

function costBand(min: number, typical: number, max: number): string {
  if (min === max && Math.abs(min - typical) < 0.0001) return peso(typical);
  return `${peso(min)} – ${peso(max)} · typ ${peso(typical)}`;
}

function lineCostEstimate(line: ParcelPackTierLine, qty: number): number {
  return Math.round(qty * (line.unit_cost ?? 0) * 100) / 100;
}

function isPlaceholderTier(tier: ParcelPackTier): boolean {
  return !tier.is_active || Boolean(tier.match_rule?.honey_250_350_solo);
}

type LineDraft = { qtyTyp: string; qtyMin: string; qtyMax: string };

function draftsFromTier(tier: ParcelPackTier): Record<number, LineDraft> {
  const out: Record<number, LineDraft> = {};
  for (const line of tier.lines) {
    out[line.id] = {
      qtyTyp: String(line.qty_typical),
      qtyMin: line.qty_min !== null ? String(line.qty_min) : "",
      qtyMax: line.qty_max !== null ? String(line.qty_max) : "",
    };
  }
  return out;
}

function TierMaterialsTable({
  tier,
  onSaved,
  onError,
}: {
  tier: ParcelPackTier;
  onSaved: (tier: ParcelPackTier & { tier_id?: number }) => void;
  onError: (msg: string) => void;
}) {
  const [drafts, setDrafts] = useState<Record<number, LineDraft>>(() => draftsFromTier(tier));
  const [saving, setSaving] = useState(false);
  const [dirty, setDirty] = useState(false);

  useEffect(() => {
    setDrafts(draftsFromTier(tier));
    setDirty(false);
  }, [tier]);

  const updateDraft = (lineId: number, patch: Partial<LineDraft>) => {
    setDrafts(prev => ({ ...prev, [lineId]: { ...prev[lineId], ...patch } }));
    setDirty(true);
  };

  const saveAll = async () => {
    setSaving(true);
    try {
      let latest: ParcelPackTier & { tier_id?: number } | null = null;
      for (const line of tier.lines) {
        const d = drafts[line.id];
        if (!d) continue;
        const res = await saveParcelTierLine({
          tier_id: tier.id,
          line_id: line.id,
          stock_id: line.stock_id,
          line_label: line.line_label ?? undefined,
          qty_typical: parseFloat(d.qtyTyp),
          qty_min: d.qtyMin !== "" ? parseFloat(d.qtyMin) : null,
          qty_max: d.qtyMax !== "" ? parseFloat(d.qtyMax) : null,
          unit: line.unit,
          sort_order: line.sort_order,
        });
        latest = res.tier;
      }
      if (latest) onSaved(latest);
      setDirty(false);
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Could not save tier.");
    } finally {
      setSaving(false);
    }
  };

  const hasVariableQty = tier.lines.some(l => l.qty_min !== null || l.qty_max !== null);

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm border-collapse min-w-[28rem]">
        <thead>
          <tr className="border-b border-border bg-secondary/30">
            <th className={thCls}>Material</th>
            <th className={thRightCls}>Unit cost</th>
            {hasVariableQty && <th className={thRightCls}>Min</th>}
            <th className={thRightCls}>Typical</th>
            {hasVariableQty && <th className={thRightCls}>Max</th>}
          </tr>
        </thead>
        <tbody>
          {tier.lines.map(line => {
            const d = drafts[line.id];
            const hasRange = line.qty_min !== null || line.qty_max !== null;
            const name = line.stock_name ?? line.line_label ?? "—";
            return (
              <tr key={line.id} className="border-b border-border/70 last:border-0">
                <td className="py-2 px-3 align-middle">
                  <p className="font-medium text-foreground text-xs leading-snug">{name}</p>
                </td>
                <td className="py-2 px-3 align-middle text-right tabular-nums text-muted-foreground text-xs whitespace-nowrap">
                  {peso(line.unit_cost)}
                </td>
                {hasVariableQty && (
                  <td className="py-1.5 px-1.5 align-middle w-[3.5rem]">
                    {hasRange ? (
                      <input
                        value={d?.qtyMin ?? ""}
                        onChange={e => updateDraft(line.id, { qtyMin: e.target.value })}
                        className={inputCls}
                        inputMode="decimal"
                        aria-label={`${name} min`}
                      />
                    ) : (
                      <span className="block text-right text-muted-foreground text-xs px-1">—</span>
                    )}
                  </td>
                )}
                <td className="py-1.5 px-1.5 align-middle w-[3.5rem]">
                  <input
                    value={d?.qtyTyp ?? ""}
                    onChange={e => updateDraft(line.id, { qtyTyp: e.target.value })}
                    className={inputCls}
                    inputMode="decimal"
                    aria-label={`${name} typical`}
                  />
                </td>
                {hasVariableQty && (
                  <td className="py-1.5 px-1.5 align-middle w-[3.5rem]">
                    {hasRange ? (
                      <input
                        value={d?.qtyMax ?? ""}
                        onChange={e => updateDraft(line.id, { qtyMax: e.target.value })}
                        className={inputCls}
                        inputMode="decimal"
                        aria-label={`${name} max`}
                      />
                    ) : (
                      <span className="block text-right text-muted-foreground text-xs px-1">—</span>
                    )}
                  </td>
                )}
              </tr>
            );
          })}
        </tbody>
      </table>
      <div className="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 border-t border-border bg-secondary/10">
        <p className="text-[11px] text-muted-foreground tabular-nums">
          Tier total: <span className="font-semibold text-foreground">{costBand(tier.cost_min, tier.cost_typical, tier.cost_max)}</span>
        </p>
        <button
          type="button"
          onClick={() => void saveAll()}
          disabled={saving || !dirty}
          className="text-xs font-semibold px-3 py-1.5 rounded-lg text-white disabled:opacity-40"
          style={{ background: "#C8900A" }}
        >
          {saving ? "Saving…" : "Save"}
        </button>
      </div>
    </div>
  );
}

function TierCard({
  tier,
  onTierUpdated,
  onError,
}: {
  tier: ParcelPackTier;
  onTierUpdated: (tier: ParcelPackTier & { tier_id?: number }) => void;
  onError: (msg: string) => void;
}) {
  const placeholder = isPlaceholderTier(tier);

  return (
    <article className="rounded-lg border border-border bg-card overflow-hidden">
      <header className="px-3 py-2.5 border-b border-border flex items-center justify-between gap-2">
        <h4 className="text-xs font-semibold text-foreground">{tier.label}</h4>
        {!placeholder ? (
          <span className="text-[11px] font-medium text-muted-foreground tabular-nums">
            {costBand(tier.cost_min, tier.cost_typical, tier.cost_max)}
          </span>
        ) : (
          <span className="text-[10px] font-semibold text-amber-800">TBD</span>
        )}
      </header>
      {placeholder ? (
        <p className="px-3 py-3 text-xs text-muted-foreground">Rules not defined yet.</p>
      ) : (
        <TierMaterialsTable tier={tier} onSaved={onTierUpdated} onError={onError} />
      )}
    </article>
  );
}

function PreviewResult({ preview }: { preview: ParcelCostPreview }) {
  return (
    <div className="rounded-lg border border-border bg-secondary/15 p-3 space-y-2">
      <p className="text-base font-bold text-foreground tabular-nums">
        {costBand(preview.cost_min, preview.cost_typical, preview.cost_max)}
      </p>
      {preview.tier_keys && preview.tier_keys.length > 0 && (
        <p className="text-xs text-muted-foreground">Tiers: {preview.tier_keys.join(" + ")}</p>
      )}
      {preview.tiers.map(t => (
        <div key={t.tier_key} className="text-xs">
          <p className="font-medium text-foreground">{t.label}</p>
          <ul className="mt-1 text-muted-foreground space-y-0.5">
            {t.lines.map(l => (
              <li key={l.id}>
                {l.stock_name ?? l.line_label}: {l.qty_typical} {l.unit}
              </li>
            ))}
          </ul>
        </div>
      ))}
      {preview.warnings?.map(w => (
        <p key={w} className="text-xs text-amber-800">{w}</p>
      ))}
    </div>
  );
}

function ParcelPackTiersModalContent({
  onNotice,
  onTiersChanged,
}: {
  onNotice?: (msg: string) => void;
  onTiersChanged?: () => void;
}) {
  const [tiers, setTiers] = useState<ParcelPackTier[]>([]);
  const [ready, setReady] = useState(true);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [previewError, setPreviewError] = useState<string | null>(null);
  const [itemsRaw, setItemsRaw] = useState("1x 1L Light Honey\n2x Paragis Tea");
  const [previewTierKey, setPreviewTierKey] = useState("");
  const [preview, setPreview] = useState<ParcelCostPreview | null>(null);
  const [previewBusy, setPreviewBusy] = useState(false);
  const [modalTab, setModalTab] = useState<"preview" | "rules">("preview");

  const loadTiers = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      const res = await fetchParcelTiers();
      setTiers(res.tiers);
      setReady(res.ready);
    } catch (e) {
      setLoadError(e instanceof ApiError ? e.message : "Could not load parcel tiers.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadTiers();
  }, [loadTiers]);

  const previewableTiers = useMemo(() => tiers.filter(t => !isPlaceholderTier(t)), [tiers]);

  const tiersByFamily = useMemo(() => {
    const map: Record<string, ParcelPackTier[]> = { honey: [], dept_pt_brc: [], jar_cacao: [] };
    for (const t of tiers) {
      (map[t.family] ??= []).push(t);
    }
    return map;
  }, [tiers]);

  const runPreview = async () => {
    setPreviewBusy(true);
    setPreviewError(null);
    try {
      const res = previewTierKey
        ? await previewParcelCost({ tier_key: previewTierKey })
        : await previewParcelCost({ items_raw: itemsRaw });
      setPreview(res);
    } catch (e) {
      setPreviewError(e instanceof ApiError ? e.message : "Preview failed.");
      setPreview(null);
    } finally {
      setPreviewBusy(false);
    }
  };

  const handleTierUpdated = (updated: ParcelPackTier & { tier_id?: number }) => {
    const id = updated.tier_id ?? updated.id;
    setTiers(prev =>
      prev.map(t =>
        t.id === id
          ? {
              ...t,
              cost_typical: updated.cost_typical,
              cost_min: updated.cost_min,
              cost_max: updated.cost_max,
              lines: updated.lines,
            }
          : t,
      ),
    );
    onNotice?.("Tier saved.");
    onTiersChanged?.();
  };

  if (!ready && !loading) {
    return (
      <p className="text-sm text-amber-900">
        Parcel pack costing is not installed. Run <code className="text-xs">sql/migration_phase30_parcel_pack_costing.sql</code>.
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex rounded-lg border border-border p-0.5 bg-secondary/30">
        {(["preview", "rules"] as const).map(tab => (
          <button
            key={tab}
            type="button"
            onClick={() => setModalTab(tab)}
            className={cn(
              "flex-1 text-xs font-semibold px-3 py-2 rounded-md transition-colors",
              modalTab === tab ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground",
            )}
          >
            {tab === "preview" ? "Try an order" : "Edit tier rules"}
          </button>
        ))}
      </div>

      {loadError && (
        <p className="text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{loadError}</p>
      )}

      {modalTab === "preview" ? (
        <div className="space-y-3">
          <p className="text-xs text-muted-foreground">
            Paste a real <strong className="text-foreground">Item/s</strong> string from an online parcel. We match bubble wrap, cushions, and pouches by what is in the box.
          </p>
          <textarea
            value={itemsRaw}
            onChange={e => {
              setItemsRaw(e.target.value);
              setPreviewTierKey("");
            }}
            rows={3}
            disabled={!!previewTierKey}
            className={cn(
              "w-full rounded-lg border-2 border-border bg-background px-3 py-2.5 text-sm font-mono",
              "focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 focus:outline-none",
              previewTierKey && "opacity-50",
            )}
            placeholder="e.g. 2x 1L Amber Honey, 3x Paragis Tea"
          />
          <div className="flex flex-wrap gap-2 items-center">
            <select
              value={previewTierKey}
              onChange={e => setPreviewTierKey(e.target.value)}
              className="flex-1 min-w-[10rem] rounded-lg border-2 border-border bg-background px-3 py-2 text-sm focus:border-[#C8900A] focus:outline-none"
            >
              <option value="">Parse Item/s above</option>
              {previewableTiers.map(t => (
                <option key={t.tier_key} value={t.tier_key}>{t.label}</option>
              ))}
            </select>
            <button
              type="button"
              onClick={() => void runPreview()}
              disabled={previewBusy || (!previewTierKey && !itemsRaw.trim())}
              className="inline-flex items-center gap-1.5 text-sm font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-50"
              style={{ background: "#C8900A" }}
            >
              {previewBusy ? <Loader2 size={14} className="animate-spin" /> : <Calculator size={14} />}
              Calculate
            </button>
          </div>
          {previewError && <p className="text-xs text-red-700">{previewError}</p>}
          {preview ? (
            <PreviewResult preview={preview} />
          ) : (
            <p className="text-xs text-muted-foreground text-center py-6 border border-dashed border-border rounded-lg">
              Results appear here after you calculate.
            </p>
          )}
        </div>
      ) : loading ? (
        <div className="flex justify-center py-10 text-sm text-muted-foreground gap-2">
          <Loader2 size={16} className="animate-spin" /> Loading…
        </div>
      ) : (
        <div className="space-y-4 max-h-[50vh] overflow-y-auto pr-1">
          <p className="text-xs text-muted-foreground">
            Adjust how much of each packaging material is used per parcel size. Prices come from Packaging Materials stock-in.
          </p>
          {(["honey", "dept_pt_brc", "jar_cacao"] as const).map(family => {
            const familyTiers = tiersByFamily[family] ?? [];
            if (familyTiers.length === 0) return null;
            return (
              <section key={family} className="space-y-2">
                <h3 className="text-xs font-semibold text-foreground">{PARCEL_FAMILY_LABELS[family]}</h3>
                <div className="grid gap-2 sm:grid-cols-2">
                  {familyTiers.map(tier => (
                    <TierCard key={tier.id} tier={tier} onTierUpdated={handleTierUpdated} onError={setLoadError} />
                  ))}
                </div>
              </section>
            );
          })}
        </div>
      )}
    </div>
  );
}

/** Compact callout on the main parcel tab — links to tier modal. */
export function ParcelPackLandedIntro({ onOpenTiers }: { onOpenTiers: () => void }) {
  return (
    <div className="rounded-xl border border-border bg-card px-4 py-3 flex flex-wrap items-center justify-between gap-3">
      <div className="flex items-start gap-3 min-w-0">
        <Package size={18} className="text-[#C8900A] flex-shrink-0 mt-0.5" />
        <div className="min-w-0 text-sm">
          <p className="font-semibold text-foreground">Landed cost = product COGS + parcel packaging</p>
          <p className="text-xs text-muted-foreground mt-1 leading-relaxed">
            <strong className="text-foreground">Pack (typ.)</strong> is a reference parcel for that product family
            (e.g. solo 1L honey, 1-2 PT units).{" "}
            <strong className="text-foreground">Landed</strong> adds that to manufacturing cost.{" "}
            <strong className="text-foreground">Margin</strong> uses selling price minus landed.
          </p>
        </div>
      </div>
      <button
        type="button"
        onClick={onOpenTiers}
        className="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/40 shrink-0"
      >
        <Settings2 size={14} />
        Tier rules &amp; calculator
      </button>
    </div>
  );
}

export function ParcelPackTiersModal({
  open,
  onClose,
  onNotice,
  onTiersChanged,
}: {
  open: boolean;
  onClose: () => void;
  onNotice?: (msg: string) => void;
  onTiersChanged?: () => void;
}) {
  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-3xl w-full max-h-[90vh] flex flex-col"
      labelledBy="parcel-tiers-modal-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <Settings2 size={18} className="text-[#C8900A]" />
            Parcel tier rules
          </span>
        }
        titleId="parcel-tiers-modal-title"
        onClose={onClose}
      />
      <AppModalBody className="overflow-y-auto overscroll-contain py-4">
        <ParcelPackTiersModalContent onNotice={onNotice} onTiersChanged={onTiersChanged} />
      </AppModalBody>
    </AppModal>
  );
}
