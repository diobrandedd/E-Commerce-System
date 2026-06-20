import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { GuideTourFooter, GuideWalkthroughSection } from "@/app/components/tour/GuideTourFooter";

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

interface DeliveriesCancelledGuideModalProps {
  open: boolean;
  onClose: () => void;
  isExecutive: boolean;
  onStartTour: () => void;
}

export function DeliveriesCancelledGuideModal({
  open,
  onClose,
  isExecutive,
  onStartTour,
}: DeliveriesCancelledGuideModalProps) {
  const roleLabel = isExecutive ? "Executive" : "Production Manager";

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="deliveries-cancelled-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Deliveries cancelled guide
            <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
              {roleLabel}
            </span>
          </span>
        }
        titleId="deliveries-cancelled-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Record courier-cancelled orders after you get void feedback. The physical box is usually still{" "}
            <strong className="text-foreground">sealed and packed</strong> — inventory stays deducted so you can
            re-waybill and re-ship on Add deliveries.
          </p>
        </GuideSection>

        <GuideSection title={`Your workflow (${roleLabel})`}>
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Scan or type the old tracking number when the courier marks the order cancelled.</li>
            <li>Confirm the prompt — status becomes <strong className="text-foreground">cancelled</strong>.</li>
            <li>CSR prints a replacement waybill if needed (Online Orders).</li>
            <li>
              Go to <strong className="text-foreground">Add deliveries → Re-waybill</strong>: old TN → new TN → photo →
              ship.
            </li>
            <li>Use the log table below to audit who cancelled and when.</li>
          </ol>
        </GuideSection>

        <GuideSection title="What does not happen">
          <ul className="list-disc pl-4 space-y-1">
            <li>Inventory is <strong className="text-foreground">not</strong> restored — the box is still packed</li>
            <li>This is not an opened return (RTS handles opened returns separately)</li>
            <li>Do not use this for wrong-label only — use On hold on Add deliveries instead</li>
          </ul>
        </GuideSection>

        <GuideSection title="Allowed parcel status">
          <p>
            Cancel scan works from <strong className="text-foreground">packing</strong> or{" "}
            <strong className="text-foreground">shipped</strong>. Already-cancelled parcels show a friendly message if
            scanned again.
          </p>
        </GuideSection>

        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
