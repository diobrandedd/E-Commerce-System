import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(): DriveStep[] {
  return [
    {
      popover: {
        title: "Staff management",
        description: "Executive-only hub for user accounts, roles, departments, and password resets.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='staff-stats']",
      popover: {
        title: "Summary cards",
        description: "Total users, active count, team leaders, and departments represented.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='staff-toolbar']",
      popover: {
        title: "Search and add",
        description: "Find staff by name, username, or employee ID. Add Staff opens the create form.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='staff-table']",
      popover: {
        title: "Staff list",
        description:
          "Edit role/dept, reset password to employee ID, or toggle active/inactive. Inactive users cannot log in.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='staff-actions-col']",
      popover: {
        title: "Row actions",
        description: "Edit pencil, password reset key, and active toggle — one row per person.",
        side: "left",
        align: "center",
      },
    },
  ];
}

export function runStaffManagementTour() {
  return runPageTour({ steps: buildSteps() });
}
