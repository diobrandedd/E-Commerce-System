import { BookOpen } from "lucide-react";
import { Link } from "react-router";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import type { Role } from "@/app/context/AuthContext";

const ROLE_LABELS: Partial<Record<Role, string>> = {
  executive: "Executive",
  hr: "HR",
  prod_manager: "Production Manager",
};

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

function HrGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Add item</strong> — create a new supply in the org-wide catalog when something new is stocked.
      </li>
      <li>
        <strong className="text-foreground">Stock in lot</strong> — when supplies arrive, open the item and enter <strong className="text-foreground">total price paid</strong> and <strong className="text-foreground">quantity received</strong> (per piece). Unit cost updates from purchase lots.
      </li>
      <li>
        <strong className="text-foreground">Issue request</strong> — when staff need supplies, submit a request with requester name, item, quantity, and reason. Executive must approve before on-hand goes down.
      </li>
      <li>
        Track pending requests on{" "}
        <Link to="/inventory/requests#supply-requests-section" className="text-[#C8900A] font-medium hover:underline">
          Requests
        </Link>
        .
      </li>
    </ol>
  );
}

function ExecutiveGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Stock in lot</strong> — same as HR when recording deliveries.
      </li>
      <li>
        <strong className="text-foreground">Issue request</strong> — you can submit on behalf of staff if needed.
      </li>
      <li>
        <strong className="text-foreground">Set on-hand</strong> — direct count correction after a physical audit.
      </li>
      <li>
        <strong className="text-foreground">Approve or deny</strong> HR issue requests on{" "}
        <Link to="/inventory/requests#supply-requests-section" className="text-[#C8900A] font-medium hover:underline">
          Requests
        </Link>
        — not on this page.
      </li>
      <li>
        <strong className="text-foreground">Activity log</strong> — full stock-in and issue history on each item.
      </li>
    </ol>
  );
}

function ProdManagerGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">View only</strong> — check on-hand before operations.
      </li>
      <li>
        Ask <strong className="text-foreground">HR</strong> to stock in new deliveries or submit an issue request for staff who need supplies.
      </li>
    </ol>
  );
}

interface SuppliesInventoryGuideModalProps {
  open: boolean;
  onClose: () => void;
  role: Role | undefined;
}

export function SuppliesInventoryGuideModal({ open, onClose, role }: SuppliesInventoryGuideModalProps) {
  const roleLabel = role ? ROLE_LABELS[role] : undefined;

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="supplies-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Supplies inventory guide
            {roleLabel && (
              <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                {roleLabel}
              </span>
            )}
          </span>
        }
        titleId="supplies-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="Org-wide pool">
          <p>
            Supplies are shared across the organization (gloves, cleaning items, etc.). Each item is tracked <strong className="text-foreground">per piece</strong> with weighted-average unit cost from stock-in lots.
          </p>
        </GuideSection>

        {role === "hr" && (
          <GuideSection title="Your workflow (HR)">
            <HrGuide />
          </GuideSection>
        )}
        {role === "executive" && (
          <GuideSection title="Your workflow (Executive)">
            <ExecutiveGuide />
          </GuideSection>
        )}
        {role === "prod_manager" && (
          <GuideSection title="Your workflow (Production Manager)">
            <ProdManagerGuide />
          </GuideSection>
        )}
        {(!role || !ROLE_LABELS[role as Role]) && (
          <GuideSection title="Your workflow">
            <p>Sign in with an inventory role to see your workflow steps.</p>
          </GuideSection>
        )}
      </AppModalBody>
    </AppModal>
  );
}
