import type { DriveStep } from "driver.js";
import { PT_TEA_BAGS_PER_POUCH, ptTeaBagsPerBag } from "@/app/lib/deptProducts";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(): DriveStep[] {
  const teaPerBag = ptTeaBagsPerBag();
  return [
    {
      popover: {
        title: "Dried Paragis — raw production",
        description: `Three stages: raw bags → tea bags (WIP) → finished pouches (${PT_TEA_BAGS_PER_POUCH} tea bags each).`,
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='raw-dept-metrics']",
      popover: {
        title: "Live counts",
        description:
          "Raw bags (sealed 1 kg), WIP tea bags, finished pouches, and the linked costing stock name. These update after each action.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='raw-dept-production-log']",
      popover: {
        title: "Production activity",
        description:
          "Audit log of Use raw bags, Pouch, and waste actions — who did what and when.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='raw-dept-activity']",
      popover: {
        title: "Raw bag entries",
        description: "Stock-in and bag waste history per supplier lot.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Header actions",
        description: `Workflow: Stock in → Use raw bags (~${teaPerBag.toLocaleString()} tea bags per bag opened) → Pouch. On mobile, tap Actions for the same buttons. WIP waste and Bag waste correct mistakes.`,
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runParagisProductionTour() {
  return runPageTour({ steps: buildSteps() });
}
