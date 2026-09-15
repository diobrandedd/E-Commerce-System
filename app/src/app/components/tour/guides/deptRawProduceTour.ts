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
      ? `about ${brcPouchesPerBag()} pouches per kg at yield.`
      : `about ${jarCacaoJarsPerBag().toFixed(2)} jars per kg at yield.`;

  const usesPouchBarcodes = deptPouchBarcodesEnabled(productKey);
  const bagPool = productKey === "brc" || productKey === "jar_cacao";
  const defaultGrams = productKey === "brc" ? 100 : 220;
  const poolTestId = productKey === "brc" ? "brc-bag-pool" : "cacao-bag-pool";

  return [
    {
      popover: {
        title: productKey === "brc" ? "Black Rice Coffee — leaf pool" : "Jar Cacao — leaf pool",
        description: bagPool
          ? `Stock raw leaf (kg) into the leaf pool → scan finished ${sellUnit}s. Each scan deducts Settings BOM grams (default ${defaultGrams} g). ${yieldExample}`
          : usesPouchBarcodes
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
        description: bagPool
          ? `Raw leaf (kg) in the pool, finished ${sellUnit}s on hand, and the linked costing stock name.`
          : `Raw bags on hand, finished ${sellUnit}s ready to sell, and costing stock link.`,
        side: "bottom",
        align: "start",
      },
    },
    ...(bagPool
      ? [
          {
            element: `[data-testid='${poolTestId}']`,
            popover: {
              title: "Leaf pool progress",
              description: `Remaining leaf grams vs stocked capacity. Each ${sellUnit} scan deducts the Settings BOM. Overdrawn is allowed with a warning.`,
              side: "bottom" as const,
              align: "start" as const,
            },
          },
        ]
      : []),
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
        title: "Raw leaf entries",
        description: "Stock-in from suppliers and leaf waste corrections.",
        side: "top",
        align: "start",
      },
    },
    {
      popover: {
        title: bagPool ? "Do next" : "Header actions",
        description: bagPool
          ? `Stock in raw leaf (kg) to fill the pool, then Request → Generate / print → Scan ${sellUnit}s. Scanning deducts leaf; packaging is not deducted.`
          : usesPouchBarcodes
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
