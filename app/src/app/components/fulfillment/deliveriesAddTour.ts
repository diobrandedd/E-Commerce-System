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
          "Scan waybills, photograph parcels, and ship — or handle sealed-box exceptions (on hold and re-waybill).",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='deliveries-add-tabs']",
      popover: {
        title: "Ship · On hold · Re-waybill",
        description:
          "Ship is the daily scan-and-photo flow. On hold marks wrong labels without undoing inventory. Re-waybill links old tracking to a new CSR waybill and ships again.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-add-date']",
      popover: {
        title: "Business date",
        description:
          "Photos and ship records group under this date. Use today unless you are catching up a prior batch.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='deliveries-add-ship-scan']",
      popover: {
        title: "Scan and ship",
        description:
          "On the Ship tab: scan label → take parcel photo → Done or Scan next. Packaging staging and honey deduct when you ship.",
        side: "top",
        align: "center",
      },
    },
    {
      popover: {
        title: "On hold & re-waybill",
        description:
          "Switch to On hold or Re-waybill tabs for sealed-box exceptions. Re-waybill accepts on-hold and courier-cancelled parcels — old QR → new waybill → photo → ship.",
        side: "over",
        align: "center",
      },
    },
    {
      popover: {
        title: "Related pages",
        description: isExecutive
          ? "Courier cancel scans go on the Cancelled page (inventory stays packed). Deliveries review shows every photo for audit."
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
