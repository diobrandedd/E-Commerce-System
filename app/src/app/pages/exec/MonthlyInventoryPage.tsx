import { createContext, useCallback, useContext, useEffect, useRef, useState } from "react";
import { ClipboardList, Loader2, Plus, RefreshCw } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppPressable,
  AppSectionEnter,
  AppSuccessNotice,
  appHeaderActionClass,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { AdminOverridePasswordModal } from "@/app/components/inventory/AdminOverridePasswordModal";
import { Button } from "@/app/components/ui/button";
import { Input } from "@/app/components/ui/input";
import { SupplierCombobox } from "@/app/components/suppliers/SupplierCombobox";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { listMaterials, setPackagingOnHand, stockInPackagingLot } from "@/app/lib/materials";
import { HONEY_SIZES, HONEY_VARIANTS } from "@/app/lib/honey";
import type { Supplier } from "@/app/lib/suppliers";
import { cn } from "@/app/components/ui/utils";

type WithAdminPassword = (run: (adminPassword: string) => Promise<void>) => void;

const MonthlyAdminPwContext = createContext<WithAdminPassword | null>(null);

function useMonthlyAdminPw(): WithAdminPassword {
  const ctx = useContext(MonthlyAdminPwContext);
  if (!ctx) {
    throw new Error("Monthly inventory admin password context missing.");
  }
  return ctx;
}

type DomainTab =
  | "warehouse"
  | "production"
  | "packaging"
  | "materials"
  | "raw"
  | "dept"
  | "rts"
  | "logs";

type HoneyRow = {
  id: number;
  supplier_code?: string;
  variant?: string;
  current_qty?: number;
  initial_qty?: number;
  batch_no?: string;
  current_liter?: number;
  current_250ml?: number;
  current_350ml?: number;
  current_gallon?: number;
};

type EditLogRow = {
  id: number;
  row_table: string;
  row_id: number;
  field_name: string;
  old_value: string | null;
  new_value: string | null;
  edited_by_name: string;
  source: string | null;
  reason: string | null;
  created_at: string;
};

type MaterialRow = { id: number; name: string; on_hand: number; unit: string };
type RawSnap = { product_key: string; title: string; bags_on_hand?: number; unit?: string };
type DeptItem = {
  dept_row_id: number | null;
  key: string;
  label: string;
  current_qty: number;
  unit: string;
};
type RtsHoney = {
  variant: string;
  current_liter: number;
  current_250ml: number;
  current_350ml: number;
  current_gallon: number;
};
type RtsOther = { product_label: string; current_qty: number };

const DOMAIN_TABS: { id: DomainTab; label: string }[] = [
  { id: "warehouse", label: "Honey warehouse" },
  { id: "production", label: "Honey production" },
  { id: "packaging", label: "Honey packaging" },
  { id: "materials", label: "Packaging materials" },
  { id: "raw", label: "Raw bags" },
  { id: "dept", label: "Dept finished" },
  { id: "rts", label: "RTS" },
  { id: "logs", label: "Edit Logs" },
];

const RAW_SLUGS = [
  { slug: "paragis", key: "pt" },
  { slug: "black-rice", key: "brc" },
  { slug: "cacao", key: "jar_cacao" },
  { slug: "honey-comb", key: "honey_comb" },
] as const;

export function MonthlyInventoryPage() {
  const [tab, setTab] = useState<DomainTab>("warehouse");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const [whRows, setWhRows] = useState<HoneyRow[]>([]);
  const [prodRows, setProdRows] = useState<HoneyRow[]>([]);
  const [packRows, setPackRows] = useState<HoneyRow[]>([]);
  const [materials, setMaterials] = useState<MaterialRow[]>([]);
  const [rawItems, setRawItems] = useState<RawSnap[]>([]);
  const [deptItems, setDeptItems] = useState<DeptItem[]>([]);
  const [rtsHoney, setRtsHoney] = useState<RtsHoney[]>([]);
  const [rtsOther, setRtsOther] = useState<RtsOther[]>([]);
  const [logs, setLogs] = useState<EditLogRow[]>([]);
  const [logsTotal, setLogsTotal] = useState(0);
  const [logSource, setLogSource] = useState("");
  const [logQ, setLogQ] = useState("");

  const [pwOpen, setPwOpen] = useState(false);
  const [pwBusy, setPwBusy] = useState(false);
  const [pwError, setPwError] = useState<string | null>(null);
  const pendingPwRun = useRef<((password: string) => Promise<void>) | null>(null);

  const withAdminPassword = useCallback<WithAdminPassword>((run) => {
    pendingPwRun.current = run;
    setPwError(null);
    setPwOpen(true);
  }, []);

  const confirmAdminPassword = async (password: string) => {
    const run = pendingPwRun.current;
    if (!run) return;
    setPwBusy(true);
    setPwError(null);
    try {
      await run(password);
      pendingPwRun.current = null;
      setPwOpen(false);
    } catch (e) {
      setPwError(e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Failed.");
    } finally {
      setPwBusy(false);
    }
  };

  const flash = (msg: string) => {
    setSuccess(msg);
    setError(null);
    window.setTimeout(() => setSuccess(null), 3500);
  };

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      if (tab === "warehouse") {
        const d = await apiGet<{ rows: HoneyRow[] }>("honey.php", "warehouse_list");
        setWhRows(d.rows ?? []);
      } else if (tab === "production") {
        const d = await apiGet<{ rows: HoneyRow[] }>("honey.php", "production_list");
        setProdRows(d.rows ?? []);
      } else if (tab === "packaging") {
        const d = await apiGet<{ rows: HoneyRow[] }>("honey.php", "packaging_list");
        setPackRows(d.rows ?? []);
      } else if (tab === "materials") {
        const d = await listMaterials("packaging");
        setMaterials(
          (d.items ?? []).map((s) => ({
            id: s.id,
            name: s.name,
            on_hand: Number(s.on_hand),
            unit: String(s.unit ?? ""),
          }))
        );
      } else if (tab === "raw") {
        const snaps: RawSnap[] = [];
        for (const r of RAW_SLUGS) {
          const d = await apiGet<RawSnap>("honey.php", "dept_raw_detail", { slug: r.slug });
          snaps.push({
            product_key: d.product_key ?? r.key,
            title: d.title ?? r.slug,
            bags_on_hand: (d as { bags_on_hand?: number }).bags_on_hand,
            unit: d.unit,
          });
        }
        setRawItems(snaps);
      } else if (tab === "dept") {
        const d = await apiGet<{ products: DeptItem[] }>("honey.php", "dept_catalog");
        setDeptItems(d.products ?? []);
      } else if (tab === "rts") {
        const d = await apiGet<{ honey: RtsHoney[]; other: RtsOther[] }>("rts.php", "inventory_list", {
          pool: "sellable",
        });
        setRtsHoney(d.honey ?? []);
        setRtsOther(d.other ?? []);
      } else if (tab === "logs") {
        const d = await apiGet<{ rows: EditLogRow[]; total: number }>("honey.php", "edit_log_list", {
          limit: 50,
          offset: 0,
          ...(logSource ? { source: logSource } : {}),
          ...(logQ.trim() ? { q: logQ.trim() } : {}),
        });
        setLogs(d.rows ?? []);
        setLogsTotal(d.total ?? 0);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Failed to load.");
    } finally {
      setLoading(false);
    }
  }, [tab, logSource, logQ]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <MonthlyAdminPwContext.Provider value={withAdminPassword}>
    <PageLayout
      title="Monthly inventory"
      subtitle="Executive physical count — overrides absolute stock (requires administrator password from System Settings). Changes go to Edit Logs."
      actions={
        <AppPressable type="button" onClick={() => void load()} className={appHeaderActionClass}>
          <RefreshCw className="size-3.5" />
          Refresh
        </AppPressable>
      }
    >
      <AppSectionEnter>
        <div className="flex flex-wrap gap-2 mb-4">
          {DOMAIN_TABS.map((t) => (
            <AppPressable
              key={t.id}
              type="button"
              onClick={() => setTab(t.id)}
              className={cn(
                "text-xs font-medium rounded-lg border px-3 py-1.5",
                tab === t.id
                  ? "bg-[#C8900A]/15 border-[#C8900A]/50 text-foreground"
                  : "border-border text-muted-foreground hover:bg-secondary/50"
              )}
            >
              {t.id === "logs" ? <ClipboardList className="size-3.5 inline mr-1" /> : null}
              {t.label}
            </AppPressable>
          ))}
        </div>

        <AppSuccessNotice message={success} />
        <AppErrorBanner message={error} />

        {loading ? (
          <div className="flex items-center gap-2 text-sm text-muted-foreground py-8">
            <Loader2 className="size-4 animate-spin" /> Loading…
          </div>
        ) : null}

        {!loading && tab === "warehouse" ? (
          <HoneyGallonSection
            title="Warehouse lots"
            rows={whRows}
            stage="warehouse"
            rowTable="honey_warehouse_rows"
            onSaved={flash}
            onReload={load}
            onError={setError}
            allowAddWarehouse
          />
        ) : null}

        {!loading && tab === "production" ? (
          <HoneyGallonSection
            title="Production lots"
            rows={prodRows}
            stage="production"
            rowTable="honey_production_rows"
            onSaved={flash}
            onReload={load}
            onError={setError}
            allowSeedProduction
            warehouseOptions={whRows}
            ensureWarehouse={() => apiGet<{ rows: HoneyRow[] }>("honey.php", "warehouse_list").then((d) => d.rows ?? [])}
          />
        ) : null}

        {!loading && tab === "packaging" ? (
          <HoneyPackagingSection
            rows={packRows}
            productionOptions={prodRows}
            onSaved={flash}
            onReload={load}
            onError={setError}
            ensureProduction={() =>
              apiGet<{ rows: HoneyRow[] }>("honey.php", "production_list").then((d) => d.rows ?? [])
            }
          />
        ) : null}

        {!loading && tab === "materials" ? (
          <MaterialsSection rows={materials} onSaved={flash} onReload={load} onError={setError} />
        ) : null}

        {!loading && tab === "raw" ? (
          <RawBagsSection items={rawItems} onSaved={flash} onReload={load} onError={setError} />
        ) : null}

        {!loading && tab === "dept" ? (
          <DeptFinishedSection items={deptItems} onSaved={flash} onReload={load} onError={setError} />
        ) : null}

        {!loading && tab === "rts" ? (
          <RtsSection honey={rtsHoney} other={rtsOther} onSaved={flash} onReload={load} onError={setError} />
        ) : null}

        {!loading && tab === "logs" ? (
          <div className="space-y-3">
            <div className="flex flex-wrap gap-2 items-end">
              <label className="text-xs space-y-1">
                <span className="text-muted-foreground">Source</span>
                <select
                  className="block border rounded-lg px-2 py-1.5 text-sm bg-background"
                  value={logSource}
                  onChange={(e) => setLogSource(e.target.value)}
                >
                  <option value="">All</option>
                  <option value="monthly_inventory">monthly_inventory</option>
                  <option value="executive_edit">executive_edit</option>
                  <option value="set_on_hand">set_on_hand</option>
                </select>
              </label>
              <label className="text-xs space-y-1 flex-1 min-w-[10rem]">
                <span className="text-muted-foreground">Search</span>
                <Input value={logQ} onChange={(e) => setLogQ(e.target.value)} placeholder="field, qty, user…" />
              </label>
              <Button type="button" variant="outline" size="sm" onClick={() => void load()}>
                Apply
              </Button>
            </div>
            <p className="text-xs text-muted-foreground">{logsTotal} log row(s)</p>
            <div className="rounded-xl border overflow-hidden">
              <table className="w-full text-sm">
                <thead className="bg-secondary/40 text-xs text-muted-foreground">
                  <tr>
                    <th className="text-left px-3 py-2">When</th>
                    <th className="text-left px-3 py-2">Who</th>
                    <th className="text-left px-3 py-2">Table</th>
                    <th className="text-left px-3 py-2">Field</th>
                    <th className="text-left px-3 py-2">Change</th>
                    <th className="text-left px-3 py-2">Reason</th>
                    <th className="text-left px-3 py-2">Source</th>
                  </tr>
                </thead>
                <tbody>
                  {logs.map((r) => (
                    <tr key={r.id} className={cn("border-t", appHoverRow)}>
                      <td className="px-3 py-2 whitespace-nowrap text-xs">{r.created_at}</td>
                      <td className="px-3 py-2">{r.edited_by_name || r.id}</td>
                      <td className="px-3 py-2 font-mono text-xs">
                        {r.row_table}#{r.row_id}
                      </td>
                      <td className="px-3 py-2">{r.field_name}</td>
                      <td className="px-3 py-2">
                        <span className="text-muted-foreground">{r.old_value ?? "—"}</span>
                        {" → "}
                        <span className="font-medium">{r.new_value ?? "—"}</span>
                      </td>
                      <td className="px-3 py-2 text-xs max-w-[14rem]">
                        {r.reason ? <span className="line-clamp-3">{r.reason}</span> : "—"}
                      </td>
                      <td className="px-3 py-2 text-xs">{r.source ?? "—"}</td>
                    </tr>
                  ))}
                  {logs.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="px-3 py-8 text-center text-muted-foreground text-sm">
                        No edit logs yet.
                      </td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          </div>
        ) : null}
      </AppSectionEnter>
    </PageLayout>
    <AdminOverridePasswordModal
      open={pwOpen}
      busy={pwBusy}
      error={pwError}
      onClose={() => {
        if (pwBusy) return;
        pendingPwRun.current = null;
        setPwOpen(false);
        setPwError(null);
      }}
      onConfirm={confirmAdminPassword}
    />
    </MonthlyAdminPwContext.Provider>
  );
}

function HoneyGallonSection({
  title,
  rows,
  stage,
  rowTable,
  onSaved,
  onReload,
  onError,
  allowAddWarehouse,
  allowSeedProduction,
  warehouseOptions,
  ensureWarehouse,
}: {
  title: string;
  rows: HoneyRow[];
  stage: string;
  rowTable: string;
  onSaved: (m: string) => void;
  onReload: () => Promise<void>;
  onError: (m: string) => void;
  allowAddWarehouse?: boolean;
  allowSeedProduction?: boolean;
  warehouseOptions?: HoneyRow[];
  ensureWarehouse?: () => Promise<HoneyRow[]>;
}) {
  const [setRow, setSetRow] = useState<HoneyRow | null>(null);
  const [qty, setQty] = useState("");
  const [busy, setBusy] = useState(false);
  const [addOpen, setAddOpen] = useState(false);
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [arrival, setArrival] = useState(new Date().toISOString().slice(0, 10));
  const [variant, setVariant] = useState("light");
  const [addQty, setAddQty] = useState("");
  const [whId, setWhId] = useState("");
  const [whList, setWhList] = useState<HoneyRow[]>(warehouseOptions ?? []);
  const withAdminPw = useMonthlyAdminPw();

  const saveSet = () => {
    if (!setRow) return;
    withAdminPw(async (admin_password) => {
      setBusy(true);
      try {
        await apiPost("honey.php", "executive_edit", {
          stage,
          row_table: rowTable,
          row_id: setRow.id,
          fields: {
            current_qty: Number(qty),
            qty_mode: "set_exact",
            log_source: "monthly_inventory",
          },
          admin_password,
        });
        setSetRow(null);
        onSaved("Quantity updated.");
        await onReload();
      } finally {
        setBusy(false);
      }
    });
  };

  const saveAdd = () => {
    withAdminPw(async (admin_password) => {
      setBusy(true);
      try {
        if (allowAddWarehouse) {
          if (!supplier) throw new Error("Pick a supplier.");
          await apiPost("honey.php", "warehouse_stock_in", {
            supplier_id: supplier.id,
            arrival_date: arrival,
            variants: [{ variant, qty: Number(addQty) }],
            monthly_inventory: true,
            log_source: "monthly_inventory",
            admin_password,
          });
          onSaved("Warehouse lot added.");
        } else if (allowSeedProduction) {
          await apiPost("honey.php", "seed_production_row", {
            warehouse_row_id: Number(whId),
            qty: Number(addQty),
            variant: variant || undefined,
            admin_password,
          });
          onSaved("Production lot seeded.");
        }
        setAddOpen(false);
        await onReload();
      } finally {
        setBusy(false);
      }
    });
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between gap-2">
        <h2 className="text-sm font-semibold">{title}</h2>
        {(allowAddWarehouse || allowSeedProduction) && (
          <Button
            type="button"
            size="sm"
            onClick={async () => {
              if (ensureWarehouse) setWhList(await ensureWarehouse());
              setAddOpen(true);
            }}
          >
            <Plus className="size-3.5 mr-1" /> Add
          </Button>
        )}
      </div>
      <LotTable
        rows={rows}
        label={(r) => `${r.supplier_code ?? ""} · ${r.variant ?? ""}`}
        qty={(r) => Number(r.current_qty ?? 0)}
        onSet={(r) => {
          setSetRow(r);
          setQty(String(r.current_qty ?? 0));
        }}
      />

      <AppModal open={!!setRow} onClose={() => setSetRow(null)} labelledBy="set-qty-title">
        <AppModalHeader titleId="set-qty-title" title="Set counted qty" onClose={() => setSetRow(null)} />
        <AppModalBody>
          <p className="text-sm text-muted-foreground mb-2">
            {setRow?.supplier_code} · {setRow?.variant}
          </p>
          <Input type="number" min={0} step="0.01" value={qty} onChange={(e) => setQty(e.target.value)} />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetRow(null)}>
            Cancel
          </Button>
          <Button type="button" disabled={busy} onClick={() => void saveSet()}>
            Save
          </Button>
        </AppModalFooter>
      </AppModal>

      <AppModal open={addOpen} onClose={() => setAddOpen(false)} labelledBy="add-lot-title">
        <AppModalHeader titleId="add-lot-title" title="Add lot" onClose={() => setAddOpen(false)} />
        <AppModalBody className="space-y-3">
          {allowAddWarehouse ? (
            <>
              <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
              <Input type="date" value={arrival} onChange={(e) => setArrival(e.target.value)} />
              <select
                className="w-full border rounded-lg px-2 py-2 text-sm bg-background"
                value={variant}
                onChange={(e) => setVariant(e.target.value)}
              >
                {HONEY_VARIANTS.map((v) => (
                  <option key={v} value={v}>
                    {v}
                  </option>
                ))}
              </select>
            </>
          ) : (
            <>
              <select
                className="w-full border rounded-lg px-2 py-2 text-sm bg-background"
                value={whId}
                onChange={(e) => setWhId(e.target.value)}
              >
                <option value="">Warehouse lot…</option>
                {(whList.length ? whList : warehouseOptions ?? []).map((w) => (
                  <option key={w.id} value={w.id}>
                    #{w.id} {w.supplier_code} · {w.variant}
                  </option>
                ))}
              </select>
              <select
                className="w-full border rounded-lg px-2 py-2 text-sm bg-background"
                value={variant}
                onChange={(e) => setVariant(e.target.value)}
              >
                <option value="">Keep warehouse variant</option>
                {HONEY_VARIANTS.map((v) => (
                  <option key={v} value={v}>
                    {v}
                  </option>
                ))}
              </select>
            </>
          )}
          <Input
            type="number"
            min={0}
            step="0.01"
            placeholder="Gallons"
            value={addQty}
            onChange={(e) => setAddQty(e.target.value)}
          />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setAddOpen(false)}>
            Cancel
          </Button>
          <Button type="button" disabled={busy} onClick={() => void saveAdd()}>
            Add
          </Button>
        </AppModalFooter>
      </AppModal>
    </div>
  );
}

function HoneyPackagingSection({
  rows,
  onSaved,
  onReload,
  onError,
  ensureProduction,
}: {
  rows: HoneyRow[];
  productionOptions: HoneyRow[];
  onSaved: (m: string) => void;
  onReload: () => Promise<void>;
  onError: (m: string) => void;
  ensureProduction: () => Promise<HoneyRow[]>;
}) {
  const [setRow, setSetRow] = useState<HoneyRow | null>(null);
  const [sizes, setSizes] = useState<Record<string, string>>({});
  const [addOpen, setAddOpen] = useState(false);
  const [prodList, setProdList] = useState<HoneyRow[]>([]);
  const [prodId, setProdId] = useState("");
  const [bottling, setBottling] = useState(new Date().toISOString().slice(0, 10));
  const [seedSizes, setSeedSizes] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);
  const withAdminPw = useMonthlyAdminPw();

  const saveSet = () => {
    if (!setRow) return;
    withAdminPw(async (admin_password) => {
      setBusy(true);
      try {
        const size_qtys: Record<string, number> = {};
        for (const s of HONEY_SIZES) size_qtys[s] = Number(sizes[s] ?? 0);
        await apiPost("honey.php", "executive_edit", {
          stage: "packaging",
          row_table: "honey_packaging_rows",
          row_id: setRow.id,
          fields: { size_qtys, qty_mode: "set_exact", log_source: "monthly_inventory" },
          admin_password,
        });
        setSetRow(null);
        onSaved("Packaging sizes updated.");
        await onReload();
      } finally {
        setBusy(false);
      }
    });
  };

  const saveSeed = () => {
    withAdminPw(async (admin_password) => {
      setBusy(true);
      try {
        const size_qtys: Record<string, number> = {};
        for (const s of HONEY_SIZES) size_qtys[s] = Number(seedSizes[s] ?? 0);
        await apiPost("honey.php", "seed_packaging_row", {
          production_row_id: Number(prodId),
          bottling_date: bottling,
          size_qtys,
          admin_password,
        });
        setAddOpen(false);
        onSaved("Packaging batch seeded.");
        await onReload();
      } finally {
        setBusy(false);
      }
    });
  };

  return (
    <div className="space-y-3">
      <div className="flex justify-between items-center">
        <h2 className="text-sm font-semibold">Bottled packaging batches</h2>
        <Button
          type="button"
          size="sm"
          onClick={async () => {
            setProdList(await ensureProduction());
            setAddOpen(true);
          }}
        >
          <Plus className="size-3.5 mr-1" /> Seed batch
        </Button>
      </div>
      <div className="rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-secondary/40 text-xs text-muted-foreground">
            <tr>
              <th className="text-left px-3 py-2">Batch</th>
              <th className="text-left px-3 py-2">1L</th>
              <th className="text-left px-3 py-2">250</th>
              <th className="text-left px-3 py-2">350</th>
              <th className="text-left px-3 py-2">Gal</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id} className={cn("border-t", appHoverRow)}>
                <td className="px-3 py-2 font-mono text-xs">
                  {r.batch_no} · {r.variant}
                </td>
                <td className="px-3 py-2">{r.current_liter}</td>
                <td className="px-3 py-2">{r.current_250ml}</td>
                <td className="px-3 py-2">{r.current_350ml}</td>
                <td className="px-3 py-2">{r.current_gallon}</td>
                <td className="px-3 py-2 text-right">
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => {
                      setSetRow(r);
                      setSizes({
                        liter: String(r.current_liter ?? 0),
                        "250ml": String(r.current_250ml ?? 0),
                        "350ml": String(r.current_350ml ?? 0),
                        gallon: String(r.current_gallon ?? 0),
                      });
                    }}
                  >
                    Set
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <AppModal open={!!setRow} onClose={() => setSetRow(null)} labelledBy="pack-set">
        <AppModalHeader titleId="pack-set" title="Set packaging sizes" onClose={() => setSetRow(null)} />
        <AppModalBody className="grid grid-cols-2 gap-2">
          {HONEY_SIZES.map((s) => (
            <label key={s} className="text-xs space-y-1">
              <span>{s}</span>
              <Input value={sizes[s] ?? "0"} onChange={(e) => setSizes((p) => ({ ...p, [s]: e.target.value }))} />
            </label>
          ))}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetRow(null)}>
            Cancel
          </Button>
          <Button type="button" disabled={busy} onClick={() => void saveSet()}>
            Save
          </Button>
        </AppModalFooter>
      </AppModal>

      <AppModal open={addOpen} onClose={() => setAddOpen(false)} labelledBy="pack-seed">
        <AppModalHeader titleId="pack-seed" title="Seed packaging batch" onClose={() => setAddOpen(false)} />
        <AppModalBody className="space-y-3">
          <select
            className="w-full border rounded-lg px-2 py-2 text-sm bg-background"
            value={prodId}
            onChange={(e) => setProdId(e.target.value)}
          >
            <option value="">Production row…</option>
            {prodList.map((p) => (
              <option key={p.id} value={p.id}>
                #{p.id} {p.supplier_code} · {p.variant}
              </option>
            ))}
          </select>
          <Input type="date" value={bottling} onChange={(e) => setBottling(e.target.value)} />
          <div className="grid grid-cols-2 gap-2">
            {HONEY_SIZES.map((s) => (
              <label key={s} className="text-xs space-y-1">
                <span>{s}</span>
                <Input
                  value={seedSizes[s] ?? ""}
                  onChange={(e) => setSeedSizes((p) => ({ ...p, [s]: e.target.value }))}
                />
              </label>
            ))}
          </div>
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setAddOpen(false)}>
            Cancel
          </Button>
          <Button type="button" disabled={busy} onClick={() => void saveSeed()}>
            Seed
          </Button>
        </AppModalFooter>
      </AppModal>
    </div>
  );
}

function MaterialsSection({
  rows,
  onSaved,
  onReload,
  onError,
}: {
  rows: MaterialRow[];
  onSaved: (m: string) => void;
  onReload: () => Promise<void>;
  onError: (m: string) => void;
}) {
  const [setRow, setSetRow] = useState<MaterialRow | null>(null);
  const [onHand, setOnHand] = useState("");
  const [addOpen, setAddOpen] = useState(false);
  const [addStockId, setAddStockId] = useState("");
  const [lotQty, setLotQty] = useState("");
  const [lotCost, setLotCost] = useState("");
  const [busy, setBusy] = useState(false);
  const withAdminPw = useMonthlyAdminPw();

  return (
    <div className="space-y-3">
      <div className="flex justify-between">
        <h2 className="text-sm font-semibold">Packaging materials</h2>
        <Button type="button" size="sm" onClick={() => setAddOpen(true)}>
          <Plus className="size-3.5 mr-1" /> Stock-in lot
        </Button>
      </div>
      <LotTable
        rows={rows}
        label={(r) => r.name}
        qty={(r) => Number(r.on_hand)}
        onSet={(r) => {
          setSetRow(r);
          setOnHand(String(r.on_hand));
        }}
      />
      <AppModal open={!!setRow} onClose={() => setSetRow(null)} labelledBy="mat-set">
        <AppModalHeader titleId="mat-set" title="Set on-hand" onClose={() => setSetRow(null)} />
        <AppModalBody>
          <Input type="number" min={0} value={onHand} onChange={(e) => setOnHand(e.target.value)} />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetRow(null)}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={busy}
            onClick={() => {
              if (!setRow) return;
              withAdminPw(async (admin_password) => {
                setBusy(true);
                try {
                  await setPackagingOnHand({
                    stock_id: setRow.id,
                    on_hand: Number(onHand),
                    note: "Monthly inventory",
                    monthly_inventory: true,
                    admin_password,
                  });
                  setSetRow(null);
                  onSaved("On-hand updated.");
                  await onReload();
                } finally {
                  setBusy(false);
                }
              });
            }}
          >
            Save
          </Button>
        </AppModalFooter>
      </AppModal>
      <AppModal open={addOpen} onClose={() => setAddOpen(false)} labelledBy="mat-add">
        <AppModalHeader titleId="mat-add" title="Stock-in lot" onClose={() => setAddOpen(false)} />
        <AppModalBody className="space-y-2">
          <select
            className="w-full border rounded-lg px-2 py-2 text-sm bg-background"
            value={addStockId}
            onChange={(e) => setAddStockId(e.target.value)}
          >
            <option value="">SKU…</option>
            {rows.map((r) => (
              <option key={r.id} value={r.id}>
                {r.name}
              </option>
            ))}
          </select>
          <Input placeholder="Qty" value={lotQty} onChange={(e) => setLotQty(e.target.value)} />
          <Input placeholder="Total ₱ (optional)" value={lotCost} onChange={(e) => setLotCost(e.target.value)} />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setAddOpen(false)}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={busy}
            onClick={() => {
              withAdminPw(async (admin_password) => {
                setBusy(true);
                try {
                  await stockInPackagingLot({
                    stock_id: Number(addStockId),
                    lot_qty: Number(lotQty),
                    lot_total_price: Number(lotCost || 0),
                    note: "Monthly inventory",
                    monthly_inventory: true,
                    admin_password,
                  });
                  setAddOpen(false);
                  onSaved("Lot stocked in.");
                  await onReload();
                } finally {
                  setBusy(false);
                }
              });
            }}
          >
            Add
          </Button>
        </AppModalFooter>
      </AppModal>
    </div>
  );
}

function RawBagsSection({
  items,
  onSaved,
  onReload,
  onError,
}: {
  items: RawSnap[];
  onSaved: (m: string) => void;
  onReload: () => Promise<void>;
  onError: (m: string) => void;
}) {
  const [setItem, setSetItem] = useState<RawSnap | null>(null);
  const [bags, setBags] = useState("");
  const [busy, setBusy] = useState(false);
  const withAdminPw = useMonthlyAdminPw();

  return (
    <div className="space-y-3">
      <h2 className="text-sm font-semibold">Raw bags (Paragis / BRC / Cacao / Comb)</h2>
      <LotTable
        rows={items}
        label={(r) => r.title}
        qty={(r) => Number(r.bags_on_hand ?? 0)}
        onSet={(r) => {
          setSetItem(r);
          setBags(String(r.bags_on_hand ?? 0));
        }}
      />
      <AppModal open={!!setItem} onClose={() => setSetItem(null)} labelledBy="raw-set">
        <AppModalHeader titleId="raw-set" title="Set bags on-hand" onClose={() => setSetItem(null)} />
        <AppModalBody>
          <Input type="number" min={0} value={bags} onChange={(e) => setBags(e.target.value)} />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetItem(null)}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={busy}
            onClick={() => {
              if (!setItem) return;
              withAdminPw(async (admin_password) => {
                setBusy(true);
                try {
                  await apiPost("honey.php", "dept_raw_set_bags", {
                    product_key: setItem.product_key,
                    bags: Number(bags),
                    note: "Monthly inventory",
                    admin_password,
                  });
                  setSetItem(null);
                  onSaved("Raw bags updated.");
                  await onReload();
                } finally {
                  setBusy(false);
                }
              });
            }}
          >
            Save
          </Button>
        </AppModalFooter>
      </AppModal>
    </div>
  );
}

function DeptFinishedSection({
  items,
  onSaved,
  onReload,
  onError,
}: {
  items: DeptItem[];
  onSaved: (m: string) => void;
  onReload: () => Promise<void>;
  onError: (m: string) => void;
}) {
  const [setItem, setSetItem] = useState<DeptItem | null>(null);
  const [qty, setQty] = useState("");
  const [busy, setBusy] = useState(false);
  const withAdminPw = useMonthlyAdminPw();
  const withRows = items.filter((i) => i.dept_row_id);

  return (
    <div className="space-y-3">
      <h2 className="text-sm font-semibold">Department finished stock</h2>
      <LotTable
        rows={withRows}
        label={(r) => `${r.label} (${r.unit})`}
        qty={(r) => Number(r.current_qty)}
        onSet={(r) => {
          setSetItem(r);
          setQty(String(r.current_qty));
        }}
      />
      <AppModal open={!!setItem} onClose={() => setSetItem(null)} labelledBy="dept-set">
        <AppModalHeader titleId="dept-set" title="Set finished qty" onClose={() => setSetItem(null)} />
        <AppModalBody>
          <Input type="number" min={0} value={qty} onChange={(e) => setQty(e.target.value)} />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetItem(null)}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={busy || !setItem?.dept_row_id}
            onClick={() => {
              if (!setItem?.dept_row_id) return;
              withAdminPw(async (admin_password) => {
                setBusy(true);
                try {
                  await apiPost("honey.php", "executive_edit", {
                    stage: "dept",
                    row_table: "dept_inventory_rows",
                    row_id: setItem.dept_row_id,
                    fields: {
                      current_qty: Number(qty),
                      qty_mode: "set_exact",
                      log_source: "monthly_inventory",
                    },
                    admin_password,
                  });
                  setSetItem(null);
                  onSaved("Finished qty updated.");
                  await onReload();
                } finally {
                  setBusy(false);
                }
              });
            }}
          >
            Save
          </Button>
        </AppModalFooter>
      </AppModal>
    </div>
  );
}

function RtsSection({
  honey,
  other,
  onSaved,
  onReload,
  onError,
}: {
  honey: RtsHoney[];
  other: RtsOther[];
  onSaved: (m: string) => void;
  onReload: () => Promise<void>;
  onError: (m: string) => void;
}) {
  const [setHoney, setSetHoney] = useState<RtsHoney | null>(null);
  const [sizes, setSizes] = useState<Record<string, string>>({});
  const [setOther, setSetOther] = useState<RtsOther | null>(null);
  const [otherQty, setOtherQty] = useState("");
  const [busy, setBusy] = useState(false);
  const withAdminPw = useMonthlyAdminPw();

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <h2 className="text-sm font-semibold">RTS honey (sellable)</h2>
        <div className="rounded-xl border overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-secondary/40 text-xs text-muted-foreground">
              <tr>
                <th className="text-left px-3 py-2">Variant</th>
                <th className="text-left px-3 py-2">1L</th>
                <th className="text-left px-3 py-2">250</th>
                <th className="text-left px-3 py-2">350</th>
                <th className="text-left px-3 py-2">Gal</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {honey.map((r) => (
                <tr key={r.variant} className={cn("border-t", appHoverRow)}>
                  <td className="px-3 py-2">{r.variant}</td>
                  <td className="px-3 py-2">{r.current_liter}</td>
                  <td className="px-3 py-2">{r.current_250ml}</td>
                  <td className="px-3 py-2">{r.current_350ml}</td>
                  <td className="px-3 py-2">{r.current_gallon}</td>
                  <td className="px-3 py-2 text-right">
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      onClick={() => {
                        setSetHoney(r);
                        setSizes({
                          liter: String(r.current_liter),
                          "250ml": String(r.current_250ml),
                          "350ml": String(r.current_350ml),
                          gallon: String(r.current_gallon),
                        });
                      }}
                    >
                      Set
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <div className="space-y-2">
        <h2 className="text-sm font-semibold">RTS other</h2>
        <LotTable
          rows={other}
          label={(r) => r.product_label}
          qty={(r) => Number(r.current_qty)}
          onSet={(r) => {
            setSetOther(r);
            setOtherQty(String(r.current_qty));
          }}
        />
      </div>

      <AppModal open={!!setHoney} onClose={() => setSetHoney(null)} labelledBy="rts-h">
        <AppModalHeader titleId="rts-h" title={`Set RTS ${setHoney?.variant ?? ""}`} onClose={() => setSetHoney(null)} />
        <AppModalBody className="grid grid-cols-2 gap-2">
          {HONEY_SIZES.map((s) => (
            <label key={s} className="text-xs space-y-1">
              <span>{s}</span>
              <Input value={sizes[s] ?? "0"} onChange={(e) => setSizes((p) => ({ ...p, [s]: e.target.value }))} />
            </label>
          ))}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetHoney(null)}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={busy}
            onClick={() => {
              if (!setHoney) return;
              withAdminPw(async (admin_password) => {
                setBusy(true);
                try {
                  const size_qtys: Record<string, number> = {};
                  for (const s of HONEY_SIZES) size_qtys[s] = Number(sizes[s] ?? 0);
                  await apiPost("rts.php", "inventory_set_honey", {
                    variant: setHoney.variant,
                    pool: "sellable",
                    size_qtys,
                    monthly_inventory: true,
                    admin_password,
                  });
                  setSetHoney(null);
                  onSaved("RTS honey updated.");
                  await onReload();
                } finally {
                  setBusy(false);
                }
              });
            }}
          >
            Save
          </Button>
        </AppModalFooter>
      </AppModal>

      <AppModal open={!!setOther} onClose={() => setSetOther(null)} labelledBy="rts-o">
        <AppModalHeader titleId="rts-o" title="Set RTS other qty" onClose={() => setSetOther(null)} />
        <AppModalBody>
          <Input type="number" min={0} value={otherQty} onChange={(e) => setOtherQty(e.target.value)} />
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setSetOther(null)}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={busy}
            onClick={() => {
              if (!setOther) return;
              withAdminPw(async (admin_password) => {
                setBusy(true);
                try {
                  await apiPost("rts.php", "inventory_set_other", {
                    product_label: setOther.product_label,
                    qty: Number(otherQty),
                    pool: "sellable",
                    monthly_inventory: true,
                    admin_password,
                  });
                  setSetOther(null);
                  onSaved("RTS other updated.");
                  await onReload();
                } finally {
                  setBusy(false);
                }
              });
            }}
          >
            Save
          </Button>
        </AppModalFooter>
      </AppModal>
    </div>
  );
}

function LotTable<T extends { id?: number } | object>({
  rows,
  label,
  qty,
  onSet,
}: {
  rows: T[];
  label: (r: T) => string;
  qty: (r: T) => number;
  onSet: (r: T) => void;
}) {
  return (
    <div className="rounded-xl border overflow-hidden">
      <table className="w-full text-sm">
        <thead className="bg-secondary/40 text-xs text-muted-foreground">
          <tr>
            <th className="text-left px-3 py-2">Item</th>
            <th className="text-left px-3 py-2">System qty</th>
            <th className="px-3 py-2" />
          </tr>
        </thead>
        <tbody>
          {rows.map((r, i) => (
            <tr key={"id" in r && r.id != null ? String(r.id) : `${label(r)}-${i}`} className={cn("border-t", appHoverRow)}>
              <td className="px-3 py-2">{label(r)}</td>
              <td className="px-3 py-2 font-medium tabular-nums">{qty(r)}</td>
              <td className="px-3 py-2 text-right">
                <Button type="button" size="sm" variant="outline" onClick={() => onSet(r)}>
                  Set
                </Button>
              </td>
            </tr>
          ))}
          {rows.length === 0 ? (
            <tr>
              <td colSpan={3} className="px-3 py-8 text-center text-muted-foreground">
                No rows.
              </td>
            </tr>
          ) : null}
        </tbody>
      </table>
    </div>
  );
}
