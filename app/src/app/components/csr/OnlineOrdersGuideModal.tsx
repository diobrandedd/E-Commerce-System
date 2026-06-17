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
  csr_tl: "CSR Team Leader",
  csr_staff: "CSR Staff",
};

function CsrWorkflow() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Start batch</strong> — one active batch per session; cancel only if you need
        to abandon work.
      </li>
      <li>
        <strong className="text-foreground">Prep tools</strong> — VIP arranger, SPX merge, or Excel validate download
        arranged iPYX sheets only (no stamping yet).
      </li>
      <li>
        <strong className="text-foreground">PDF + Excel stamper</strong> — when prep Excel is ready, stamp waybill PDFs
        into the batch.
      </li>
      <li>
        <strong className="text-foreground">Done — download master Excel</strong> — finalizes batch, promotes parcels to
        packing, downloads master print file for warehouse.
      </li>
    </ol>
  );
}

export function OnlineOrdersGuideModal({
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
  const isExec = role === "executive";

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="online-orders-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Online Orders guide
            {roleLabel && (
              <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                {roleLabel}
              </span>
            )}
          </span>
        }
        titleId="online-orders-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            CSR batch workflow for partner orders: prep Excel from VIP/SPX/uploads, stamp waybills, then finalize into
            master Excel and online parcels in <strong className="text-foreground">packing</strong> status.
          </p>
        </GuideSection>
        <GuideSection title={isExec ? "Workflow (Executive oversight)" : "Your workflow (CSR)"}>
          {isExec ? (
            <p>
              Same tools as CSR — use for troubleshooting or filling in. Executives can start/cancel batches and finalize
              when CSR is unavailable.
            </p>
          ) : (
            <CsrWorkflow />
          )}
        </GuideSection>
        <GuideSection title="Tool picker">
          <ul className="list-disc pl-4 space-y-1">
            <li><strong className="text-foreground">VIP Excel arranger</strong> — courier sheet → remark resolution → iPYX download</li>
            <li><strong className="text-foreground">SPX name merge</strong> — packing list / PDF → iPYX download</li>
            <li><strong className="text-foreground">Validate Excel</strong> — check an existing partner workbook</li>
            <li><strong className="text-foreground">PDF + Excel stamper</strong> — merge labels into batch (separate step)</li>
          </ul>
        </GuideSection>
        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
