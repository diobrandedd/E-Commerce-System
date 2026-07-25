import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

export function CourierSoaGuideModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="courier-soa-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Courier SOA guide
          </span>
        }
        titleId="courier-soa-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Upload one or more J&amp;T and/or SPX statement-of-account Excel files and compare tracking numbers to parcels
            you marked <strong className="text-foreground">shipped</strong> in Online Parcels for a ship-date range. You
            can mix couriers in a single run.
          </p>
        </GuideSection>
        <GuideSection title="How matching works">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Pick ship date from / to — this filters only our shipped parcels.</li>
            <li>Upload one or many SOA files (each format is auto-detected from headers).</li>
            <li>
              Tracking numbers from all files are merged. Our shipped set includes every courier detected in the uploads
              (J&amp;T and/or SPX).
            </li>
            <li>
              <strong className="text-foreground">Missing from SOA</strong> — we shipped them in range, but they are not
              on any uploaded file.
            </li>
            <li>
              <strong className="text-foreground">Extra in SOA</strong> — on a file, but not in our shipped set for that
              range / courier.
            </li>
          </ol>
        </GuideSection>
        <GuideSection title="SPX date tip">
          <p>
            SPX SOA rows use COD / settlement times, not ship dates. Set your ship-date range to the period you mean to
            check so “extra in SOA” is not mostly other days’ parcels.
          </p>
        </GuideSection>
        <GuideSection title="Download">
          <p>
            Each upload gets its own download: tracking / waybill cells are{" "}
            <span className="text-green-700 font-medium">green</span> when matched and{" "}
            <span className="text-red-700 font-medium">red</span> when not found in our shipped set. Amounts are ignored.
          </p>
        </GuideSection>
      </AppModalBody>
    </AppModal>
  );
}
