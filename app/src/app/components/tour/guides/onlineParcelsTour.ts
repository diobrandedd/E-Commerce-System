import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(role: Role | undefined): DriveStep[] {
  const isCsr = role === "csr_tl" || role === "csr_staff";
  const isPm = role === "prod_manager";

  return [
    {
      popover: {
        title: "Online parcels",
        description: "Search and audit parcels from finalized CSR batches — before and after warehouse fulfillment.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='online-parcels-filters']",
      popover: {
        title: "Search and filter",
        description:
          "Enter tracking or recipient, tap search. Filter by packing, shipped, cancelled, or returned. Refresh reloads the list.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='online-parcels-table']",
      popover: {
        title: "Parcel list",
        description: isCsr
          ? "Click a row for customer service detail — items, order no., pipeline source."
          : isPm
            ? "Confirm packing status before scan on Add deliveries. Shipped means inventory already deducted."
            : "Click any row for full parcel detail and status history.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='online-parcels-status-col']",
      popover: {
        title: "Status column",
        description: "packing → ready to scan · shipped → left warehouse · cancelled/returned → exceptions",
        side: "left",
        align: "center",
      },
    },
    {
      popover: {
        title: "Related workflows",
        description: isPm
          ? "Scan and photo on Add deliveries. Fix mistakes on Deliveries review. Restock voided parcels on Cancelled."
          : "Production scans on Deliveries. CSR answers tracking questions here.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runOnlineParcelsTour(role: Role | undefined) {
  return runPageTour({ steps: buildSteps(role) });
}
