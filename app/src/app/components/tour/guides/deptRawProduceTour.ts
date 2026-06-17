import type { DriveStep } from "driver.js";
import type { DeptProductKey } from "@/app/lib/deptProducts";
import { brcPouchesPerBag, jarCacaoJarsPerBag } from "@/app/lib/deptProducts";
import { runPageTour } from "@/app/components/tour/runPageTour";

type DeptRawTourKey = Extract<DeptProductKey, "brc" | "jar_cacao">;

function buildSteps(productKey: DeptRawTourKey): DriveStep[] {
  const sellUnit = productKey === "brc" ? "pouch" : "jar";
  const yieldExample =
    productKey === "brc"
      ? `about ${brcPouchesPerBag()} pouches per full bag at yield.`
      : `about ${jarCacaoJarsPerBag().toFixed(2)} jars per full bag at yield.`;

  return [
    {
      popover: {
        title: productKey === "brc" ? "Black Rice Coffee — raw" : "Jar Cacao — raw",
        description: `Two stages: sealed raw bags → finished ${sellUnit}s. No WIP step — Produce goes straight from raw to retail units.`,
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
        description: "Log of Produce actions with quantities and notes.",
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
        description: `Stock in supplier bags, then Produce ${sellUnit}s (${yieldExample}). Produce preview shows formula deduct lines before you confirm. Bag waste removes sealed bags never opened.`,
        side: "over",
        align: "center",
      },
    },
  ];
}

export function runDeptRawProduceTour(productKey: DeptRawTourKey) {
  return runPageTour({ steps: buildSteps(productKey) });
}
