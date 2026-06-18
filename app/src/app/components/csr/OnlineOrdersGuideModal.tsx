import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import {
  GuideMultiTourFooter,
  GuideWalkthroughSection,
  type GuideTourOption,
} from "@/app/components/tour/GuideTourFooter";
import type { OnlineOrdersTourId } from "@/app/components/tour/guides/onlineOrdersTour";
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

const TOUR_OPTIONS: GuideTourOption[] = [
  { id: "overview", label: "Overview tour" },
  { id: "vip", label: "VIP tour" },
  { id: "spx", label: "SPX tour" },
  { id: "excel", label: "Validate tour" },
  { id: "stamper", label: "Stamper tour" },
];

function CsrWorkflow() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Start batch</strong> — one active batch per session; cancel only if you need
        to abandon work.
      </li>
      <li>
        <strong className="text-foreground">Pick one prep tool</strong> — VIP, SPX, or Validate (Excel download only).
      </li>
      <li>
        <strong className="text-foreground">PDF + Excel stamper</strong> — separate step when prep Excel + waybill PDFs
        are ready.
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
  onStartTour: (tourId: OnlineOrdersTourId) => void;
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
            CSR batch workflow for partner orders: four tools on the picker — three prep Excel, one stamper. Finish with
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

        <GuideSection title="VIP Excel arranger">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Upload J&T / VIP courier Excel (.xlsx).</li>
            <li>Resolve ambiguous remark cards (variant picks) if shown.</li>
            <li>
              <strong className="text-foreground">Build arranged Excel</strong> — downloads remarks export + iPYX sheet.
            </li>
            <li>Back to tools → open stamper when PDFs are ready.</li>
          </ol>
        </GuideSection>

        <GuideSection title="SPX name merge">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Choose <strong className="text-foreground">PDF only</strong> or packing list + PDF mode.</li>
            <li>Upload PDFs (and packing list if needed) → Extract / Import.</li>
            <li>Confirm variant on each line → Review all items.</li>
            <li>
              <strong className="text-foreground">Build iPYX Excel</strong> → download → back to tools for stamper.
            </li>
          </ol>
        </GuideSection>

        <GuideSection title="Validate existing Excel">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Upload partner iPYX workbook(s).</li>
            <li>
              <strong className="text-foreground">Validate Excel</strong> — checks columns and row count.
            </li>
            <li>Download copy if needed → Back to tools → stamper.</li>
          </ol>
        </GuideSection>

        <GuideSection title="PDF + Excel stamper">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Set order type: Partner, VIP, or Shopee SPX (match how Excel was built).</li>
            <li>Upload prepared Excel → Load Excel → optional download to verify.</li>
            <li>Upload matching waybill PDFs.</li>
            <li>
              <strong className="text-foreground">Stamp &amp; save to batch</strong> — repeat for more PDF sets, then
              finish batch.
            </li>
          </ol>
        </GuideSection>

        <GuideWalkthroughSection multiTool />
      </AppModalBody>
      <GuideMultiTourFooter
        tours={TOUR_OPTIONS}
        onStartTour={id => onStartTour(id as OnlineOrdersTourId)}
        onClose={onClose}
      />
    </AppModal>
  );
}
