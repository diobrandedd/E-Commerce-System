import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";

function detailStepCopy(role: Role | undefined): string {
  if (role === "hr") {
    return "Open any row for Stock in lot — enter total ₱ paid and quantity received when packaging arrives.";
  }
  if (role === "prod_manager") {
    return "Open a row to Deduct for use when materials go to the production floor.";
  }
  if (role === "executive") {
    return "Open a row for stock-in, deduct, set on-hand, pool yield, and the full activity log.";
  }
  return "Click a row to open the detail modal and see role-specific actions.";
}

function buildSteps(role: Role | undefined): DriveStep[] {
  return [
    {
      popover: {
        title: "Packaging Materials",
        description:
          "Track pouches, bottles, stickers, and pool rolls (yarn, foil, seals). HR stocks in purchases; production deducts for use; executives have full control.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='packaging-stats']",
      popover: {
        title: "Summary cards",
        description:
          "Catalog count, in-stock SKUs, and out-of-stock alerts. Per-unit items block production when empty; pool rolls are cost-only for costing.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='packaging-filters']",
      popover: {
        title: "Search and filters",
        description: "Find items by name. Filter All, Per-unit, Pool, or Out of stock to focus the list.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='packaging-catalog']",
      popover: {
        title: "Catalog list",
        description: detailStepCopy(role),
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Per-unit vs pool",
        description:
          "Per-unit (pouches, bottles) is counted piece-by-piece. Pool (rolls) is tracked in whole rolls — deduct a roll when opened. Both types are in this same list.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runPackagingInventoryTour(role: Role | undefined) {
  return runPageTour({ steps: buildSteps(role) });
}
