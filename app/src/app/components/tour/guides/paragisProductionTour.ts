import type { DriveStep } from "driver.js";
import { PT_TEA_BAGS_PER_POUCH } from "@/app/lib/deptProducts";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(): DriveStep[] {
  return [
    {
      popover: {
        title: "Dried Paragis — leaf pool",
        description: `Two stages: stock raw leaf (kg) into the leaf pool → scan finished pouches (${PT_TEA_BAGS_PER_POUCH} tea bags each). No WIP tea-bag step.`,
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='raw-dept-metrics']",
      popover: {
        title: "Live counts",
        description:
          "Raw leaf (kg) in the pool, finished pouches on hand, and the linked costing stock name.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-testid='paragis-bag-pool']",
      popover: {
        title: "Leaf pool progress",
        description:
          "Remaining leaf grams vs stocked capacity. Each pouch scan deducts the Settings BOM (default 48 g). Overdrawn is allowed with a warning.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='raw-dept-inline-actions']",
      popover: {
        title: "Do next",
        description:
          "Stock in raw leaf (kg) to fill the pool, then Request → Generate / print → Scan pouches. Scanning deducts leaf; packaging is not deducted.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='raw-dept-activity']",
      popover: {
        title: "Raw leaf entries",
        description: "Stock-in and leaf waste history per supplier lot.",
        side: "top",
        align: "start",
      },
    },
  ];
}

export function runParagisProductionTour() {
  return runPageTour({ steps: buildSteps() });
}
