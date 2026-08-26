import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(): DriveStep[] {
  return [
    {
      popover: {
        title: "Cancelled restock",
        description:
          "Return packed, shipped, or POS walk-in sold stickers to packaging, finished, or RTS stock (by original pool). Sealed-box re-waybill stays on Hold.",
        side: "over",
        align: "center",
      },
    },
    {
      popover: {
        title: "Restock vs History",
        description: "Restock is the live scan session. History lists confirmed returns so you can see how many came back.",
        side: "over",
        align: "center",
      },
    },
    {
      popover: {
        title: "Next step",
        description:
          "Opened customer returns with tracking still go through RTS Record. Wrong label without unpacking? Use On hold on Add deliveries.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runDeliveriesCancelledTour(_ctx?: { isExecutive?: boolean }) {
  runPageTour({ steps: buildSteps() });
}
