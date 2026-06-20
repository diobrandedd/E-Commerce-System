import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(role: Role | undefined): DriveStep[] {
  const steps: DriveStep[] = [
    {
      popover: {
        title: "Inventory Requests",
        description:
          "Central hub for corrections, supply issues, packaging count changes, delivery edits, and POS discount approvals — depending on your role.",
        side: "over",
        align: "center",
      },
    },
  ];

  if (role !== "hr") {
    steps.push({
      element: "#inventory-corrections-section",
      popover: {
        title: "Inventory corrections",
        description:
          role === "prod_manager"
            ? "Submit honey warehouse / production / packaging qty fixes with New request in the header. Executives approve here."
            : "Review and approve production manager correction requests for honey pipeline inventory.",
        side: "bottom",
        align: "start",
      },
    });
  }

  steps.push({
    element: "#supply-requests-section",
    popover: {
      title: "Supply issue requests",
      description:
        role === "hr"
          ? "Track supply issues you submitted — pending until an executive approves and stock deducts."
          : role === "executive"
            ? "Click a pending row to approve (deducts stock) or deny with a note to HR."
            : "HR submits supply issues here; executives approve. Read-only for production managers.",
      side: "bottom",
      align: "start",
    },
  });

  steps.push({
    element: "#packaging-requests-section",
    popover: {
      title: "Packaging count requests",
      description:
        role === "hr"
          ? "Submit packaging on-hand corrections when a physical count differs from the system."
          : "Review packaging on-hand adjustment requests from HR.",
      side: "bottom",
      align: "start",
    },
  });

  if (role === "executive" || role === "prod_manager") {
    steps.push({
      element: "#edit-requests-section",
      popover: {
        title: "Edit requests",
        description:
          role === "executive"
            ? "Approve delivery photo/tracking edits and other edit requests from production staff."
            : "Track your pending delivery edit and delete requests awaiting executive approval.",
        side: "bottom",
        align: "start",
      },
    });
  }

  steps.push({
    popover: {
      title: "Notifications",
      description:
        "Watch the bell icon in the header — it highlights when new requests need your review.",
      side: "over",
      align: "center",
    },
  });

  return steps;
}

export function runInventoryRequestsTour(role: Role | undefined) {
  return runPageTour({ steps: buildSteps(role) });
}
