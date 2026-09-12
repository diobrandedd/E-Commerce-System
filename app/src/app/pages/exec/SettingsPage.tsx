import { useCallback, useEffect, useState } from "react";
import { Loader2, ShoppingCart, Tag, Droplets, Leaf, Shield } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { Switch } from "@/app/components/ui/switch";
import {
  AppErrorBanner,
  AppSuccessNotice,
} from "@/app/components/layout/appFeedbackMotion";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { skuBarcodesApi, type SkuBarcodeRow } from "@/app/lib/skuBarcodes";
import { DEFAULT_ML_PER_US_GALLON } from "@/app/lib/honey";

interface PosPinRow {
  id: number;
  label: string;
  is_active: boolean;
  created_at: string;
}

function skuRowKey(r: SkuBarcodeRow): string {
  return r.kind === "honey" ? `honey:${r.variant}:${r.size}` : `dept:${r.product_key}`;
}

function formatPriceInput(n: number | null): string {
  if (n == null || !Number.isFinite(n)) return "";
  return Number.isInteger(n) ? String(n) : n.toFixed(2);
}

function parsePriceInput(raw: string): number | null {
  const t = raw.trim();
  if (t === "") return null;
  const n = Number(t);
  if (!Number.isFinite(n) || n < 0) return null;
  return Math.round(n * 100) / 100;
}

function pricesEqual(a: number | null, b: number | null): boolean {
  if (a == null && b == null) return true;
  if (a == null || b == null) return false;
  return Math.abs(a - b) < 0.005;
}

export function SettingsPage() {
  const [systemName, setSystemName] = useState("Pyx Tracker Management System");
  const [timezone, setTimezone] = useState("Asia/Manila");
  const [exportFormat, setExportFormat] = useState("xlsx");

  const [pins, setPins] = useState<PosPinRow[]>([]);
  const [pinsLoading, setPinsLoading] = useState(true);
  const [pinForms, setPinForms] = useState<Record<number, { label: string; pin: string; pinConfirm: string; isActive: boolean }>>({});
  const [createForm, setCreateForm] = useState({ label: "POS Terminal", pin: "", pinConfirm: "" });
  const [showCreate, setShowCreate] = useState(false);
  const [posBusy, setPosBusy] = useState<number | "create" | null>(null);
  const [posMsg, setPosMsg] = useState<{ type: "ok" | "err"; text: string } | null>(null);

  const [skuRows, setSkuRows] = useState<SkuBarcodeRow[]>([]);
  const [skuCodes, setSkuCodes] = useState<Record<string, string>>({});
  const [skuPrices, setSkuPrices] = useState<Record<string, string>>({});
  const [skuLoading, setSkuLoading] = useState(true);
  const [skuSaving, setSkuSaving] = useState(false);
  const [skuOk, setSkuOk] = useState<string | null>(null);
  const [skuErr, setSkuErr] = useState<string | null>(null);

  const [mlPerGallon, setMlPerGallon] = useState(String(DEFAULT_ML_PER_US_GALLON));
  const [mlDefault, setMlDefault] = useState(DEFAULT_ML_PER_US_GALLON);
  const [mlLoading, setMlLoading] = useState(true);
  const [mlSaving, setMlSaving] = useState(false);
  const [mlOk, setMlOk] = useState<string | null>(null);
  const [mlErr, setMlErr] = useState<string | null>(null);

  const [ptGramsTea, setPtGramsTea] = useState("2");
  const [ptBagsPouch, setPtBagsPouch] = useState("24");
  const [ptDefaultGrams, setPtDefaultGrams] = useState(2);
  const [ptDefaultBags, setPtDefaultBags] = useState(24);
  const [ptGramsPouch, setPtGramsPouch] = useState(48);
  const [ptBagPoolDeduct, setPtBagPoolDeduct] = useState(true);
  const [ptLoading, setPtLoading] = useState(true);
  const [ptSaving, setPtSaving] = useState(false);
  const [ptOk, setPtOk] = useState<string | null>(null);
  const [ptErr, setPtErr] = useState<string | null>(null);

  const [brcGramsPouch, setBrcGramsPouch] = useState("100");
  const [brcGramsBag, setBrcGramsBag] = useState("1000");
  const [brcDefaultPouch, setBrcDefaultPouch] = useState(100);
  const [brcDefaultBag, setBrcDefaultBag] = useState(1000);
  const [brcPouchesPerBag, setBrcPouchesPerBag] = useState(10);
  const [brcBagPoolDeduct, setBrcBagPoolDeduct] = useState(true);
  const [brcLoading, setBrcLoading] = useState(true);
  const [brcSaving, setBrcSaving] = useState(false);
  const [brcOk, setBrcOk] = useState<string | null>(null);
  const [brcErr, setBrcErr] = useState<string | null>(null);

  const [cacaoGramsJar, setCacaoGramsJar] = useState("220");
  const [cacaoGramsBag, setCacaoGramsBag] = useState("1000");
  const [cacaoDefaultJar, setCacaoDefaultJar] = useState(220);
  const [cacaoDefaultBag, setCacaoDefaultBag] = useState(1000);
  const [cacaoJarsPerBag, setCacaoJarsPerBag] = useState(1000 / 220);
  const [cacaoBagPoolDeduct, setCacaoBagPoolDeduct] = useState(true);
  const [cacaoLoading, setCacaoLoading] = useState(true);
  const [cacaoSaving, setCacaoSaving] = useState(false);
  const [cacaoOk, setCacaoOk] = useState<string | null>(null);
  const [cacaoErr, setCacaoErr] = useState<string | null>(null);

  const [adminPwConfigured, setAdminPwConfigured] = useState(false);
  const [adminPwLoading, setAdminPwLoading] = useState(true);
  const [adminPwSaving, setAdminPwSaving] = useState(false);
  const [adminPwCurrent, setAdminPwCurrent] = useState("");
  const [adminPwNew, setAdminPwNew] = useState("");
  const [adminPwConfirm, setAdminPwConfirm] = useState("");
  const [adminPwOk, setAdminPwOk] = useState<string | null>(null);
  const [adminPwErr, setAdminPwErr] = useState<string | null>(null);

  const loadPins = useCallback(async () => {
    setPinsLoading(true);
    try {
      const data = await apiGet<{ pins: PosPinRow[] }>("settings.php", "pos_pins_list");
      setPins(data.pins);
      const forms: Record<number, { label: string; pin: string; pinConfirm: string; isActive: boolean }> = {};
      data.pins.forEach(p => {
        forms[p.id] = { label: p.label, pin: "", pinConfirm: "", isActive: p.is_active };
      });
      setPinForms(forms);
      setShowCreate(data.pins.length === 0);
    } catch (e) {
      setPosMsg({ type: "err", text: e instanceof ApiError ? e.message : "Could not load POS terminals." });
    } finally {
      setPinsLoading(false);
    }
  }, []);

  const loadSkuRows = useCallback(async () => {
    setSkuLoading(true);
    try {
      const data = await skuBarcodesApi.list();
      setSkuRows(data.rows);
      const codes: Record<string, string> = {};
      const prices: Record<string, string> = {};
      data.rows.forEach(r => {
        const key = skuRowKey(r);
        codes[key] = r.code ?? "";
        prices[key] = formatPriceInput(r.selling_price_hint);
      });
      setSkuCodes(codes);
      setSkuPrices(prices);
    } catch (e) {
      setSkuErr(e instanceof ApiError ? e.message : "Could not load barcode settings.");
    } finally {
      setSkuLoading(false);
    }
  }, []);

  const loadHoneyVolume = useCallback(async () => {
    setMlLoading(true);
    setMlErr(null);
    try {
      const data = await apiGet<{ ml_per_gallon: number; default_ml_per_gallon: number }>(
        "settings.php",
        "honey_volume_get",
      );
      setMlPerGallon(String(data.ml_per_gallon));
      setMlDefault(data.default_ml_per_gallon ?? DEFAULT_ML_PER_US_GALLON);
    } catch (e) {
      setMlErr(e instanceof ApiError ? e.message : "Could not load honey volume setting.");
    } finally {
      setMlLoading(false);
    }
  }, []);

  const loadPtBom = useCallback(async () => {
    setPtLoading(true);
    setPtErr(null);
    try {
      const data = await apiGet<{
        grams_per_tea_bag: number;
        tea_bags_per_pouch: number;
        grams_per_pouch: number;
        bag_pool_deduct: boolean;
        default_grams_per_tea_bag: number;
        default_tea_bags_per_pouch: number;
      }>("settings.php", "pt_bom_get");
      setPtGramsTea(String(data.grams_per_tea_bag));
      setPtBagsPouch(String(data.tea_bags_per_pouch));
      setPtGramsPouch(data.grams_per_pouch);
      setPtBagPoolDeduct(data.bag_pool_deduct !== false);
      setPtDefaultGrams(data.default_grams_per_tea_bag);
      setPtDefaultBags(data.default_tea_bags_per_pouch);
    } catch (e) {
      setPtErr(e instanceof ApiError ? e.message : "Could not load Paragis BOM settings.");
    } finally {
      setPtLoading(false);
    }
  }, []);

  const loadBrcBom = useCallback(async () => {
    setBrcLoading(true);
    setBrcErr(null);
    try {
      const data = await apiGet<{
        grams_per_pouch: number;
        grams_per_bag: number;
        pouches_per_bag: number;
        bag_pool_deduct: boolean;
        default_grams_per_pouch: number;
        default_grams_per_bag: number;
      }>("settings.php", "brc_bom_get");
      setBrcGramsPouch(String(data.grams_per_pouch));
      setBrcGramsBag(String(data.grams_per_bag));
      setBrcPouchesPerBag(data.pouches_per_bag);
      setBrcBagPoolDeduct(data.bag_pool_deduct !== false);
      setBrcDefaultPouch(data.default_grams_per_pouch);
      setBrcDefaultBag(data.default_grams_per_bag);
    } catch (e) {
      setBrcErr(e instanceof ApiError ? e.message : "Could not load Black Rice BOM settings.");
    } finally {
      setBrcLoading(false);
    }
  }, []);

  const loadCacaoBom = useCallback(async () => {
    setCacaoLoading(true);
    setCacaoErr(null);
    try {
      const data = await apiGet<{
        grams_per_jar: number;
        grams_per_bag: number;
        jars_per_bag: number;
        bag_pool_deduct: boolean;
        default_grams_per_jar: number;
        default_grams_per_bag: number;
      }>("settings.php", "jar_cacao_bom_get");
      setCacaoGramsJar(String(data.grams_per_jar));
      setCacaoGramsBag(String(data.grams_per_bag));
      setCacaoJarsPerBag(data.jars_per_bag);
      setCacaoBagPoolDeduct(data.bag_pool_deduct !== false);
      setCacaoDefaultJar(data.default_grams_per_jar);
      setCacaoDefaultBag(data.default_grams_per_bag);
    } catch (e) {
      setCacaoErr(e instanceof ApiError ? e.message : "Could not load Jar Cacao BOM settings.");
    } finally {
      setCacaoLoading(false);
    }
  }, []);

  const loadAdminPassword = useCallback(async () => {
    setAdminPwLoading(true);
    setAdminPwErr(null);
    try {
      const data = await apiGet<{ configured: boolean }>("settings.php", "admin_password_get");
      setAdminPwConfigured(!!data.configured);
    } catch (e) {
      setAdminPwErr(e instanceof ApiError ? e.message : "Could not load administrator password status.");
    } finally {
      setAdminPwLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadPins();
    void loadSkuRows();
    void loadHoneyVolume();
    void loadPtBom();
    void loadBrcBom();
    void loadCacaoBom();
    void loadAdminPassword();
  }, [loadPins, loadSkuRows, loadHoneyVolume, loadPtBom, loadBrcBom, loadCacaoBom, loadAdminPassword]);

  const saveAdminPassword = async () => {
    setAdminPwSaving(true);
    setAdminPwOk(null);
    setAdminPwErr(null);
    try {
      if (adminPwNew.trim().length < 6) {
        throw new Error("New password must be at least 6 characters.");
      }
      if (adminPwNew !== adminPwConfirm) {
        throw new Error("New password and confirmation do not match.");
      }
      await apiPost("settings.php", "admin_password_save", {
        current_password: adminPwConfigured ? adminPwCurrent : undefined,
        new_password: adminPwNew,
        new_password_confirm: adminPwConfirm,
      });
      setAdminPwConfigured(true);
      setAdminPwCurrent("");
      setAdminPwNew("");
      setAdminPwConfirm("");
      setAdminPwOk(
        adminPwConfigured
          ? "Administrator password updated."
          : "Administrator password set. Monthly inventory will require it for stock overrides."
      );
    } catch (e) {
      setAdminPwErr(e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Could not save.");
    } finally {
      setAdminPwSaving(false);
    }
  };

  const saveHoneyVolume = async () => {
    setMlSaving(true);
    setMlOk(null);
    setMlErr(null);
    try {
      const n = Number(mlPerGallon);
      const res = await apiPost<{ ml_per_gallon: number }>("settings.php", "honey_volume_save", {
        ml_per_gallon: n,
      });
      setMlPerGallon(String(res.ml_per_gallon));
      setMlOk(`Saved ml per gallon (${res.ml_per_gallon}).`);
    } catch (e) {
      setMlErr(e instanceof ApiError ? e.message : "Could not save honey volume.");
    } finally {
      setMlSaving(false);
    }
  };

  const savePtBom = async () => {
    setPtSaving(true);
    setPtOk(null);
    setPtErr(null);
    try {
      const res = await apiPost<{
        grams_per_tea_bag: number;
        tea_bags_per_pouch: number;
        grams_per_pouch: number;
        bag_pool_deduct: boolean;
      }>("settings.php", "pt_bom_save", {
        grams_per_tea_bag: Number(ptGramsTea),
        tea_bags_per_pouch: Number(ptBagsPouch),
        bag_pool_deduct: ptBagPoolDeduct ? 1 : 0,
      });
      setPtGramsTea(String(res.grams_per_tea_bag));
      setPtBagsPouch(String(res.tea_bags_per_pouch));
      setPtGramsPouch(res.grams_per_pouch);
      setPtBagPoolDeduct(res.bag_pool_deduct !== false);
      setPtOk(
        `Saved Paragis BOM (${res.grams_per_pouch} g per pouch · deduct ${res.bag_pool_deduct ? "on" : "off"}).`,
      );
    } catch (e) {
      setPtErr(e instanceof ApiError ? e.message : "Could not save Paragis BOM.");
    } finally {
      setPtSaving(false);
    }
  };

  const saveBrcBom = async () => {
    setBrcSaving(true);
    setBrcOk(null);
    setBrcErr(null);
    try {
      const res = await apiPost<{
        grams_per_pouch: number;
        grams_per_bag: number;
        pouches_per_bag: number;
        bag_pool_deduct: boolean;
      }>("settings.php", "brc_bom_save", {
        grams_per_pouch: Number(brcGramsPouch),
        grams_per_bag: Number(brcGramsBag),
        bag_pool_deduct: brcBagPoolDeduct ? 1 : 0,
      });
      setBrcGramsPouch(String(res.grams_per_pouch));
      setBrcGramsBag(String(res.grams_per_bag));
      setBrcPouchesPerBag(res.pouches_per_bag);
      setBrcBagPoolDeduct(res.bag_pool_deduct !== false);
      setBrcOk(
        `Saved Black Rice BOM (${res.grams_per_pouch} g per pouch · deduct ${res.bag_pool_deduct ? "on" : "off"}).`,
      );
    } catch (e) {
      setBrcErr(e instanceof ApiError ? e.message : "Could not save Black Rice BOM.");
    } finally {
      setBrcSaving(false);
    }
  };

  const saveCacaoBom = async () => {
    setCacaoSaving(true);
    setCacaoOk(null);
    setCacaoErr(null);
    try {
      const res = await apiPost<{
        grams_per_jar: number;
        grams_per_bag: number;
        jars_per_bag: number;
        bag_pool_deduct: boolean;
      }>("settings.php", "jar_cacao_bom_save", {
        grams_per_jar: Number(cacaoGramsJar),
        grams_per_bag: Number(cacaoGramsBag),
        bag_pool_deduct: cacaoBagPoolDeduct ? 1 : 0,
      });
      setCacaoGramsJar(String(res.grams_per_jar));
      setCacaoGramsBag(String(res.grams_per_bag));
      setCacaoJarsPerBag(res.jars_per_bag);
      setCacaoBagPoolDeduct(res.bag_pool_deduct !== false);
      setCacaoOk(
        `Saved Jar Cacao BOM (${res.grams_per_jar} g per jar · deduct ${res.bag_pool_deduct ? "on" : "off"}).`,
      );
    } catch (e) {
      setCacaoErr(e instanceof ApiError ? e.message : "Could not save Jar Cacao BOM.");
    } finally {
      setCacaoSaving(false);
    }
  };

  const saveAllSku = async () => {
    setSkuSaving(true);
    setSkuOk(null);
    setSkuErr(null);
    try {
      const items = skuRows.map(row => {
        const key = skuRowKey(row);
        const code = (skuCodes[key] ?? "").trim();
        const priceRaw = (skuPrices[key] ?? "").trim();
        const priceDirty = !pricesEqual(parsePriceInput(priceRaw), row.selling_price_hint);
        const body: {
          kind: "honey" | "dept";
          variant?: string;
          size?: string;
          product_key?: string;
          code: string;
          selling_price?: string;
        } = {
          kind: row.kind,
          ...(row.kind === "honey"
            ? { variant: row.variant ?? undefined, size: row.size ?? undefined }
            : { product_key: row.product_key ?? undefined }),
          code,
        };
        if (priceDirty) {
          body.selling_price = priceRaw;
        }
        return body;
      });
      const res = await skuBarcodesApi.saveBulk(items);
      setSkuOk(`Saved barcode settings (${res.saved_count} row${res.saved_count === 1 ? "" : "s"}).`);
      await loadSkuRows();
    } catch (e) {
      setSkuErr(e instanceof ApiError ? e.message : "Could not save barcode settings.");
    } finally {
      setSkuSaving(false);
    }
  };

  const skuDirty = skuRows.some(row => {
    const key = skuRowKey(row);
    const codeDirty = (skuCodes[key] ?? "").trim() !== (row.code ?? "").trim();
    const priceDirty = !pricesEqual(parsePriceInput(skuPrices[key] ?? ""), row.selling_price_hint);
    return codeDirty || priceDirty;
  });

  const savePin = async (id: number) => {
    const form = pinForms[id];
    if (!form) return;
    setPosBusy(id);
    setPosMsg(null);
    try {
      const res = await apiPost<{ pin_changed?: boolean }>("settings.php", "pos_pin_update", {
        id,
        label: form.label,
        is_active: form.isActive ? 1 : 0,
        pin: form.pin,
        pin_confirm: form.pinConfirm,
      });
      setPosMsg({
        type: "ok",
        text: res.pin_changed ? "POS PIN updated." : "Terminal settings saved.",
      });
      setPinForms(prev => ({
        ...prev,
        [id]: { ...prev[id], pin: "", pinConfirm: "" },
      }));
      await loadPins();
    } catch (e) {
      setPosMsg({ type: "err", text: e instanceof ApiError ? e.message : "Save failed." });
    } finally {
      setPosBusy(null);
    }
  };

  const createPin = async () => {
    setPosBusy("create");
    setPosMsg(null);
    try {
      await apiPost("settings.php", "pos_pin_create", {
        label: createForm.label,
        pin: createForm.pin,
        pin_confirm: createForm.pinConfirm,
      });
      setPosMsg({ type: "ok", text: "POS terminal created." });
      setCreateForm({ label: "POS Terminal", pin: "", pinConfirm: "" });
      setShowCreate(false);
      await loadPins();
    } catch (e) {
      setPosMsg({ type: "err", text: e instanceof ApiError ? e.message : "Could not create terminal." });
    } finally {
      setPosBusy(null);
    }
  };

  return (
    <PageLayout title="System Settings" subtitle="Configure system-wide preferences">
      <div className="max-w-4xl space-y-6">
        {posMsg?.type === "ok" && <AppSuccessNotice message={posMsg.text} />}
        {posMsg?.type === "err" && <AppErrorBanner message={posMsg.text} />}
        {skuOk && <AppSuccessNotice message={skuOk} />}
        {skuErr && <AppErrorBanner message={skuErr} />}
        {mlOk && <AppSuccessNotice message={mlOk} />}
        {mlErr && <AppErrorBanner message={mlErr} />}
        {ptOk && <AppSuccessNotice message={ptOk} />}
        {ptErr && <AppErrorBanner message={ptErr} />}
        {brcOk && <AppSuccessNotice message={brcOk} />}
        {brcErr && <AppErrorBanner message={brcErr} />}
        {cacaoOk && <AppSuccessNotice message={cacaoOk} />}
        {cacaoErr && <AppErrorBanner message={cacaoErr} />}

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <Droplets size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">Honey volume</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Milliliters per 1 production gallon used when bottling deducts gallons. Default is the US gallon (
                {mlDefault} ml). Changing this does not rewrite past deductions.
              </p>
            </div>
          </div>
          {mlLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 flex flex-wrap items-end gap-3">
              <div className="w-48">
                <label className="text-xs text-muted-foreground">ml per gallon</label>
                <input
                  type="number"
                  min={1000}
                  max={100000}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={mlPerGallon}
                  onChange={e => setMlPerGallon(e.target.value)}
                />
              </div>
              <Button
                type="button"
                disabled={mlSaving}
                onClick={() => void saveHoneyVolume()}
                className="bg-[#C8900A] hover:bg-[#b07f09] text-white"
              >
                {mlSaving ? <Loader2 className="animate-spin" size={14} /> : "Save"}
              </Button>
              <Button
                type="button"
                variant="outline"
                disabled={mlSaving}
                onClick={() => setMlPerGallon(String(mlDefault))}
              >
                Reset to default
              </Button>
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <Leaf size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">Paragis pouch BOM</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Leaf grams per tea bag and tea bags per sellable pouch. Scan deducts grams_per_tea_bag × tea_bags_per_pouch
                from the bag pool (1 kg bags). Defaults {ptDefaultGrams} g × {ptDefaultBags} = {ptDefaultGrams * ptDefaultBags} g.
              </p>
            </div>
          </div>
          {ptLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-secondary/20 px-3 py-2.5">
                <div>
                  <p className="text-sm font-medium text-foreground">Deduct raw bags on finished stock-in</p>
                  <p className="text-xs text-muted-foreground">
                    When off, pouch scans credit finished inventory without using the bag pool.
                  </p>
                </div>
                <Switch
                  checked={ptBagPoolDeduct}
                  onCheckedChange={setPtBagPoolDeduct}
                  data-testid="pt-bag-pool-deduct-switch"
                  className="data-[state=checked]:bg-[#C8900A]"
                  aria-label="Deduct raw bags on finished stock-in"
                />
              </div>
              <div className="flex flex-wrap items-end gap-3">
              <div className="w-40">
                <label htmlFor="pt-grams-tea" className="text-xs text-muted-foreground">
                  g per tea bag
                </label>
                <input
                  id="pt-grams-tea"
                  type="number"
                  min={0.1}
                  max={50}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={ptGramsTea}
                  onChange={e => {
                    setPtGramsTea(e.target.value);
                    const g = Number(e.target.value);
                    const n = Number(ptBagsPouch);
                    if (Number.isFinite(g) && Number.isFinite(n)) setPtGramsPouch(g * n);
                  }}
                />
              </div>
              <div className="w-40">
                <label htmlFor="pt-bags-pouch" className="text-xs text-muted-foreground">
                  tea bags per pouch
                </label>
                <input
                  id="pt-bags-pouch"
                  type="number"
                  min={1}
                  max={500}
                  step="1"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={ptBagsPouch}
                  onChange={e => {
                    setPtBagsPouch(e.target.value);
                    const n = Number(e.target.value);
                    const g = Number(ptGramsTea);
                    if (Number.isFinite(g) && Number.isFinite(n)) setPtGramsPouch(g * n);
                  }}
                />
              </div>
              <div className="min-w-[8rem]">
                <p className="text-xs text-muted-foreground">g per pouch</p>
                <p className="mt-1 text-sm font-semibold tabular-nums">{ptGramsPouch}</p>
              </div>
              <Button
                type="button"
                disabled={ptSaving}
                onClick={() => void savePtBom()}
                className="bg-[#C8900A] hover:bg-[#b07f09] text-white"
              >
                {ptSaving ? <Loader2 className="animate-spin" size={14} /> : "Save"}
              </Button>
              <Button
                type="button"
                variant="outline"
                disabled={ptSaving}
                onClick={() => {
                  setPtGramsTea(String(ptDefaultGrams));
                  setPtBagsPouch(String(ptDefaultBags));
                  setPtGramsPouch(ptDefaultGrams * ptDefaultBags);
                  setPtBagPoolDeduct(true);
                }}
              >
                Reset to default
              </Button>
              </div>
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <Leaf size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">Black Rice pouch BOM</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Grams deducted per pouch scan and supplier bag size for the bag pool. Defaults{" "}
                {brcDefaultPouch} g / pouch · {brcDefaultBag} g / bag ({brcDefaultBag / brcDefaultPouch} pouches/bag).
              </p>
            </div>
          </div>
          {brcLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-secondary/20 px-3 py-2.5">
                <div>
                  <p className="text-sm font-medium text-foreground">Deduct raw bags on finished stock-in</p>
                  <p className="text-xs text-muted-foreground">
                    When off, pouch scans credit finished inventory without using the bag pool.
                  </p>
                </div>
                <Switch
                  checked={brcBagPoolDeduct}
                  onCheckedChange={setBrcBagPoolDeduct}
                  data-testid="brc-bag-pool-deduct-switch"
                  className="data-[state=checked]:bg-[#C8900A]"
                  aria-label="Deduct raw bags on finished stock-in"
                />
              </div>
              <div className="flex flex-wrap items-end gap-3">
              <div className="w-40">
                <label htmlFor="brc-grams-pouch" className="text-xs text-muted-foreground">
                  g per pouch
                </label>
                <input
                  id="brc-grams-pouch"
                  type="number"
                  min={1}
                  max={2000}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={brcGramsPouch}
                  onChange={e => {
                    setBrcGramsPouch(e.target.value);
                    const g = Number(e.target.value);
                    const bag = Number(brcGramsBag);
                    if (Number.isFinite(g) && Number.isFinite(bag) && g > 0) {
                      setBrcPouchesPerBag(Math.round((bag / g) * 10000) / 10000);
                    }
                  }}
                />
              </div>
              <div className="w-40">
                <label htmlFor="brc-grams-bag" className="text-xs text-muted-foreground">
                  g per bag
                </label>
                <input
                  id="brc-grams-bag"
                  type="number"
                  min={100}
                  max={50000}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={brcGramsBag}
                  onChange={e => {
                    setBrcGramsBag(e.target.value);
                    const bag = Number(e.target.value);
                    const g = Number(brcGramsPouch);
                    if (Number.isFinite(g) && Number.isFinite(bag) && g > 0) {
                      setBrcPouchesPerBag(Math.round((bag / g) * 10000) / 10000);
                    }
                  }}
                />
              </div>
              <div className="min-w-[8rem]">
                <p className="text-xs text-muted-foreground">pouches per bag</p>
                <p className="mt-1 text-sm font-semibold tabular-nums">{brcPouchesPerBag}</p>
              </div>
              <Button
                type="button"
                disabled={brcSaving}
                onClick={() => void saveBrcBom()}
                className="bg-[#C8900A] hover:bg-[#b07f09] text-white"
              >
                {brcSaving ? <Loader2 className="animate-spin" size={14} /> : "Save"}
              </Button>
              <Button
                type="button"
                variant="outline"
                disabled={brcSaving}
                onClick={() => {
                  setBrcGramsPouch(String(brcDefaultPouch));
                  setBrcGramsBag(String(brcDefaultBag));
                  setBrcPouchesPerBag(brcDefaultBag / brcDefaultPouch);
                  setBrcBagPoolDeduct(true);
                }}
              >
                Reset to default
              </Button>
              </div>
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <Leaf size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">Jar Cacao BOM</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Grams deducted per jar scan and supplier bag size for the bag pool. Defaults{" "}
                {cacaoDefaultJar} g / jar · {cacaoDefaultBag} g / bag (
                {(cacaoDefaultBag / cacaoDefaultJar).toFixed(2)} jars/bag).
              </p>
            </div>
          </div>
          {cacaoLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-secondary/20 px-3 py-2.5">
                <div>
                  <p className="text-sm font-medium text-foreground">Deduct raw bags on finished stock-in</p>
                  <p className="text-xs text-muted-foreground">
                    When off, jar scans credit finished inventory without using the bag pool.
                  </p>
                </div>
                <Switch
                  checked={cacaoBagPoolDeduct}
                  onCheckedChange={setCacaoBagPoolDeduct}
                  data-testid="cacao-bag-pool-deduct-switch"
                  className="data-[state=checked]:bg-[#C8900A]"
                  aria-label="Deduct raw bags on finished stock-in"
                />
              </div>
              <div className="flex flex-wrap items-end gap-3">
              <div className="w-40">
                <label htmlFor="cacao-grams-jar" className="text-xs text-muted-foreground">
                  g per jar
                </label>
                <input
                  id="cacao-grams-jar"
                  type="number"
                  min={1}
                  max={2000}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={cacaoGramsJar}
                  onChange={e => {
                    setCacaoGramsJar(e.target.value);
                    const g = Number(e.target.value);
                    const bag = Number(cacaoGramsBag);
                    if (Number.isFinite(g) && Number.isFinite(bag) && g > 0) {
                      setCacaoJarsPerBag(Math.round((bag / g) * 10000) / 10000);
                    }
                  }}
                />
              </div>
              <div className="w-40">
                <label htmlFor="cacao-grams-bag" className="text-xs text-muted-foreground">
                  g per bag
                </label>
                <input
                  id="cacao-grams-bag"
                  type="number"
                  min={100}
                  max={50000}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                  value={cacaoGramsBag}
                  onChange={e => {
                    setCacaoGramsBag(e.target.value);
                    const bag = Number(e.target.value);
                    const g = Number(cacaoGramsJar);
                    if (Number.isFinite(g) && Number.isFinite(bag) && g > 0) {
                      setCacaoJarsPerBag(Math.round((bag / g) * 10000) / 10000);
                    }
                  }}
                />
              </div>
              <div className="min-w-[8rem]">
                <p className="text-xs text-muted-foreground">jars per bag</p>
                <p className="mt-1 text-sm font-semibold tabular-nums">{cacaoJarsPerBag}</p>
              </div>
              <Button
                type="button"
                disabled={cacaoSaving}
                onClick={() => void saveCacaoBom()}
                className="bg-[#C8900A] hover:bg-[#b07f09] text-white"
              >
                {cacaoSaving ? <Loader2 className="animate-spin" size={14} /> : "Save"}
              </Button>
              <Button
                type="button"
                variant="outline"
                disabled={cacaoSaving}
                onClick={() => {
                  setCacaoGramsJar(String(cacaoDefaultJar));
                  setCacaoGramsBag(String(cacaoDefaultBag));
                  setCacaoJarsPerBag(cacaoDefaultBag / cacaoDefaultJar);
                  setCacaoBagPoolDeduct(true);
                }}
              >
                Reset to default
              </Button>
              </div>
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center justify-between gap-4">
            <div className="flex items-center gap-2 min-w-0">
              <Tag size={18} className="text-[#C8900A] shrink-0" />
              <div className="min-w-0">
                <h3 className="text-sm font-semibold text-foreground">Barcode Settings</h3>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Permanent SKU barcodes for stickers. Selling price defaults from Product Setup; changes here update Product Setup and POS.
                </p>
              </div>
            </div>
            {!skuLoading && skuRows.length > 0 && (
              <Button
                type="button"
                disabled={skuSaving || !skuDirty}
                onClick={() => void saveAllSku()}
                className="shrink-0 bg-[#C8900A] hover:bg-[#b07f09] text-white"
              >
                {skuSaving ? <Loader2 className="animate-spin" size={14} /> : "Save all"}
              </Button>
            )}
          </div>
          {skuLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-secondary/40 text-[10px] uppercase tracking-wide text-muted-foreground">
                  <tr>
                    <th className="text-left px-4 py-2.5 font-semibold">Product</th>
                    <th className="text-left px-4 py-2.5 font-semibold">Selling price</th>
                    <th className="text-left px-4 py-2.5 font-semibold">Code</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {skuRows.map(row => {
                    const key = skuRowKey(row);
                    const codeDirty = (skuCodes[key] ?? "").trim() !== (row.code ?? "").trim();
                    const priceDirty = !pricesEqual(parsePriceInput(skuPrices[key] ?? ""), row.selling_price_hint);
                    const dirty = codeDirty || priceDirty;
                    return (
                      <tr key={key} className={dirty ? "bg-[#C8900A]/5" : undefined}>
                        <td className="px-4 py-3 font-medium text-foreground whitespace-nowrap">{row.label}</td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-1 min-w-[6rem]">
                            <span className="text-xs text-muted-foreground shrink-0">₱</span>
                            <input
                              value={skuPrices[key] ?? ""}
                              onChange={e =>
                                setSkuPrices(prev => ({ ...prev, [key]: e.target.value }))
                              }
                              type="number"
                              step="0.01"
                              min="0"
                              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                              placeholder={row.selling_price_hint == null ? "No product" : "0"}
                            />
                          </div>
                        </td>
                        <td className="px-4 py-3">
                          <input
                            value={skuCodes[key] ?? ""}
                            onChange={e =>
                              setSkuCodes(prev => ({ ...prev, [key]: e.target.value }))
                            }
                            className="w-full min-w-[7rem] rounded-lg border border-border bg-background px-3 py-2 font-mono text-sm outline-none focus:border-[#C8900A]"
                            placeholder="e.g. 250"
                            maxLength={32}
                          />
                        </td>
                      </tr>
                    );
                  })}
                  {skuRows.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-4 py-6 text-center text-muted-foreground">
                        No SKU catalog rows yet. Run the SKU barcodes migration.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
              {skuRows.length > 0 && (
                <div className="px-6 py-4 border-t border-border flex justify-end">
                  <Button
                    type="button"
                    disabled={skuSaving || !skuDirty}
                    onClick={() => void saveAllSku()}
                    className="bg-[#C8900A] hover:bg-[#b07f09] text-white"
                  >
                    {skuSaving ? <Loader2 className="animate-spin" size={14} /> : "Save all"}
                  </Button>
                </div>
              )}
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <Shield size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">Administrator password</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Required on Monthly inventory when overriding absolute stock levels. Separate from login
                passwords — executives set and change it here.
              </p>
            </div>
          </div>
          {adminPwLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 space-y-4">
              <AppSuccessNotice message={adminPwOk} />
              <AppErrorBanner message={adminPwErr} />
              <p className="text-xs text-muted-foreground">
                Status:{" "}
                <span className="font-medium text-foreground">
                  {adminPwConfigured ? "Configured" : "Not set yet"}
                </span>
              </p>
              {adminPwConfigured ? (
                <div>
                  <label htmlFor="admin-pw-current" className="text-xs text-muted-foreground">
                    Current password
                  </label>
                  <input
                    id="admin-pw-current"
                    type="password"
                    autoComplete="current-password"
                    className="mt-1 w-full max-w-md rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                    value={adminPwCurrent}
                    onChange={e => setAdminPwCurrent(e.target.value)}
                  />
                </div>
              ) : null}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-2xl">
                <div>
                  <label htmlFor="admin-pw-new" className="text-xs text-muted-foreground">
                    New password
                  </label>
                  <input
                    id="admin-pw-new"
                    type="password"
                    autoComplete="new-password"
                    className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                    value={adminPwNew}
                    onChange={e => setAdminPwNew(e.target.value)}
                    placeholder="At least 6 characters"
                  />
                </div>
                <div>
                  <label htmlFor="admin-pw-confirm" className="text-xs text-muted-foreground">
                    Confirm new password
                  </label>
                  <input
                    id="admin-pw-confirm"
                    type="password"
                    autoComplete="new-password"
                    className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
                    value={adminPwConfirm}
                    onChange={e => setAdminPwConfirm(e.target.value)}
                  />
                </div>
              </div>
              <Button
                type="button"
                disabled={adminPwSaving || !adminPwNew.trim()}
                onClick={() => void saveAdminPassword()}
                className="bg-[#C8900A] hover:bg-[#b07f09] text-white"
              >
                {adminPwSaving ? <Loader2 className="animate-spin" size={14} /> : adminPwConfigured ? "Update password" : "Set password"}
              </Button>
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <ShoppingCart size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">POS terminal</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Cashiers sign in at the login screen with this PIN (4-12 digits). Leave PIN blank to only update label or active status.
              </p>
            </div>
          </div>

          {pinsLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 space-y-6">
              {pins.map(p => {
                const form = pinForms[p.id];
                if (!form) return null;
                return (
                  <div key={p.id} className="rounded-lg border border-border p-4 space-y-3">
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-xs text-muted-foreground font-mono">Terminal #{p.id}</span>
                      <label className="flex items-center gap-2 text-xs">
                        <input
                          type="checkbox"
                          checked={form.isActive}
                          onChange={e =>
                            setPinForms(prev => ({
                              ...prev,
                              [p.id]: { ...prev[p.id], isActive: e.target.checked },
                            }))
                          }
                        />
                        Active
                      </label>
                    </div>
                    <div>
                      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">
                        Label
                      </label>
                      <input
                        value={form.label}
                        onChange={e =>
                          setPinForms(prev => ({
                            ...prev,
                            [p.id]: { ...prev[p.id], label: e.target.value },
                          }))
                        }
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A]"
                        placeholder="e.g. Main POS"
                      />
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">
                          New PIN
                        </label>
                        <input
                          type="password"
                          inputMode="numeric"
                          autoComplete="new-password"
                          value={form.pin}
                          onChange={e =>
                            setPinForms(prev => ({
                              ...prev,
                              [p.id]: { ...prev[p.id], pin: e.target.value.replace(/\D/g, "") },
                            }))
                          }
                          className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] font-mono"
                          placeholder="••••"
                          maxLength={12}
                        />
                      </div>
                      <div>
                        <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">
                          Confirm PIN
                        </label>
                        <input
                          type="password"
                          inputMode="numeric"
                          autoComplete="new-password"
                          value={form.pinConfirm}
                          onChange={e =>
                            setPinForms(prev => ({
                              ...prev,
                              [p.id]: { ...prev[p.id], pinConfirm: e.target.value.replace(/\D/g, "") },
                            }))
                          }
                          className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] font-mono"
                          placeholder="••••"
                          maxLength={12}
                        />
                      </div>
                    </div>
                    <button
                      type="button"
                      disabled={posBusy === p.id}
                      onClick={() => void savePin(p.id)}
                      className="w-full sm:w-auto px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-50"
                      style={{ background: "#C8900A" }}
                    >
                      {posBusy === p.id ? <Loader2 className="inline animate-spin" size={14} /> : "Save POS settings"}
                    </button>
                  </div>
                );
              })}

              {pins.length > 0 && !showCreate && (
                <button
                  type="button"
                  onClick={() => setShowCreate(true)}
                  className="text-sm font-medium text-[#C8900A] hover:underline"
                >
                  + Add another terminal
                </button>
              )}

              {showCreate && (
                <div className="rounded-lg border border-dashed border-border p-4 space-y-3">
                  <p className="text-sm font-semibold">{pins.length === 0 ? "Create POS terminal" : "New terminal"}</p>
                  <div>
                    <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Label</label>
                    <input
                      value={createForm.label}
                      onChange={e => setCreateForm(f => ({ ...f, label: e.target.value }))}
                      className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A]"
                    />
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">PIN</label>
                      <input
                        type="password"
                        inputMode="numeric"
                        value={createForm.pin}
                        onChange={e => setCreateForm(f => ({ ...f, pin: e.target.value.replace(/\D/g, "") }))}
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm font-mono"
                        maxLength={12}
                      />
                    </div>
                    <div>
                      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Confirm PIN</label>
                      <input
                        type="password"
                        inputMode="numeric"
                        value={createForm.pinConfirm}
                        onChange={e => setCreateForm(f => ({ ...f, pinConfirm: e.target.value.replace(/\D/g, "") }))}
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm font-mono"
                        maxLength={12}
                      />
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      disabled={posBusy === "create"}
                      onClick={() => void createPin()}
                      className="px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-50"
                      style={{ background: "#C8900A" }}
                    >
                      {posBusy === "create" ? <Loader2 className="inline animate-spin" size={14} /> : "Create terminal"}
                    </button>
                    {pins.length > 0 && (
                      <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2.5 rounded-lg border text-sm">
                        Cancel
                      </button>
                    )}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">General</h3>
            <p className="text-xs text-muted-foreground mt-0.5">Coming soon — not saved yet.</p>
          </div>
          <div className="px-6 py-5 space-y-5 opacity-60 pointer-events-none">
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">System Name</label>
              <input value={systemName} onChange={e => setSystemName(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm" />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Timezone</label>
              <select value={timezone} onChange={e => setTimezone(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm">
                <option value="Asia/Manila">Asia/Manila (GMT+8)</option>
                <option value="UTC">UTC</option>
              </select>
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Default Export Format</label>
              <select value={exportFormat} onChange={e => setExportFormat(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm">
                <option value="xlsx">Excel (.xlsx)</option>
                <option value="csv">CSV (.csv)</option>
              </select>
            </div>
          </div>
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden opacity-60" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Departments</h3>
          </div>
          <div className="divide-y divide-border">
            {["Honey", "Paragis", "Cacao / BRC", "CSR"].map(dept => (
              <div key={dept} className="flex items-center justify-between px-6 py-4">
                <span className="text-sm text-foreground font-medium">{dept}</span>
                <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">Active</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
