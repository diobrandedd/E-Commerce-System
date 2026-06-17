import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

type DeliveriesReviewTourContext = {
  isExecutive: boolean;
};

function editStepCopy(isExecutive: boolean): string {
  if (isExecutive) {
    return "Executives can edit tracking, photo, or delete a row directly. Changes apply immediately.";
  }
  return "Production managers submit an edit request here. An executive approves it under Inventory → Requests → Edit requests.";
}

function buildSteps({ isExecutive }: DeliveriesReviewTourContext): DriveStep[] {
  const steps: DriveStep[] = [
    {
      popover: {
        title: "Deliveries review",
        description:
          "This page is your audit trail after parcels are scanned on Add deliveries. Browse photos, check status, and fix mistakes when needed.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='deliveries-review-filters']",
      popover: {
        title: "Search and date range",
        description:
          "Filter by tracking number, customer name, or item text. Narrow by delivery date, then tap Search to refresh the table.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-review-table']",
      popover: {
        title: "Delivery records",
        description:
          "Each row is one scanned shipment: ID, date, tracking, packing photo, and parcel status (packing, shipped, cancelled, etc.).",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-review-photo']",
      popover: {
        title: "Packing photo",
        description:
          "Tap the thumbnail or View to open the full record — tracking, recipient, items, courier, and the photo taken at scan time.",
        side: "bottom",
        align: "center",
      },
    },
    {
      element: "[data-tour='deliveries-review-actions']",
      popover: {
        title: "Edit or correct",
        description: editStepCopy(isExecutive),
        side: "left",
        align: "center",
      },
    },
    {
      element: "[data-tour='deliveries-review-pagination']",
      popover: {
        title: "Browse pages",
        description:
          "Use Prev / Next when you have more than ten rows. The footer shows total count and whether edits need executive approval.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Related pages",
        description: isExecutive
          ? "Use Cancelled page when couriers void parcels after pickup — restock inventory there. Add deliveries is where production scans and ships new parcels."
          : "Use Cancelled page for voided parcels after pickup. Add deliveries is where you scan and ship. My requests tracks your pending edit approvals.",
        side: "over",
        align: "center",
      },
    },
  ];

  return steps;
}

export function runDeliveriesReviewTour(ctx: DeliveriesReviewTourContext) {
  return runPageTour({ steps: buildSteps(ctx) });
}
