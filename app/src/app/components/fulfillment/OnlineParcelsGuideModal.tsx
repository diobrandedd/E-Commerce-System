import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { GuideTourFooter, GuideWalkthroughSection } from "@/app/components/tour/GuideTourFooter";
import type { Role } from "@/app/context/AuthContext";

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

const ROLE_LABELS: Partial<Record<Role, string>> = {
  executive: "Executive",
  prod_manager: "Production Manager",
  csr_tl: "CSR Team Leader",
  csr_staff: "CSR Staff",
};

export function OnlineParcelsGuideModal({
  open,
  onClose,
  role,
  onStartTour,
}: {
  open: boolean;
  onClose: () => void;
  role: Role | undefined;
  onStartTour: () => void;
}) {
  const roleLabel = role ? ROLE_LABELS[role] : undefined;
  const isCsr = role === "csr_tl" || role === "csr_staff";
  const isPm = role === "prod_manager";

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="online-parcels-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Online parcels guide
            {roleLabel && (
              <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                {roleLabel}
              </span>
            )}
          </span>
        }
        titleId="online-parcels-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Lookup and audit parcels created from Online Orders batches. See tracking, recipient, items, pipeline (VIP /
            SPX / etc.), and status before and after warehouse scan.
          </p>
        </GuideSection>
        <GuideSection title={`Your workflow${roleLabel ? ` (${roleLabel})` : ""}`}>
          {isCsr ? (
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>Search or scan tracking to answer customer or courier inquiries.</li>
              <li>Edit contents (packing only) — same item builder as VIP Online Orders; raw text fallback for odd marketplace lines.</li>
              <li>Filter by status to find packing vs shipped parcels — use <strong className="text-foreground">RTS</strong> after recording a return on Record RTS.</li>
            </ol>
          ) : isPm ? (
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>
                Before-shipment photos are saved on <strong className="text-foreground">Add deliveries</strong> by
                tracking (CSR finalize not required for the photo).
              </li>
              <li>After ship scan, status becomes <strong className="text-foreground">shipped</strong> — inventory deducts.</li>
              <li>Use Deliveries review for photo audit; Cancelled page for voided parcels.</li>
            </ol>
          ) : (
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>Search any tracking; open detail for CSR or warehouse disputes.</li>
              <li>Monitor packing → shipped flow and cancelled/returned edge cases.</li>
            </ol>
          )}
        </GuideSection>
        <GuideSection title="Status meanings">
          <ul className="list-disc pl-4 space-y-1">
            <li><strong className="text-foreground">packing</strong> — finalized from CSR batch, awaiting warehouse scan</li>
            <li><strong className="text-foreground">shipped</strong> — scanned and photo taken on Deliveries</li>
            <li><strong className="text-foreground">cancelled / returned</strong> — exception handling</li>
            <li><strong className="text-foreground">RTS</strong> — CSR recorded a return-to-sender case for this tracking</li>
          </ul>
        </GuideSection>
        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
