import { BookOpen } from "lucide-react";
import { Link } from "react-router";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { GuideTourFooter, GuideWalkthroughSection } from "@/app/components/tour/GuideTourFooter";
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

function TypeLegend() {
  return (
    <div className="grid sm:grid-cols-2 gap-2">
      <div className="rounded-lg border bg-secondary/20 px-3 py-2">
        <p className="font-semibold text-foreground">
          <span className="inline-block text-[10px] px-1.5 py-0.5 rounded-full bg-[#C8900A]/10 text-[#8a6507] mr-1.5">Per-unit</span>
          Pouches, bottles, stickers
        </p>
        <p className="mt-1">
          Counted one-by-one. Staff with deduct permission <strong className="text-foreground">scan stock out</strong> when materials go to the floor.
        </p>
      </div>
      <div className="rounded-lg border border-dashed bg-secondary/20 px-3 py-2">
        <p className="font-semibold text-foreground">
          <span className="inline-block text-[10px] px-1.5 py-0.5 rounded-full bg-secondary mr-1.5">Pool</span>
          Rolls — yarn, paper, seals, foil, bubble wrap
        </p>
        <p className="mt-1">
          Tracked in <strong className="text-foreground">whole rolls</strong>. Deduct a roll when opened; costing uses a yield estimate per roll.
        </p>
      </div>
      <div className="rounded-lg border bg-amber-50/50 px-3 py-2 sm:col-span-2">
        <p className="font-semibold text-foreground">
          <span className="inline-block text-[10px] px-1.5 py-0.5 rounded-full bg-[#C8900A]/15 text-[#8a6507] mr-1.5">Fulfillment</span>
          Parcel ship — bubble rolls, cushion rolls, LAD pieces, pouches, foam rolls, cardboard
        </p>
        <p className="mt-1">
          Use the <strong className="text-foreground">For packing</strong> filter. Stock in whole rolls — including unsized <strong className="text-foreground">Bubble wrap roll</strong> (width 13 / 17 / 22 cm is chosen when issuing to packing). Sized bubble SKUs are staging-only. Ship scan deducts staging — fractional rolls snap to 1/16.
        </p>
      </div>
    </div>
  );
}

function ExecutiveGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Stock in lot</strong> — only executives record purchase lots (total invoice + qty).
      </li>
      <li>
        <strong className="text-foreground">Scan stock out</strong> — scan a packaging barcode, confirm, enter qty + reason. This is the warehouse norm (no auto-deduct on produce).
      </li>
      <li>
        <strong className="text-foreground">Report damage</strong> — same deduct permission; deducts on-hand and logs the damaged pool. Filter row damage totals by Today / This week / Custom.
      </li>
      <li>
        <strong className="text-foreground">Generate all barcodes</strong> — print one A4 sheet with names under each barcode.
      </li>
      <li>
        <strong className="text-foreground">Level %</strong> — each row shows on-hand vs the last stock-in lot qty.
      </li>
      <li>
        <strong className="text-foreground">Set on-hand / pool yield</strong> — count corrections and costing yield.
      </li>
      <li>
        <strong className="text-foreground">Add catalog item</strong> — new packaging SKUs get a permanent barcode.
      </li>
    </ol>
  );
}

function HrGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">View only / requests</strong> — packaging stock-in is executive-only. Ask an executive to record purchase lots.
      </li>
      <li>
        <strong className="text-foreground">You do not deduct</strong> — production staff with deduct permission scan stock out. Flag low level % to executive.
      </li>
    </ol>
  );
}

function ProdManagerGuide() {
  return (
    <ol className="list-decimal pl-4 space-y-1.5">
      <li>
        <strong className="text-foreground">Scan stock out only</strong> — assigned staff scan a supply barcode and enter quantity. The catalog, history, and report damage stay with executives.
      </li>
      <li>
        <strong className="text-foreground">Pool rolls</strong> — stock out <strong className="text-foreground">1 whole roll</strong> when a roll is opened.
      </li>
      <li>
        <strong className="text-foreground">You do not stock in</strong> — executives record deliveries. Flag low stock or damage to an executive.
      </li>
      <li>
        Produce no longer auto-deducts packaging materials — scan stock out is required.
      </li>
    </ol>
  );
}

function TipsCopy({ isExec }: { isExec: boolean }) {
  if (isExec) {
    return (
      <p>
        Click any row for details and actions. Unit cost is weighted average from stock-in lots and feeds{" "}
        <Link to="/costing" className="text-[#C8900A] hover:underline">Product Costing</Link>.
        Stock-out access is listed at the top of the page.
      </p>
    );
  }
  return (
    <p>
      Click any row for details. Stock-out uses barcode scan (or deduct on the detail modal) when you have packaging deduct permission. Only executives stock in.
    </p>
  );
}

interface PackagingInventoryGuideModalProps {
  open: boolean;
  onClose: () => void;
  role: Role | undefined;
  onStartTour: () => void;
}

export function PackagingInventoryGuideModal({ open, onClose, role, onStartTour }: PackagingInventoryGuideModalProps) {
  const roleLabel = role ? ROLE_LABELS[role] : undefined;
  const isExec = role === "executive";

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="packaging-guide-title"
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
        titleId="packaging-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="Per-unit vs pool">
          <TypeLegend />
        </GuideSection>

        {role === "executive" && (
          <GuideSection title="Your workflow (Executive)">
            <ExecutiveGuide />
          </GuideSection>
        )}
        {role === "hr" && (
          <GuideSection title="Your workflow (HR)">
            <HrGuide />
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

        <GuideSection title="Tips">
          <TipsCopy isExec={isExec} />
        </GuideSection>

        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
