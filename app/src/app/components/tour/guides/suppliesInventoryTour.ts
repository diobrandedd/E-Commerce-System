import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";

function detailStepCopy(role: Role | undefined): string {
  if (role === "hr") {
    return "Stock in lots when supplies arrive. Use Issue request (header Actions) to submit staff supply issues for executive approval.";
  }
  if (role === "executive") {
    return "Stock in, set on-hand, review activity, and add catalog items. Approve HR issue requests on the Requests page.";
  }
  if (role === "prod_manager") {
    return "View on-hand and unit cost. Ask HR to stock in or submit issue requests for floor staff.";
  }
  return "Click a row for details and your role actions.";
}

function buildSteps(role: Role | undefined): DriveStep[] {
  return [
    {
      popover: {
        title: "Supplies inventory",
        description:
          "Org-wide consumables tracked per piece with weighted-average cost from purchase lots — gloves, cleaning, office supplies, etc.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='supplies-stats']",
      popover: {
        title: "Summary cards",
        description: "Catalog size, how many SKUs have stock, and total pieces on hand across all items.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='supplies-filters']",
      popover: {
        title: "Search and filters",
        description: "Search by item name. Filter to in-stock or out-of-stock SKUs.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='supplies-catalog']",
      popover: {
        title: "Catalog list",
        description: detailStepCopy(role),
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Issue requests",
        description:
          role === "hr" || role === "executive"
            ? "HR submits supply issues from the header Actions menu. Executives approve them under Inventory → Requests → Supply issue requests."
            : "Supply issues are submitted by HR and approved by executives on the Requests page.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runSuppliesInventoryTour(role: Role | undefined) {
  return runPageTour({ steps: buildSteps(role) });
}
