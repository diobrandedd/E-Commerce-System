import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";
import { filterTourSteps, tourClickToContinue, tourIntro } from "@/app/components/tour/tourStepHelpers";
import type { OnlineOrdersPracticeStage } from "@/app/components/csr/onlineOrders/onlineOrdersPractice";

export type OnlineOrdersPracticeContext = {
  role?: Role;
  setStage: (stage: OnlineOrdersPracticeStage) => void;
  openTool: (tool: "vip" | "spx" | "excel" | "stamper") => void;
  backToTools: () => void;
  onComplete?: () => void;
};

function buildPracticeSteps(ctx: OnlineOrdersPracticeContext): DriveStep[] {
  const { role, setStage, openTool, backToTools } = ctx;
  const finishCopy =
    role === "executive"
      ? "In a real batch, tap Done to download master Excel and promote parcels to packing."
      : "In a real batch, tap Done to download master Excel for packers. Parcels move to packing on Deliveries.";

  return [
    tourIntro(
      "Practice mode — full CSR workflow",
      "Nothing is saved: no batch, no uploads, no stamping. Click each highlighted control to walk through VIP → SPX → Validate → Stamper.",
    ),
    tourClickToContinue(
      "[data-tour='online-orders-batch-bar']",
      {
        title: "Step 1 — Active batch",
        description: "Every session starts one batch. Line count and stamp runs appear here.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("tools") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-tool-vip']",
      {
        title: "Step 2 — VIP Excel arranger",
        description: "Open the VIP tool for J&T / VIP courier workbooks.",
        side: "right",
        align: "start",
      },
      {
        onAdvance: () => {
          openTool("vip");
          setStage("vip-upload");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-vip-upload']",
      {
        title: "Step 3 — Upload courier Excel",
        description: "Drop the partner workbook (.xlsx). Ambiguous remarks need variant picks next.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("vip-resolve") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-vip-resolve']",
      {
        title: "Step 4 — Resolve ambiguous orders",
        description: "Swipe cards and pick the correct variant for each remark.",
        side: "top",
        align: "start",
      },
      { onAdvance: () => setStage("vip-build") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-vip-build']",
      {
        title: "Step 5 — Build arranged Excel",
        description: "Downloads remarks export + iPYX sheet for stamping later.",
        side: "top",
        align: "center",
      },
      { onAdvance: () => setStage("vip-done") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-vip-done']",
      {
        title: "Step 6 — Back to tools",
        description: "Prep tools never stamp — return to the picker when Excel is ready.",
        side: "top",
        align: "start",
      },
      {
        onAdvance: () => {
          backToTools();
          setStage("tools");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-tool-spx']",
      {
        title: "Step 7 — SPX name merge",
        description: "Shopee waybills → review variants → download iPYX.",
        side: "left",
        align: "start",
      },
      {
        onAdvance: () => {
          openTool("spx");
          setStage("spx-mode");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-spx-mode']",
      {
        title: "Step 8 — Import mode",
        description: "PDF only, or packing list Excel + PDFs for customer names.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("spx-upload") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-spx-upload']",
      {
        title: "Step 9 — Upload files",
        description: "Add waybill PDFs (and packing list if that mode).",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("spx-extract") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-spx-extract']",
      {
        title: "Step 10 — Extract / import",
        description: "Parses PDFs into rows for variant confirmation.",
        side: "top",
        align: "center",
      },
      { onAdvance: () => setStage("spx-resolve") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-spx-resolve']",
      {
        title: "Step 11 — Variant picks",
        description: "Confirm each line's item text before review.",
        side: "top",
        align: "start",
      },
      { onAdvance: () => setStage("spx-review") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-spx-review']",
      {
        title: "Step 12 — Review all items",
        description: "Scan the full list; edit any row packers might misread.",
        side: "top",
        align: "start",
      },
      { onAdvance: () => setStage("spx-build") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-spx-build']",
      {
        title: "Step 13 — Build iPYX Excel",
        description: "Creates the download sheet — verify tracking numbers.",
        side: "top",
        align: "center",
      },
      {
        onAdvance: () => {
          backToTools();
          setStage("tools");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-tool-excel']",
      {
        title: "Step 14 — Validate existing Excel",
        description: "When CSR or partner already built the iPYX workbook.",
        side: "right",
        align: "start",
      },
      {
        onAdvance: () => {
          openTool("excel");
          setStage("excel-upload");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-excel-upload']",
      {
        title: "Step 15 — Upload iPYX sheet",
        description: "Partner column layout — one or more workbooks.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("excel-validate") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-excel-validate']",
      {
        title: "Step 16 — Validate Excel",
        description: "Checks columns and row count before stamping.",
        side: "top",
        align: "center",
      },
      { onAdvance: () => setStage("excel-result") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-excel-result']",
      {
        title: "Step 17 — Back to tools",
        description: "Validation complete — return for the stamper.",
        side: "top",
        align: "start",
      },
      {
        onAdvance: () => {
          backToTools();
          setStage("tools");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-tool-stamper']",
      {
        title: "Step 18 — PDF + Excel stamper",
        description: "Merges prepared Excel with waybill PDFs into the batch.",
        side: "left",
        align: "start",
      },
      {
        onAdvance: () => {
          openTool("stamper");
          setStage("stamper-pipeline");
        },
      },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-stamper-pipeline']",
      {
        title: "Step 19 — Order type",
        description: "Partner, VIP, or Shopee SPX — must match how Excel was built.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("stamper-excel") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-stamper-excel-upload']",
      {
        title: "Step 20 — Prepared Excel",
        description: "Upload the iPYX sheet from a prep tool.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("stamper-load") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-stamper-excel-load']",
      {
        title: "Step 21 — Load Excel",
        description: "Registers rows — download preview to double-check.",
        side: "top",
        align: "center",
      },
      { onAdvance: () => setStage("stamper-pdf") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-stamper-pdf-upload']",
      {
        title: "Step 22 — Waybill PDFs",
        description: "Courier labels matching Excel tracking numbers.",
        side: "bottom",
        align: "start",
      },
      { onAdvance: () => setStage("stamper-run") },
    ),
    tourClickToContinue(
      "[data-tour='online-orders-stamper-run']",
      {
        title: "Step 23 — Stamp & save",
        description: "Writes labels into the batch. Repeat for more PDF sets.",
        side: "top",
        align: "center",
      },
    ),
    tourIntro("Practice complete", finishCopy),
  ];
}

export function runOnlineOrdersPracticeTour(ctx: OnlineOrdersPracticeContext) {
  // Do not filter — targets appear as the user advances through tools.
  return runPageTour({
    steps: buildPracticeSteps(ctx),
    onComplete: ctx.onComplete,
  });
}

/** Spotlight-only overview for users who prefer Next/Back without practice fixtures. */
export function runOnlineOrdersOverviewTour(role?: Role, onComplete?: () => void) {
  const finishCopy =
    role === "executive"
      ? "After stamping, use Done — download master Excel to promote parcels to packing."
      : "After stamping, tap Done — download master Excel. Parcels move to packing for production.";

  return runPageTour({
    steps: filterTourSteps([
      tourIntro(
        "Online Orders — overview",
        "CSR batch hub: start one batch, prep Excel with the right tool, stamp waybills, then download master Excel.",
      ),
      {
        element: "[data-tour='online-orders-batch-bar']",
        popover: {
          title: "Active batch",
          description: "Batch ID, line count, and stamp runs live here.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "[data-tour='online-orders-tools']",
        popover: {
          title: "Four tools",
          description: "VIP, SPX, Validate build Excel. Stamper merges Excel + PDFs into this batch.",
          side: "top",
          align: "start",
        },
      },
      tourIntro("Finish the batch", finishCopy),
    ]),
    onComplete,
  });
}
