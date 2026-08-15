import type { DriveStep } from "driver.js";
import type { DeptProductKey } from "@/app/lib/deptProducts";
import { brcPouchesPerBag, jarCacaoJarsPerBag } from "@/app/lib/deptProducts";
import { deptPouchBarcodesEnabled } from "@/app/lib/deptPouchBarcodes";
import { runPageTour } from "@/app/components/tour/runPageTour";

type DeptRawTourKey = Extract<DeptProductKey, "brc" | "jar_cacao">;

function buildSteps(productKey: DeptRawTourKey): DriveStep[] {
  const sellUnit = productKey === "brc" ? "pouch" : "jar";
  const yieldExample =
    productKey === "brc"
      ? `about ${brcPouchesPerBag()} pouches per full bag at yield.`
      : `about ${jarCacaoJarsPerBag().toFixed(2)} jars per full bag at yield.`;

  const usesPouchBarcodes = deptPouchBarcodesEnabled(productKey);

  return [
    {
      popover: {
        title: productKey === "brc" ? "Black Rice Coffee — raw" : "Jar Cacao — raw",
        description: usesPouchBarcodes
          ? `Two stages: sealed raw bags → finished ${sellUnit}s. Finished stock comes from scanning a barcode sticker on each ${sellUnit}.`
          : `Two stages: sealed raw bags → finished ${sellUnit}s. No WIP step — Produce goes straight from raw to retail units.`,
        side: "over",
        align: "center",
      },
    },
    {
      element: "[data-tour='raw-dept-metrics']",
      popover: {
        title: "Live counts",
        description: `Raw bags on hand, finished ${sellUnit}s ready to sell, and costing stock link.`,
        side: "bottom",
        align: "start",
      },
    },
    {
      element: "[data-tour='raw-dept-production-log']",
      popover: {
        title: "Production activity",
        description: usesPouchBarcodes
          ? "Historical log of Produce actions. New finished stock is recorded by scanning stickers instead."
          : "Log of Produce actions with quantities and notes.",
        side: "top",
        align: "start",
      },
    },
    {
      element: "[data-tour='raw-dept-activity']",
      popover: {
        title: "Raw bag entries",
        description: "Stock-in from suppliers and bag waste corrections.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: "Header actions",
        description: usesPouchBarcodes
          ? `Stock in supplier bags (${yieldExample}), request barcodes for the batch, print the A4 sticker sheet, then scan each sticker to add finished ${sellUnit}s. Bag waste removes sealed bags never opened.`
          : `Stock in supplier bags, then Produce ${sellUnit}s (${yieldExample}). Produce preview shows formula deduct lines before you confirm. Bag waste removes sealed bags never opened.`,
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runDeptRawProduceTour(productKey: DeptRawTourKey) {
  return runPageTour({ steps: buildSteps(productKey) });
}
