import { useState } from "react";
import { Loader2 } from "lucide-react";
import { getSupplierByHoneyCode } from "@/app/lib/suppliers";
import { ApiError } from "@/app/lib/api";
import { SupplierProfileModal } from "@/app/components/suppliers/SupplierProfileModal";

interface SupplierCodeButtonProps {
  code: string;
  className?: string;
}

export function SupplierCodeButton({ code, className = "" }: SupplierCodeButtonProps) {
  const [profileId, setProfileId] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const openProfile = async () => {
    if (loading || profileId) return;
    setLoading(true);
    setErr(null);
    try {
      const data = await getSupplierByHoneyCode(code);
      setProfileId(data.supplier.id);
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Supplier not found");
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <button
        type="button"
        onClick={openProfile}
        disabled={loading}
        className={`font-mono font-medium text-[#C8900A] hover:underline text-left inline-flex items-center gap-1 ${className}`}
        title="View supplier profile"
      >
        {loading ? <Loader2 size={12} className="animate-spin" /> : null}
        {code}
      </button>
      {err && <span className="block text-[10px] text-red-600 mt-0.5">{err}</span>}
      {profileId !== null && (
        <SupplierProfileModal
          supplierId={profileId}
          supplierCode={code}
          onClose={() => setProfileId(null)}
        />
      )}
    </>
  );
}
