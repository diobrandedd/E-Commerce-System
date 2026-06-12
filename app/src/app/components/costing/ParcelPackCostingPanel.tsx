import { useCallback, useEffect, useMemo, useState } from "react";
import { AlertCircle, Calculator, Loader2, Save } from "lucide-react";
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
  "w-full rounded-lg border border-border bg-background px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#C8900A]/30";

function costBand(min: number, typical: number, max: number): string {
  if (min === max && min === typical) return peso(typical);
  return `${peso(min)} – ${peso(max)} (typ. ${peso(typical)})`;
}

function TierLineEditor({
  tier,
  line,
  onSaved,
  onError,
}: {
  tier: ParcelPackTier;
  line: ParcelPackTierLine;
  onSaved: (tier: ParcelPackTier) => void;
  onError: (msg: string) => void;
}) {
  const [qtyTyp, setQtyTyp] = useState(String(line.qty_typical));
  const [qtyMin, setQtyMin] = useState(line.qty_min !== null ? String(line.qty_min) : "");
  const [qtyMax, setQtyMax] = useState(line.qty_max !== null ? String(line.qty_max) : "");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setQtyTyp(String(line.qty_typical));
    setQtyMin(line.qty_min !== null ? String(line.qty_min) : "");
    setQtyMax(line.qty_max !== null ? String(line.qty_max) : "");
  }, [line]);

  const save = async () => {
    setSaving(true);
    try {
      const res = await saveParcelTierLine({
        tier_id: tier.id,
        line_id: line.id,
        stock_id: line.stock_id,
        line_label: line.line_label ?? undefined,
        qty_typical: parseFloat(qtyTyp),
        qty_min: qtyMin !== "" ? parseFloat(qtyMin) : null,
        qty_max: qtyMax !== "" ? parseFloat(qtyMax) : null,
        unit: line.unit,
        sort_order: line.sort_order,
      });
      onSaved(res.tier);
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Could not save line.");
    } finally {
      setSaving(false);
    }
  };

  const hasRange = line.qty_min !== null || line.qty_max !== null;

  return (
    <div className="grid gap-2 sm:grid-cols-[1fr_auto_auto_auto_auto] sm:items-end text-xs border-t border-border pt-2 first:border-t-0 first:pt-0">
      <div className="min-w-0">
        <p className="font-medium text-foreground truncate">{line.stock_name ?? line.line_label}</p>
        <p className="text-muted-foreground">
          WAC {peso(line.unit_cost)}/{line.unit}
          {line.cost_typical !== undefined && (
            <> · line {costBand(line.cost_min ?? line.cost_typical, line.cost_typical, line.cost_max ?? line.cost_typical)}</>
          )}
        </p>
      </div>
      {hasRange && (
        <label className="block">
          <span className="text-[10px] uppercase text-muted-foreground">Min</span>
          <input value={qtyMin} onChange={e => setQtyMin(e.target.value)} className={inputCls} inputMode="decimal" />
        </label>
      )}
      <label className="block">
        <span className="text-[10px] uppercase text-muted-foreground">Typical</span>
        <input value={qtyTyp} onChange={e => setQtyTyp(e.target.value)} className={inputCls} inputMode="decimal" required />
      </label>
      {hasRange && (
        <label className="block">
          <span className="text-[10px] uppercase text-muted-foreground">Max</span>
          <input value={qtyMax} onChange={e => setQtyMax(e.target.value)} className={inputCls} inputMode="decimal" />
        </label>
      )}
      <button
        type="button"
        onClick={() => void save()}
        disabled={saving}
        className="inline-flex items-center justify-center gap-1 rounded-lg border border-border px-2.5 py-1.5 text-xs font-semibold hover:bg-secondary/50 disabled:opacity-50"
      >
        {saving ? <Loader2 size={12} className="animate-spin" /> : <Save size={12} />}
        Save
      </button>
    </div>
  );
}

function TierCard({
  tier,
  onTierUpdated,
  onError,
}: {
  tier: ParcelPackTier;
  onTierUpdated: (tier: ParcelPackTier) => void;
  onError: (msg: string) => void;
}) {
  const isTbd = Boolean(tier.match_rule?.honey_250_350_solo);

  return (
    <div className="rounded-xl border border-border bg-card overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
      <div className="px-4 py-3 border-b border-border bg-secondary/20 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h4 className="text-sm font-semibold text-foreground">{tier.label}</h4>
          <p className="text-[11px] text-muted-foreground font-mono">{tier.tier_key}</p>
        </div>
        <div className="text-right text-xs">
          {isTbd ? (
            <span className="text-amber-700 font-semibold">TBD — research later</span>
          ) : (
            <span className="font-semibold text-foreground tabular-nums">
              {costBand(tier.cost_min, tier.cost_typical, tier.cost_max)}
            </span>
          )}
        </div>
      </div>
      <div className="px-4 py-3 space-y-2">
        {tier.lines.length === 0 ? (
          <p className="text-xs text-muted-foreground">No material lines.</p>
        ) : (
          tier.lines.map(line => (
            <TierLineEditor
              key={line.id}
              tier={tier}
              line={line}
              onSaved={onTierUpdated}
              onError={onError}
            />
          ))
        )}
      </div>
    </div>
  );
}

export function ParcelPackCostingPanel({ onNotice }: { onNotice?: (msg: string) => void }) {
  const [tiers, setTiers] = useState<ParcelPackTier[]>([]);
  const [ready, setReady] = useState(true);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [itemsRaw, setItemsRaw] = useState("1x 1L Light Honey\n2x Paragis Tea");
  const [previewTierKey, setPreviewTierKey] = useState("");
  const [preview, setPreview] = useState<ParcelCostPreview | null>(null);
  const [previewBusy, setPreviewBusy] = useState(false);

  const loadTiers = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetchParcelTiers();
      setTiers(res.tiers);
      setReady(res.ready);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load parcel tiers.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadTiers();
  }, [loadTiers]);

  const tiersByFamily = useMemo(() => {
    const map: Record<string, ParcelPackTier[]> = { honey: [], dept_pt_brc: [], jar_cacao: [] };
    for (const t of tiers) {
      (map[t.family] ??= []).push(t);
    }
    return map;
  }, [tiers]);

  const runPreview = async () => {
    setPreviewBusy(true);
    setError(null);
    try {
      const res = previewTierKey
        ? await previewParcelCost({ tier_key: previewTierKey })
        : await previewParcelCost({ items_raw: itemsRaw });
      setPreview(res);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Preview failed.");
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
    onNotice?.("Tier line saved.");
  };

  if (!ready && !loading) {
    return (
      <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex items-start gap-2">
        <AlertCircle size={16} className="mt-0.5 flex-shrink-0" />
        <span>Parcel pack costing tables are not installed. Run <code className="text-xs">sql/migration_phase30_parcel_pack_costing.sql</code>.</span>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
          <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
        </div>
      )}

      <div className="rounded-xl border border-border bg-card p-4 space-y-3" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
        <div className="flex items-center gap-2">
          <Calculator size={16} className="text-[#C8900A]" />
          <h3 className="text-sm font-semibold text-foreground">Preview calculator</h3>
        </div>
        <p className="text-xs text-muted-foreground">
          Paste an order <strong>Item/s</strong> string (same format as online parcels) or pick a single tier. Mixed parcels sum applicable family tiers.
        </p>
        <textarea
          value={itemsRaw}
          onChange={e => setItemsRaw(e.target.value)}
          rows={3}
          className={cn(inputCls, "font-mono text-xs")}
          placeholder="e.g. 2x 1L Amber Honey, 3x Paragis Tea"
        />
        <div className="flex flex-wrap gap-2 items-center">
          <select value={previewTierKey} onChange={e => setPreviewTierKey(e.target.value)} className={cn(inputCls, "max-w-xs")}>
            <option value="">— From Item/s above —</option>
            {tiers.map(t => (
              <option key={t.tier_key} value={t.tier_key}>
                {t.label}
              </option>
            ))}
          </select>
          <button
            type="button"
            onClick={() => void runPreview()}
            disabled={previewBusy}
            className="inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-2 rounded-lg text-white disabled:opacity-50"
            style={{ background: "#C8900A" }}
          >
            {previewBusy ? <Loader2 size={14} className="animate-spin" /> : <Calculator size={14} />}
            Preview cost
          </button>
        </div>
        {preview && (
          <div className="rounded-lg border border-border bg-secondary/15 p-3 text-xs space-y-2">
            <p className="font-semibold text-foreground tabular-nums">
              Total parcel packaging: {costBand(preview.cost_min, preview.cost_typical, preview.cost_max)}
            </p>
            {preview.tier_keys && preview.tier_keys.length > 0 && (
              <p className="text-muted-foreground">Tiers: {preview.tier_keys.join(" + ")}</p>
            )}
            {preview.tiers.map(t => (
              <div key={t.tier_key} className="border-t border-border pt-2 first:border-t-0 first:pt-0">
                <p className="font-medium">{t.label}</p>
                <ul className="mt-1 space-y-0.5 text-muted-foreground">
                  {t.lines.map(l => (
                    <li key={l.id}>
                      {l.stock_name ?? l.line_label}: {l.qty_min ?? l.qty_typical}–{l.qty_max ?? l.qty_typical} {l.unit} @ {peso(l.unit_cost)}
                    </li>
                  ))}
                </ul>
              </div>
            ))}
            {preview.warnings?.map(w => (
              <p key={w} className="text-amber-800">{w}</p>
            ))}
          </div>
        )}
      </div>

      {loading ? (
        <div className="flex items-center gap-2 text-sm text-muted-foreground py-6 justify-center">
          <Loader2 size={16} className="animate-spin" /> Loading tiers…
        </div>
      ) : (
        (["honey", "dept_pt_brc", "jar_cacao"] as const).map(family => {
          const familyTiers = tiersByFamily[family] ?? [];
          if (familyTiers.length === 0) return null;
          return (
            <section key={family} className="space-y-3">
              <h3 className="text-sm font-semibold text-foreground">{PARCEL_FAMILY_LABELS[family]}</h3>
              <div className="grid gap-3 lg:grid-cols-2">
                {familyTiers.map(tier => (
                  <TierCard
                    key={tier.id}
                    tier={tier}
                    onTierUpdated={handleTierUpdated}
                    onError={msg => setError(msg)}
                  />
                ))}
              </div>
            </section>
          );
        })
      )}

      <p className="text-[11px] text-muted-foreground italic">
        Parcel packaging is estimated; bubble wrap usage varies 2–4 per order. Manufacturing COGS (product formula) is unchanged — this layer models fulfillment materials only.
      </p>
    </div>
  );
}
