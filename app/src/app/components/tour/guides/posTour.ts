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
          description: "Logs out the cashier and returns to the main login screen when your shift ends.",
          side: "bottom",
          align: "end",
        },
      },
    ],
  });
}

export function runPosProductTour() {
  return runPageTour({
    steps: [
      {
        popover: {
          title: "Product sale screen",
          description: "Add items to cart, then checkout for a printed receipt.",
          side: "over",
          align: "center",
        },
      },
      {
        element: "[data-tour='pos-cart']",
        popover: {
          title: "Cart",
          description: "Lines appear here as you add products. Remove mistakes with the trash icon.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "[data-tour='pos-product-grid']",
        popover: {
          title: "Products",
          description:
            "Tap a variant or product tile. Pick size and qty (honey) or qty only (dept). Stock limits block overselling.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "[data-tour='pos-checkout-bar']",
        popover: {
          title: "Checkout",
          description: "Opens buyer details and payment confirmation. Receipt prints after successful checkout.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "[data-tour='pos-header-back']",
        popover: {
          title: "Back",
          description: "Return to category picker to sell a different product line.",
          side: "bottom",
          align: "start",
        },
      },
    ],
  });
}
