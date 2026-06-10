import { Fragment, useCallback, useEffect, useState } from "react";
import { Plus, Search, Edit2, Loader2, Camera, Eye, ChevronDown, ChevronRight } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { SupplierProfileModal } from "@/app/components/suppliers/SupplierProfileModal";
import { SupplierHoneyPricesInline } from "@/app/components/suppliers/SupplierHoneyPricesInline";
import { SupplierDeptPricesInline } from "@/app/components/suppliers/SupplierDeptPricesInline";
import { fetchSupplierHoneyPrices } from "@/app/lib/honeyPricing";
import {
  listSuppliers,
  setSupplierActive,
  createSupplierForm,
  updateSupplierForm,
  SUPPLIER_CATEGORIES,
  type Supplier,
  type SupplierCategory,
} from "@/app/lib/suppliers";
import { ApiError } from "@/app/lib/api";

export function SuppliersPage() {
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [modal, setModal] = useState<"add" | Supplier | null>(null);
  const [profileId, setProfileId] = useState<number | null>(null);
  const [expandedPricingId, setExpandedPricingId] = useState<number | null>(null);
  const [pendingCount, setPendingCount] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await listSuppliers();
      setSuppliers(data.suppliers);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load suppliers.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  useEffect(() => {
    void fetchSupplierHoneyPrices()
      .then(data => setPendingCount(data.pending_count))
      .catch(() => {});
  }, []);

  const filtered = suppliers.filter(s => {
    const q = search.toLowerCase();
    if (!q) return true;
    return (
      s.display_name.toLowerCase().includes(q) ||
      s.category_label.toLowerCase().includes(q) ||
      (s.contact_number ?? "").includes(q)
    );
  });

  const toggleActive = async (s: Supplier) => {
    try {
      await setSupplierActive(s.id, !s.is_active);
      refresh();
    } catch (e) {
      alert(e instanceof ApiError ? e.message : "Failed");
    }
  };

  const togglePricingExpand = (s: Supplier) => {
    setExpandedPricingId(prev => (prev === s.id ? null : s.id));
  };

  const supplierHasInlinePricing = (s: Supplier) =>
    s.category === "honey" || s.category === "paragis" || s.category === "black_rice" || s.category === "cacao";

  return (
    <PageLayout title="Supplier Master" subtitle="Executive registers suppliers; production managers select from this list">
      <div className="space-y-5">
        {error && <p className="text-sm text-red-600">{error}</p>}
        {pendingCount > 0 && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>{pendingCount}</strong> warehouse delivery row{pendingCount === 1 ? "" : "s"} still need a supplier
            price. Expand a honey supplier below and save ₱/gallon rates.
          </div>
        )}
        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center justify-between px-6 py-4 border-b border-border gap-4">
            <div className="relative flex-1 max-w-xs">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search suppliers…"
                className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background outline-none focus:border-[#C8900A] transition-colors"
              />
            </div>
            <button
              type="button"
              onClick={() => setModal("add")}
              className="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white"
              style={{ background: "#C8900A" }}
            >
              <Plus size={15} /> Add Supplier
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Name", "Category", "Contact", "Payment", "Address", "Status", ""].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={7} className="py-10 text-center text-muted-foreground"><Loader2 className="inline animate-spin" size={16} /></td></tr>
                )}
                {!loading && filtered.map((s, i) => {
                  const isHoney = s.category === "honey";
                  const isParagis = s.category === "paragis";
                  const isBlackRice = s.category === "black_rice";
                  const isCacao = s.category === "cacao";
                  const hasPricing = supplierHasInlinePricing(s);
                  const isExpanded = hasPricing && expandedPricingId === s.id;
                  return (
                    <Fragment key={s.id}>
                      <tr
                        role="button"
                        tabIndex={0}
                        aria-expanded={hasPricing ? isExpanded : undefined}
                        onClick={() => {
                          if (hasPricing) togglePricingExpand(s);
                          else setProfileId(s.id);
                        }}
                        onKeyDown={e => {
                          if (e.key !== "Enter" && e.key !== " ") return;
                          e.preventDefault();
                          if (hasPricing) togglePricingExpand(s);
                          else setProfileId(s.id);
                        }}
                        className={`border-b border-border hover:bg-secondary/30 cursor-pointer ${
                          isExpanded ? "bg-amber-50/80" : i % 2 ? "bg-secondary/10" : ""
                        }`}
                      >
                        <td className="px-6 py-3.5 font-semibold">
                          <span className="inline-flex items-center gap-1.5">
                            {hasPricing && (
                              isExpanded ? (
                                <ChevronDown size={14} className="text-[#C8900A]" />
                              ) : (
                                <ChevronRight size={14} className="text-muted-foreground" />
                              )
                            )}
                            {s.display_name}
                          </span>
                        </td>
                        <td className="px-5 py-3.5">{s.category_label}</td>
                        <td className="px-5 py-3.5">{s.contact_number}</td>
                        <td className="px-5 py-3.5 text-xs">
                          <p>{s.payment_account ?? "—"}</p>
                          <p className="text-muted-foreground">{s.payment_account_number ?? ""}</p>
                        </td>
                        <td className="px-5 py-3.5 text-muted-foreground max-w-[200px] truncate">{s.address}</td>
                        <td className="px-5 py-3.5">
                          <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${s.is_active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"}`}>
                            {s.is_active ? "Active" : "Inactive"}
                          </span>
                        </td>
                        <td className="px-5 py-3.5 space-x-2 whitespace-nowrap" onClick={e => e.stopPropagation()}>
                          <button
                            type="button"
                            onClick={() => setProfileId(s.id)}
                            className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A]"
                            title="View supplier profile"
                          >
                            <Eye size={12} /> View
                          </button>
                          {s.valid_id_url && (
                            <a href={s.valid_id_url} target="_blank" rel="noreferrer" className="text-xs text-[#C8900A] underline">ID</a>
                          )}
                          <button type="button" onClick={() => setModal(s)} className="p-1.5 rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground inline-flex">
                            <Edit2 size={14} />
                          </button>
                          <button type="button" onClick={() => toggleActive(s)} className="text-xs font-semibold text-muted-foreground hover:text-foreground">
                            {s.is_active ? "Deactivate" : "Activate"}
                          </button>
                        </td>
                      </tr>
                      {isExpanded && isHoney && (
                        <tr className="border-b border-border bg-secondary/20">
                          <td colSpan={7} className="px-6 py-4" onClick={e => e.stopPropagation()}>
                            <SupplierHoneyPricesInline
                              supplierId={s.id}
                              supplierName={s.display_name}
                              onPendingCountChange={setPendingCount}
                            />
                          </td>
                        </tr>
                      )}
                      {isExpanded && isParagis && (
                        <tr className="border-b border-border bg-secondary/20">
                          <td colSpan={7} className="px-6 py-4" onClick={e => e.stopPropagation()}>
                            <SupplierDeptPricesInline
                              supplierId={s.id}
                              supplierName={s.display_name}
                              productKey="pt"
                            />
                          </td>
                        </tr>
                      )}
                      {isExpanded && isBlackRice && (
                        <tr className="border-b border-border bg-secondary/20">
                          <td colSpan={7} className="px-6 py-4" onClick={e => e.stopPropagation()}>
                            <SupplierDeptPricesInline
                              supplierId={s.id}
                              supplierName={s.display_name}
                              productKey="brc"
                            />
                          </td>
                        </tr>
                      )}
                      {isExpanded && isCacao && (
                        <tr className="border-b border-border bg-secondary/20">
                          <td colSpan={7} className="px-6 py-4" onClick={e => e.stopPropagation()}>
                            <SupplierDeptPricesInline
                              supplierId={s.id}
                              supplierName={s.display_name}
                              productKey="jar_cacao"
                            />
                          </td>
                        </tr>
                      )}
                    </Fragment>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>
      {profileId !== null && (
        <SupplierProfileModal
          supplierId={profileId}
          onClose={() => setProfileId(null)}
          onEdit={supplier => {
            setProfileId(null);
            setModal(supplier);
          }}
        />
      )}
      {modal && (
        <SupplierFormModal
          initial={modal === "add" ? null : modal}
          onClose={() => setModal(null)}
          onSaved={() => { setModal(null); refresh(); }}
        />
      )}
    </PageLayout>
  );
}

function SupplierFormModal({
  initial,
  onClose,
  onSaved,
}: {
  initial: Supplier | null;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [firstName, setFirstName] = useState(initial?.first_name ?? "");
  const [lastName, setLastName] = useState(initial?.last_name ?? "");
  const [contact, setContact] = useState(initial?.contact_number ?? "");
  const [payAcct, setPayAcct] = useState(initial?.payment_account ?? "");
  const [payNum, setPayNum] = useState(initial?.payment_account_number ?? "");
  const [address, setAddress] = useState(initial?.address ?? "");
  const [category, setCategory] = useState<SupplierCategory>(initial?.category ?? "honey");
  const [idFile, setIdFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErr(null);
    const fd = new FormData();
    if (initial) fd.set("id", String(initial.id));
    fd.set("first_name", firstName);
    fd.set("last_name", lastName);
    fd.set("contact_number", contact);
    fd.set("payment_account", payAcct);
    fd.set("payment_account_number", payNum);
    fd.set("address", address);
    fd.set("category", category);
    if (idFile) fd.set("valid_id", idFile);
    try {
      if (initial) {
        await updateSupplierForm(fd);
      } else {
        if (!idFile) {
          setErr("Valid ID photo is required.");
          setBusy(false);
          return;
        }
        await createSupplierForm(fd);
      }
      onSaved();
    } catch (ex) {
      setErr(ex instanceof ApiError ? ex.message : "Save failed");
    } finally {
      setBusy(false);
    }
  };

  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background outline-none focus:border-[#C8900A]";

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-lg w-full" labelledBy="supplier-form-modal-title">
      <AppModalHeader title={initial ? "Edit supplier" : "Add supplier"} titleId="supplier-form-modal-title" onClose={onClose} />
      <AppModalBody>
        <form onSubmit={submit} className="space-y-3">
          {err && <p className="text-xs text-red-600">{err}</p>}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs text-muted-foreground">First name</label>
              <input value={firstName} onChange={e => setFirstName(e.target.value)} className={inputCls} required />
            </div>
            <div>
              <label className="text-xs text-muted-foreground">Last name</label>
              <input value={lastName} onChange={e => setLastName(e.target.value)} className={inputCls} required />
            </div>
          </div>
          <div>
            <label className="text-xs text-muted-foreground">Contact number</label>
            <input value={contact} onChange={e => setContact(e.target.value)} className={inputCls} required />
          </div>
          <div>
            <label className="text-xs text-muted-foreground">Payment account (e.g. Cebuana)</label>
            <input value={payAcct} onChange={e => setPayAcct(e.target.value)} className={inputCls} required />
          </div>
          <div>
            <label className="text-xs text-muted-foreground">Account number</label>
            <input value={payNum} onChange={e => setPayNum(e.target.value)} className={inputCls} required />
          </div>
          <div>
            <label className="text-xs text-muted-foreground">Location / address</label>
            <textarea value={address} onChange={e => setAddress(e.target.value)} className={inputCls} rows={2} required />
          </div>
          <div>
            <label className="text-xs text-muted-foreground block mb-1">Valid ID photo{initial ? " (optional replace)" : ""}</label>
            <div className="flex flex-wrap gap-2">
              <label className="inline-flex items-center gap-1 text-xs font-semibold border rounded-lg px-3 py-2 cursor-pointer hover:bg-secondary">
                <Camera size={14} /> Camera / file
                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  className="sr-only"
                  onChange={e => setIdFile(e.target.files?.[0] ?? null)}
                />
              </label>
              {idFile && <span className="text-xs text-muted-foreground">{idFile.name}</span>}
              {initial?.valid_id_url && !idFile && (
                <a href={initial.valid_id_url} target="_blank" rel="noreferrer" className="text-xs text-[#C8900A] underline">Current ID</a>
              )}
            </div>
          </div>
          <div>
            <label className="text-xs text-muted-foreground">Product category</label>
            <select value={category} onChange={e => setCategory(e.target.value as SupplierCategory)} className={inputCls}>
              {SUPPLIER_CATEGORIES.map(c => (
                <option key={c.value} value={c.value}>{c.label}</option>
              ))}
            </select>
          </div>
          <button type="submit" disabled={busy} className="w-full py-2.5 rounded-lg text-sm font-bold text-white mt-2" style={{ background: "#C8900A" }}>
            {busy ? "Saving…" : initial ? "Update" : "Create supplier"}
          </button>
        </form>
      </AppModalBody>
    </AppModal>
  );
}
