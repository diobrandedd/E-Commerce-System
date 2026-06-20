import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

type DeliveriesCancelledTourContext = {
  isExecutive: boolean;
};

function buildSteps({ isExecutive }: DeliveriesCancelledTourContext): DriveStep[] {
  return [
    {
      popover: {
        title: "Deliveries cancelled",
        description:
          "Record courier voids when the order is dead at the courier but the box is still sealed — ready for re-waybill later.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='deliveries-cancel-scanner']",
      popover: {
        title: "Cancel parcel scanner",
        description:
          "Scan or type tracking after courier cancel feedback. Inventory stays deducted — the packed box is reused on Add deliveries → Re-waybill.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-cancel-filters']",
      popover: {
        title: "Search the log",
        description: "Filter cancelled events by tracking, customer name, items text, or date range.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-cancel-table']",
      popover: {
        title: "Cancellation audit",
        description:
          "Each row shows when the cancel happened, previous status, who scanned it, and order details. Box column reminds you to re-waybill.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-cancel-pagination']",
      popover: {
        title: "Browse pages",
        description: "Use Prev / Next when more than ten cancellations match your filters.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Next step",
        description: isExecutive
          ? "After cancel: CSR prints a new waybill if needed → Add deliveries → Re-waybill tab → photo → ship. Review batches on Deliveries review."
          : "After cancel: get a replacement waybill from CSR → Add deliveries → Re-waybill → ship. Wrong label only (no courier cancel)? Use On hold instead.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runDeliveriesCancelledTour(ctx: DeliveriesCancelledTourContext) {
  return runPageTour({ steps: buildSteps(ctx) });
}
