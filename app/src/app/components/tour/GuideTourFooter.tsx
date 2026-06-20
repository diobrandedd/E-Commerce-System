import type { ReactNode } from "react";
import { MousePointerClick } from "lucide-react";
import { AppModalFooter } from "@/app/components/AppModal";
import { AppPressable } from "@/app/components/layout/appFeedbackMotion";

export function GuideWalkthroughSection({ practiceMode }: { practiceMode?: boolean; multiTool?: boolean }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">Interactive walkthrough</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">
        {practiceMode ? (
          <p>
            Tap <strong className="text-foreground">Start practice tour</strong> for a hands-on walkthrough — click each
            highlighted control to advance. Nothing is saved (no batch, uploads, or stamping).
          </p>
        ) : (
          <p>
            Tap <strong className="text-foreground">Start tour</strong> below for a step-by-step highlight of each control
            on this page — arrows point at the real buttons and fields.
          </p>
        )}
      </div>
    </section>
  );
}

export type GuideTourOption = {
  id: string;
  label: string;
};

export function GuideMultiTourFooter({
  tours,
  onStartTour,
  onClose,
}: {
  tours: GuideTourOption[];
  onStartTour: (id: string) => void;
  onClose: () => void;
}) {
  return (
    <AppModalFooter className="justify-between gap-2 flex-wrap">
      <div className="flex flex-wrap items-center gap-2">
        {tours.map(t => (
          <AppPressable
            key={t.id}
            variant={t.id === "overview" ? "primary" : "outline"}
            onClick={() => onStartTour(t.id)}
            className="inline-flex items-center gap-1.5 px-3 py-2 text-xs"
          >
            <MousePointerClick size={14} />
            {t.label}
          </AppPressable>
        ))}
      </div>
      <AppPressable variant="outline" onClick={onClose} className="px-3 py-2 text-xs">
        Close
      </AppPressable>
    </AppModalFooter>
  );
}

export function GuideTourFooter({
  onStartTour,
  onClose,
  extraLeft,
  onStartPracticeTour,
  practiceLabel = "Start practice tour",
}: {
  onStartTour: () => void;
  onClose: () => void;
  extraLeft?: ReactNode;
  onStartPracticeTour?: () => void;
  practiceLabel?: string;
}) {
  return (
    <AppModalFooter className="justify-between gap-2 flex-wrap">
      <div className="flex flex-wrap items-center gap-2">
        {onStartPracticeTour ? (
          <AppPressable
            variant="primary"
            onClick={onStartPracticeTour}
            className="inline-flex items-center gap-1.5 px-3 py-2 text-xs"
          >
            <MousePointerClick size={14} />
            {practiceLabel}
          </AppPressable>
        ) : (
          <AppPressable
            variant="primary"
            onClick={onStartTour}
            className="inline-flex items-center gap-1.5 px-3 py-2 text-xs"
          >
            <MousePointerClick size={14} />
            Start tour
          </AppPressable>
        )}
        {onStartPracticeTour && (
          <AppPressable variant="outline" onClick={onStartTour} className="inline-flex items-center gap-1.5 px-3 py-2 text-xs">
            Quick overview
          </AppPressable>
        )}
        {extraLeft}
      </div>
      <AppPressable variant="outline" onClick={onClose} className="px-3 py-2 text-xs">
        Close
      </AppPressable>
    </AppModalFooter>
  );
}
