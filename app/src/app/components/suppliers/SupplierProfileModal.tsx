import { useEffect, useState } from "react";
import { Loader2, Edit2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { getSupplier, type Supplier } from "@/app/lib/suppliers";
import { ApiError } from "@/app/lib/api";

interface SupplierProfileModalProps {
  supplierId: number;
  supplierCode?: string;
  onClose: () => void;
  onEdit?: (supplier: Supplier) => void;
}

export function SupplierProfileModal({ supplierId, supplierCode, onClose, onEdit }: SupplierProfileModalProps) {
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    getSupplier(supplierId)
      .then(d => {
        if (!cancelled) setSupplier(d.supplier);
      })
      .catch(e => {
        if (!cancelled) setError(e instanceof ApiError ? e.message : "Could not load supplier.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [supplierId]);

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-lg w-full" labelledBy="supplier-profile-title" zIndex="z-[100]">
      <AppModalHeader
        title="Supplier profile"
        titleId="supplier-profile-title"
        subtitle={supplierCode ? `Code: ${supplierCode}` : undefined}
        onClose={onClose}
      >
        {onEdit && supplier && (
          <button
            type="button"
            onClick={() => onEdit(supplier)}
            className="p-2 rounded-lg hover:bg-muted/60 text-muted-foreground"
            title="Edit supplier"
          >
            <Edit2 size={16} />
          </button>
        )}
      </AppModalHeader>
      <AppModalBody>
          {loading && (
            <div className="py-12 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={22} />
            </div>
          )}
          {error && <p className="text-sm text-red-600 py-4">{error}</p>}
          {!loading && !error && supplier && (
            <div className="space-y-4">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="text-lg font-bold text-foreground">{supplier.display_name}</p>
                  <p className="text-sm text-muted-foreground">{supplier.category_label}</p>
                </div>
                <span
                  className={`text-xs font-semibold px-2.5 py-1 rounded-full shrink-0 ${
                    supplier.is_active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"
                  }`}
                >
                  {supplier.is_active ? "Active" : "Inactive"}
                </span>
              </div>

              <dl className="grid grid-cols-1 gap-3 text-sm">
                <div>
                  <dt className="text-xs text-muted-foreground uppercase tracking-wide">Contact</dt>
                  <dd className="font-medium">{supplier.contact_number}</dd>
                </div>
                <div>
                  <dt className="text-xs text-muted-foreground uppercase tracking-wide">Payment account</dt>
                  <dd className="font-medium">{supplier.payment_account ?? "—"}</dd>
                  {supplier.payment_account_number && (
                    <dd className="text-muted-foreground font-mono text-xs mt-0.5">{supplier.payment_account_number}</dd>
                  )}
                </div>
                <div>
                  <dt className="text-xs text-muted-foreground uppercase tracking-wide">Address</dt>
                  <dd className="font-medium whitespace-pre-wrap">{supplier.address}</dd>
                </div>
                <div className="grid grid-cols-2 gap-3 text-xs text-muted-foreground">
                  <div>
                    <span className="block uppercase tracking-wide">Registered</span>
                    <span className="text-foreground">{new Date(supplier.created_at.replace(" ", "T")).toLocaleString()}</span>
                  </div>
                  <div>
                    <span className="block uppercase tracking-wide">Updated</span>
                    <span className="text-foreground">{new Date(supplier.updated_at.replace(" ", "T")).toLocaleString()}</span>
                  </div>
                </div>
              </dl>

              <div>
                <p className="text-xs text-muted-foreground uppercase tracking-wide mb-2">Valid ID</p>
                {supplier.valid_id_url ? (
                  <a href={supplier.valid_id_url} target="_blank" rel="noreferrer" className="block rounded-lg border overflow-hidden bg-secondary/30">
                    <img
                      src={supplier.valid_id_url}
                      alt="Supplier valid ID"
                      className="w-full max-h-72 object-contain"
                    />
                  </a>
                ) : (
                  <p className="text-sm text-muted-foreground">No ID photo on file.</p>
                )}
              </div>
            </div>
          )}
      </AppModalBody>
    </AppModal>
  );
}
