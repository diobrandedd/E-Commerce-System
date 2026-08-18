import type { DriveStep } from "driver.js";
import { runPageTour } from "@/app/components/tour/runPageTour";

export function runPosCategoryTour() {
  return runPageTour({
    steps: [
      {
        popover: {
          title: "Pyx POS",
          description: "Walk-in sales terminal — pick a product family to start a sale.",
          side: "over",
          align: "center",
        },
      },
      {
        element: "[data-tour='pos-categories']",
        popover: {
          title: "Product categories",
          description: "Tap Honey, Paragis Tea, Black Rice Coffee, or Jar Cacao to open that catalog.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "[data-tour='pos-header-exit']",
        popover: {
          title: "Exit POS",
          description: "PIN cashiers log out to the login screen. Staff accounts return to the dashboard.",
          side: "bottom",
          align: "end",
        },
      },
    ],
  });
}

export function runPosProductTour(mode: "product" | "unified" = "product") {
  const steps: DriveStep[] = [
    {
      popover: {
        title: mode === "unified" ? "Unified POS" : "Product sale screen",
        description:
          mode === "unified"
            ? "Scan stickers for any product, enter prices, then checkout."
            : "Add items to cart, then checkout for a printed receipt.",
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='pos-cart']",
      popover: {
        title: "Cart",
        description:
          mode === "unified"
            ? "Each scanned sticker appears here — enter price per line and remove with trash if needed."
            : "Lines appear here as you add products. Remove mistakes with the trash icon.",
        side: "bottom",
        align: "start",
      },
    },
  ];

  if (mode === "product") {
    steps.push({
      element: "[data-tour='pos-product-grid']",
      popover: {
        title: "Products",
        description:
          "Tap a variant or product tile. Pick size and qty (honey) or qty only (dept). Stock limits block overselling.",
        side: "top",
        align: "center",
      },
    });
  }

  steps.push(
    {
      element: "[data-tour='pos-checkout-bar']",
      popover: {
        title: "Checkout",
        description: "Opens buyer details. Pay full price or apply 10%/20% off immediately — no approval wait.",
        side: "top",
        align: "center",
      },
    },
    {
      element: "[data-tour='pos-header-exit']",
      popover: {
        title: "Exit POS",
        description: "PIN cashiers log out. Staff accounts return to the dashboard.",
        side: "bottom",
        align: "end",
      },
    },
  );

  return runPageTour({ steps });
}
