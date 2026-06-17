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

export function StaffManagementGuideModal({
  open,
  onClose,
  onStartTour,
}: {
  open: boolean;
  onClose: () => void;
  onStartTour: () => void;
}) {
  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="staff-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Staff management guide
            <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
              Executive
            </span>
          </span>
        }
        titleId="staff-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Create and maintain Pyx Tracker logins: executives, production managers, HR, CSR team leaders/staff, and POS
            cashiers. Default password for new users is their <strong className="text-foreground">employee ID</strong>.
          </p>
        </GuideSection>
        <GuideSection title="Your workflow">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Add Staff</strong> — name, username, employee ID, role, and department
              when required (TL/Staff need a dept).
            </li>
            <li>
              <strong className="text-foreground">Edit</strong> — update role, department, or active status.
            </li>
            <li>
              <strong className="text-foreground">Reset password</strong> — sets password back to employee ID if someone
              is locked out.
            </li>
            <li>
              <strong className="text-foreground">Toggle active</strong> — deactivate leavers without deleting history.
            </li>
          </ol>
        </GuideSection>
        <GuideSection title="Roles at a glance">
          <ul className="list-disc pl-4 space-y-1">
            <li><strong className="text-foreground">Executive</strong> — full admin, costing, suppliers, staff</li>
            <li><strong className="text-foreground">Production Manager</strong> — inventory, deliveries, production</li>
            <li><strong className="text-foreground">HR</strong> — stock-in, supply/packaging requests</li>
            <li><strong className="text-foreground">CSR TL / Staff</strong> — online orders, parcels (dept: CSR)</li>
            <li><strong className="text-foreground">POS</strong> — walk-in sales terminal only</li>
          </ul>
        </GuideSection>
        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
