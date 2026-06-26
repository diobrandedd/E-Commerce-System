import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

type DeliveriesReviewTourContext = {
  isExecutive: boolean;
};

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
      element: "[data-tour='deliveries-review-row']",
      popover: {
        title: "Row details",
        description:
          "Click any row to open the full record — tracking, recipient, items, courier, status, and the packing photo. Edit or delete from inside that panel.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-review-photo']",
      popover: {
        title: "Photo indicator",
        description:
          "Yes means a packing photo is on file. Open the row to view the full image — thumbnails are not loaded in the table for speed.",
        side: "bottom",
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
          ? "Use Cancelled when couriers void orders — inventory stays packed; re-waybill on Add deliveries. Add deliveries is where production scans and ships new parcels."
          : "Use Cancelled for courier voids, then re-waybill on Add deliveries. Add deliveries is where you scan and ship. My requests tracks your pending edit approvals.",
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
