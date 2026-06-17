import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

function buildSteps(): DriveStep[] {
  return [
    {
      popover: {
        title: "Product Costing",
        description:
          "Build formulas, track live manufacturing cost, and see margin when supplier or packaging prices change. Executive-only page.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='costing-view-toggle']",
      popover: {
        title: "Product COGS vs Parcel packaging",
        description:
          "Product COGS — per-SKU formula builder. Parcel packaging — fulfillment add-ons (bubble wrap, courier pouch) and landed cost by product family.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='costing-products-header']",
      popover: {
        title: "Products panel",
        description:
          "Honey variants, Paragis, BRC, Jar Cacao, and custom products. Expand a row to edit formula lines, selling price, and see baseline vs current cost.",
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='costing-products-list']",
      popover: {
        title: "Formula lines",
        description:
          "Each line is qty × stock price. Dept lines auto-suggest from yield. Add Custom cost for labor or shipping. Margin updates when stock prices move.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "End-to-end",
        description:
          "Set supplier prices → stock in packaging → verify formulas → set selling price. On Parcel packaging tab, open Tier rules to preview landed cost for online orders.",
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runCostingTour() {
  return runPageTour({ steps: buildSteps() });
}
