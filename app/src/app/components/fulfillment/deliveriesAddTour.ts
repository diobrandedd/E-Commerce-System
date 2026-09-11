import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

type DeliveriesAddTourContext = {
  isExecutive: boolean;
};

function buildSteps({ isExecutive }: DeliveriesAddTourContext): DriveStep[] {
  return [
    {
      popover: {
        title: "Add deliveries",
        description:
          "Scan tracking and save a before-shipment photo for RTS. No CSR list and no inventory deduct. On hold and re-waybill handle sealed-box exceptions.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='deliveries-add-tabs']",
      popover: {
        title: "Photo · On hold · Re-waybill",
        description:
          "Photo is the daily scan-and-document flow. On hold marks wrong labels. Re-waybill links old tracking to a new CSR waybill.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-add-date']",
      popover: {
        title: "Business date",
        description: "Photos group under this date. Use today unless you are catching up a prior batch.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-add-ship-scan']",
      popover: {
        title: "Scan and photograph",
        description:
          "Tap Scan tracking, photograph the parcel, then Done or Scan next. Stock and packaging never change here.",
        side: "top",
        align: "center",
      },
    },
    {
      popover: {
        title: "On hold & re-waybill",
        description:
          "Switch tabs for sealed-box exceptions. Re-waybill: old QR → new waybill → photo.",
        side: "over",
        align: "center",
      },
    },
    {
      popover: {
        title: "Related pages",
        description: isExecutive
          ? "Courier cancel scans go on the Cancelled page. Deliveries review shows every photo for audit. RTS uses the photo by tracking."
          : "Scan courier cancels on Cancelled, then re-waybill here. Deliveries review is your photo audit trail.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runDeliveriesAddTour(ctx: DeliveriesAddTourContext) {
  return runPageTour({ steps: buildSteps(ctx) });
}
