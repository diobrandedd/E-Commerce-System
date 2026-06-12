import { BookOpen } from "lucide-react";
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
        <strong className="text-foreground">Supply issue requests</strong> — use <em>Supply issue request</em> to submit: requester name, select item, quantity (pieces), and reason.
      </li>
      <li>
        Track status in the <strong className="text-foreground">Supply issue requests</strong> table below — pending until executive approves.
      </li>
      <li>
        When approved, on-hand deducts automatically. If denied, read the executive&apos;s note and resubmit if needed.
      </li>
      <li>
        You cannot approve requests on this page.
      </li>
    </ol>
  );
}

function ExecutiveGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Supply issues</strong> — click a pending row to review details. <strong className="text-foreground">Approve</strong> deducts stock; <strong className="text-foreground">Deny</strong> requires a note to HR.
      </li>
      <li>
        Red badge / bell icon alerts you when new supply requests arrive.
      </li>
      <li>
        <strong className="text-foreground">Packaging count requests</strong> — separate section for packaging on-hand corrections.
      </li>
      <li>
        <strong className="text-foreground">Inventory corrections</strong> — honey pipeline qty fixes from production manager.
      </li>
      <li>
        Also review edit requests and POS discount approvals in their sections when shown.
      </li>
    </ol>
  );
}

function ProdManagerGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Inventory corrections</strong> — submit honey warehouse / production / packaging qty fixes using <em>New request</em>.
      </li>
      <li>
        Supply and packaging material tables are read-only for you — HR submits those requests.
      </li>
    </ol>
  );
}

interface InventoryRequestsGuideModalProps {
  open: boolean;
  onClose: () => void;
  role: Role | undefined;
}

export function InventoryRequestsGuideModal({ open, onClose, role }: InventoryRequestsGuideModalProps) {
  const roleLabel = role ? ROLE_LABELS[role] : undefined;

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="requests-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Requests guide
            {roleLabel && (
              <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                {roleLabel}
              </span>
            )}
          </span>
        }
        titleId="requests-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
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
            <p>Sign in to see role-specific request workflows.</p>
          </GuideSection>
        )}

        <GuideSection title="Sections on this page">
          <ul className="list-disc pl-4 space-y-1">
            <li><strong className="text-foreground">Supply issue requests</strong> — HR deduct-for-use (executive approval)</li>
            <li><strong className="text-foreground">Packaging count requests</strong> — packaging on-hand corrections</li>
            <li><strong className="text-foreground">Inventory corrections</strong> — honey pipeline (exec / PM)</li>
          </ul>
        </GuideSection>
      </AppModalBody>
    </AppModal>
  );
}
