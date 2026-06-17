import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(role: Role | undefined): DriveStep[] {
  const steps: DriveStep[] = [
    {
      popover: {
        title: "Online Orders",
        description:
          "CSR batch hub: prep partner Excel, stamp PDFs, finalize master file, and queue parcels for packing.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='online-orders-batch-bar']",
      popover: {
        title: "Active batch",
        description:
          "Shows batch ID, line count, and runs. Cancel batch only if abandoning the session. Start a new batch from the modal when none is active.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='online-orders-tools']",
      popover: {
        title: "Tool picker",
        description:
          "VIP, SPX, and Excel validate download prep sheets. Return here, then choose PDF + Excel stamper when ready to merge waybills.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Finish the batch",
        description:
          role === "executive"
            ? "After stamping, use Done — download master Excel to promote parcels and get the packer print file."
            : "After stamping, tap Done — download master Excel. Parcels move to packing for production to scan on Deliveries.",
        side: "over",
        align: "center",
      },
    },
  ];

  return steps;
}

export function runOnlineOrdersTour(role: Role | undefined) {
  let steps = buildSteps(role);
  if (!document.querySelector("[data-tour='online-orders-batch-bar']")) {
    steps = steps.filter(s => s.element !== "[data-tour='online-orders-batch-bar']");
  }
  if (!document.querySelector("[data-tour='online-orders-tools']")) {
    steps = steps.filter(s => s.element !== "[data-tour='online-orders-tools']");
  }
  return runPageTour({ steps });
}
