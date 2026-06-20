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

export type POSGuideContext = "category" | "product";

export function POSGuideModal({
  open,
  onClose,
  context,
  onStartTour,
}: {
  open: boolean;
  onClose: () => void;
  context: POSGuideContext;
  onStartTour: () => void;
}) {
  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="pos-guide-title"
      zIndex="z-[200]"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            POS guide
            <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
              Cashier
            </span>
          </span>
        }
        titleId="pos-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What POS is for">
          <p>
            Walk-in sales at the warehouse — tap products, build a cart, checkout, print receipt. Honey uses variant +
            bottle size; dept products use single-SKU qty.
          </p>
        </GuideSection>
        {context === "category" ? (
          <GuideSection title="Start here">
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>Pick a product category (Honey, Paragis, BRC, Jar Cacao).</li>
              <li>Add items to cart, then Checkout at the bottom.</li>
              <li>Enter buyer name if needed; complete sale for receipt.</li>
            </ol>
          </GuideSection>
        ) : (
          <GuideSection title="On a product screen">
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>
                <strong className="text-foreground">Cart</strong> strip at top — review lines and subtotal.
              </li>
              <li>Tap variant / product tiles to open qty picker (respects on-hand stock).</li>
              <li>
                <strong className="text-foreground">Checkout</strong> — fixed bar at bottom when cart has items.
              </li>
              <li>Honey: large discounts may need executive approval before receipt prints.</li>
            </ol>
          </GuideSection>
        )}
        <GuideSection title="Exit">
          <p>
            Use <strong className="text-foreground">Exit</strong> in the header to log out of POS and return to the login
            screen. Use <strong className="text-foreground">Back</strong> on product pages to change category.
          </p>
        </GuideSection>
        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
