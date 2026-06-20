import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(): DriveStep[] {
  return [
    {
      popover: {
        title: "Supplier Master",
        description:
          "Register vendors and set honey/dept prices that drive costing and raw-material stock-in.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='supplier-pending-banner']",
      popover: {
        title: "Pending honey prices",
        description:
          "When shown, warehouse deliveries still need ₱/gallon on a honey supplier — expand the row and save rates.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='supplier-toolbar']",
      popover: {
        title: "Search and add",
        description: "Filter suppliers by name or category. Add Supplier registers a new vendor.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='supplier-table']",
      popover: {
        title: "Supplier list",
        description:
          "Honey, paragis, black rice, and cacao rows expand for inline ₱ pricing. Other categories open a profile modal.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "After pricing",
        description:
          "Updated dept bag prices refresh costing formulas. Honey ₱/gallon flows to costing stock as ₱/liter automatically.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runSupplierMasterTour() {
  const steps = buildSteps();
  const hasPending = document.querySelector("[data-tour='supplier-pending-banner']");
  return runPageTour({
    steps: hasPending ? steps : steps.filter(s => s.element !== "[data-tour='supplier-pending-banner']"),
  });
}
