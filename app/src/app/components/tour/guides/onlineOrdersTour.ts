import type { DriveStep } from "driver.js";
import type { Role } from "@/app/context/AuthContext";
import { runPageTour } from "@/app/components/tour/runPageTour";
import { filterTourSteps, tourIntro, tourSpotlight } from "@/app/components/tour/tourStepHelpers";

export type OnlineOrdersTourId = "overview" | "vip" | "spx" | "excel" | "stamper";

function finalizeSteps(role: Role | undefined, steps: DriveStep[]) {
  return runPageTour({ steps: filterTourSteps(steps) });
}

function overviewSteps(role: Role | undefined): DriveStep[] {
  return [
    tourIntro(
      "Online Orders — overview",
      "CSR batch hub: start one batch, prep Excel with the right tool, stamp waybills, then download master Excel for packers.",
    ),
    tourSpotlight("[data-tour='online-orders-batch-bar']", {
      title: "Active batch",
      description:
        "Batch ID, line count, and stamp runs live here. Cancel batch only if abandoning the whole session.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tools']", {
      title: "Four tools — different jobs",
      description:
        "VIP, SPX, and Validate build or check Excel (download only). Stamper merges Excel + PDF labels into this batch. Pick the tool that matches your courier file.",
      side: "top",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tool-vip']", {
      title: "VIP Excel arranger",
      description: "J&T / VIP courier workbook → resolve ambiguous remarks → download arranged iPYX sheet.",
      side: "right",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tool-spx']", {
      title: "SPX name merge",
      description: "Shopee waybill PDFs (or packing list + PDFs) → pick variants → download iPYX sheet.",
      side: "left",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tool-excel']", {
      title: "Validate existing Excel",
      description: "Already have a partner iPYX workbook? Upload to validate columns and row count.",
      side: "right",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tool-stamper']", {
      title: "PDF + Excel stamper",
      description:
        "After prep: upload prepared Excel + matching waybill PDFs → stamp into batch. Repeat for more runs, then finish batch.",
      side: "left",
      align: "start",
    }),
    tourIntro(
      "Per-tool tours",
      "Open Guide again and start the VIP, SPX, Validate, or Stamper tour for field-by-field walkthrough inside that tool.",
    ),
    tourIntro(
      "Finish the batch",
      role === "executive"
        ? "After stamping, use Done — download master Excel to promote parcels to packing and get the packer print file."
        : "After stamping, tap Done — download master Excel. Parcels move to packing for production to scan on Deliveries.",
    ),
  ];
}

function vipSteps(): DriveStep[] {
  return [
    tourIntro(
      "VIP Excel arranger",
      "For J&T / VIP courier exports. Upload their Excel, fix ambiguous item lines, then download arranged iPYX — stamping is a separate step.",
    ),
    tourSpotlight("[data-tour='online-orders-vip-upload']", {
      title: "Step 1 — Upload courier Excel",
      description: "Drop the VIP/J&T workbook (.xlsx). The system parses remarks and flags orders that need variant picks.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-vip-resolve']", {
      title: "Step 2 — Resolve ambiguous orders",
      description:
        "If this panel appears, swipe through cards and pick the correct variant for each remark. Skip this step if no ambiguous rows.",
      side: "top",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-vip-build']", {
      title: "Step 3 — Build arranged Excel",
      description:
        "When all remarks are resolved, tap here. Downloads remarks export + builds the iPYX sheet your stamper needs.",
      side: "top",
      align: "center",
    }),
    tourSpotlight("[data-tour='online-orders-vip-done']", {
      title: "Step 4 — Back to tools",
      description:
        "After download, return to the tool picker. Open PDF + Excel stamper when waybill PDFs are ready — prep does not stamp automatically.",
      side: "top",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tool-back']", {
      title: "Back anytime",
      description: "Use Back to tools if you opened the wrong prep flow.",
      side: "bottom",
      align: "start",
    }),
  ];
}

function spxSteps(): DriveStep[] {
  return [
    tourIntro(
      "SPX name merge",
      "For Shopee SPX waybills. Choose PDF-only or packing-list mode, extract lines, confirm variants, review, then download iPYX.",
    ),
    tourSpotlight("[data-tour='online-orders-spx-mode']", {
      title: "Step 1 — Import mode",
      description:
        "PDF only: labels alone. Packing list + PDF: upload Shopee packing list Excel plus PDFs for customer names.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-spx-upload']", {
      title: "Step 2 — Upload files",
      description: "Add packing list (if that mode) and waybill PDFs, then run Extract or Import.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-spx-extract']", {
      title: "Step 3 — Extract / import",
      description: "Parses PDFs into rows. Next you confirm variant picks per line.",
      side: "top",
      align: "center",
    }),
    tourSpotlight("[data-tour='online-orders-spx-resolve']", {
      title: "Step 4 — Variant picks",
      description: "Confirm each line's item text. When all lines are ready, continue to review.",
      side: "top",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-spx-review']", {
      title: "Step 5 — Review all items",
      description: "Scan the full list; edit any row if packers would misread an item line.",
      side: "top",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-spx-build']", {
      title: "Step 6 — Build iPYX Excel",
      description: "Creates the download sheet. Verify tracking numbers before stamping.",
      side: "top",
      align: "center",
    }),
    tourSpotlight("[data-tour='online-orders-tool-back']", {
      title: "Then stamp separately",
      description: "Download, go Back to tools, open PDF + Excel stamper with matching PDFs.",
      side: "bottom",
      align: "start",
    }),
  ];
}

function excelSteps(): DriveStep[] {
  return [
    tourIntro(
      "Validate existing Excel",
      "Use when CSR or partner already built the iPYX workbook. Validates layout and row count — does not stamp.",
    ),
    tourSpotlight("[data-tour='online-orders-excel-upload']", {
      title: "Step 1 — Upload iPYX sheet(s)",
      description: "One or more partner workbooks in the expected column layout.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-excel-validate']", {
      title: "Step 2 — Validate Excel",
      description: "Checks columns and counts rows. Fix the file offline if validation fails.",
      side: "top",
      align: "center",
    }),
    tourSpotlight("[data-tour='online-orders-excel-result']", {
      title: "Step 3 — Download or return",
      description: "Green panel shows row count. Download a copy if needed, then Back to tools for stamper.",
      side: "top",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-tool-back']", {
      title: "Back to tools",
      description: "Prep tools never stamp — always return here for PDF + Excel stamper.",
      side: "bottom",
      align: "start",
    }),
  ];
}

function stamperSteps(): DriveStep[] {
  return [
    tourIntro(
      "PDF + Excel stamper",
      "Merges prepared iPYX Excel with waybill PDFs into the active batch. Run once per PDF set; finish batch when all parcels are stamped.",
    ),
    tourSpotlight("[data-tour='online-orders-stamper-pipeline']", {
      title: "Step 1 — Order type",
      description: "Partner, VIP, or Shopee SPX — must match how the Excel was built.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-stamper-excel-upload']", {
      title: "Step 2 — Prepared Excel",
      description: "Upload the iPYX sheet from VIP, SPX, or validate tool.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-stamper-excel-load']", {
      title: "Step 3 — Load Excel",
      description: "Registers rows into memory. Download preview to double-check before stamping.",
      side: "top",
      align: "center",
    }),
    tourSpotlight("[data-tour='online-orders-stamper-pdf-upload']", {
      title: "Step 4 — Waybill PDFs",
      description: "Courier label PDFs that match the Excel tracking numbers.",
      side: "bottom",
      align: "start",
    }),
    tourSpotlight("[data-tour='online-orders-stamper-run']", {
      title: "Step 5 — Stamp & save",
      description: "Writes labels into the batch. Resolve duplicate-tracking prompts if shown.",
      side: "top",
      align: "center",
    }),
    tourIntro(
      "After stamping",
      "Download stamped PDF from the result screen. Process another run or finish batch for master Excel + packing promotion.",
    ),
  ];
}

export function runOnlineOrdersTour(id: OnlineOrdersTourId, role?: Role) {
  switch (id) {
    case "vip":
      return finalizeSteps(role, vipSteps());
    case "spx":
      return finalizeSteps(role, spxSteps());
    case "excel":
      return finalizeSteps(role, excelSteps());
    case "stamper":
      return finalizeSteps(role, stamperSteps());
    case "overview":
    default:
      return finalizeSteps(role, overviewSteps(role));
  }
}
