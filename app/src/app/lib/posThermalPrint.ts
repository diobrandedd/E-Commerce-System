import { peso, variantLabel, type HoneyCheckoutResult } from "@/app/lib/posHoney";

/**
 * ISO C8 width (57 mm) — matches 57.5 ± 0.5 mm thermal roll.
 * PDF page size is embedded so Chrome/Android do not default to A4.
 */
export const THERMAL_RECEIPT_PAGE_SIZE = "C8" as const;
export const THERMAL_RECEIPT_WIDTH_MM = 57;
export const THERMAL_RECEIPT_MIN_HEIGHT_MM = 81;
export const THERMAL_RECEIPT_FOOTER_TEXT = "This is not an Official Receipt";

const MARGIN_X_MM = 2;
const LINE_MM = 3.4;
const ITEM_LINE_MM = 4;
const PX_PER_MM = 96 / 25.4;
const RASTER_SCALE = 4;
const FOOTER_BOTTOM_MM = 2;

type ImageAsset = {
  dataUrl: string;
  width: number;
  height: number;
};

export type PosThermalReceipt = Pick<
  HoneyCheckoutResult,
  "receipt_no" | "total" | "buyer_name" | "seller_name" | "items" | "subtotal" | "discount_percent" | "discount_amount"
>;

type ReceiptPdf = {
  setFont: (face: string, style?: string) => void;
  setFontSize: (size: number) => void;
  text: (text: string, x: number, y: number, options?: { align?: "left" | "center" | "right" }) => void;
  splitTextToSize: (text: string, maxWidth: number) => string | string[];
  addImage: (imageData: string, format: string, x: number, y: number, w: number, h: number) => void;
  setLineDashPattern: (pattern: number[], phase: number) => void;
  setLineWidth: (width: number) => void;
  line: (x1: number, y1: number, x2: number, y2: number) => void;
  output: (type: "blob") => Blob;
};

type TextRaster = {
  dataUrl: string;
  widthMm: number;
  heightMm: number;
};

function itemLabelPdf(item: PosThermalReceipt["items"][number]): string {
  if (item.variant) {
    return `${variantLabel(item.variant)} · ${item.size} x${item.qty}`;
  }
  return `${item.name} x${item.qty}`;
}

function itemPriceText(item: PosThermalReceipt["items"][number]): string {
  if (item.line_subtotal != null && item.line_subtotal > item.line_total) {
    return `${peso(item.line_subtotal)} ${peso(item.line_total)}`;
  }
  return peso(item.line_total);
}

function estimateReceiptHeightMm(receipt: PosThermalReceipt): number {
  const hasDiscount = receipt.discount_percent != null && receipt.discount_percent > 0;
  const header = logoBlockMm() + 18;
  const buyer = 8;
  const items = receipt.items.length * ITEM_LINE_MM;
  const discount = hasDiscount ? 12 : 0;
  const totalBlock = 12;
  const footerGap = 8;
  const raw = header + buyer + items + discount + totalBlock + footerGap + FOOTER_BOTTOM_MM + 3;
  return Math.max(THERMAL_RECEIPT_MIN_HEIGHT_MM, Math.ceil(raw));
}

function logoBlockMm(): number {
  return 12;
}

async function loadImageAsset(url: string): Promise<ImageAsset | null> {
  try {
    const res = await fetch(url);
    if (!res.ok) return null;
    const blob = await res.blob();
    const dataUrl = await new Promise<string | null>(resolve => {
      const reader = new FileReader();
      reader.onload = () => resolve(typeof reader.result === "string" ? reader.result : null);
      reader.onerror = () => resolve(null);
      reader.readAsDataURL(blob);
    });
    if (!dataUrl) return null;

    const img = new Image();
    img.src = dataUrl;
    await img.decode();
    return { dataUrl, width: img.naturalWidth, height: img.naturalHeight };
  } catch {
    return null;
  }
}

function renderTextRaster(text: string, fontSizePt: number, bold = false): TextRaster {
  const fontSizePx = (fontSizePt * 96) / 72;
  const canvas = document.createElement("canvas");
  const ctx = canvas.getContext("2d");
  if (!ctx) {
    return { dataUrl: "", widthMm: 0, heightMm: 0 };
  }

  const family = '"Courier New", Courier, monospace';
  const font = `${bold ? "bold " : ""}${fontSizePx}px ${family}`;
  ctx.font = font;
  const metrics = ctx.measureText(text);
  const pad = 2;
  const logicalW = Math.max(1, Math.ceil(metrics.width) + pad * 2);
  const logicalH = Math.max(1, Math.ceil(fontSizePx * 1.2) + pad * 2);

  canvas.width = logicalW * RASTER_SCALE;
  canvas.height = logicalH * RASTER_SCALE;
  ctx.scale(RASTER_SCALE, RASTER_SCALE);
  ctx.font = font;
  ctx.fillStyle = "#000";
  ctx.textBaseline = "top";
  ctx.fillText(text, pad, pad);

  return {
    dataUrl: canvas.toDataURL("image/png"),
    widthMm: logicalW / PX_PER_MM,
    heightMm: logicalH / PX_PER_MM,
  };
}

function drawRasterRight(doc: ReceiptPdf, raster: TextRaster, rightX: number, baselineY: number): void {
  if (!raster.dataUrl) return;
  const topY = baselineY - raster.heightMm * 0.72;
  doc.addImage(raster.dataUrl, "PNG", rightX - raster.widthMm, topY, raster.widthMm, raster.heightMm);
}

async function rasterizeLogo(logo: ImageAsset, maxMm: number): Promise<TextRaster | null> {
  const aspect = logo.width / logo.height;
  let wMm = maxMm;
  let hMm = wMm / aspect;
  if (hMm > maxMm) {
    hMm = maxMm;
    wMm = hMm * aspect;
  }

  const img = new Image();
  img.src = logo.dataUrl;
  await img.decode();

  const wPx = Math.max(1, Math.round(wMm * PX_PER_MM * RASTER_SCALE));
  const hPx = Math.max(1, Math.round(hMm * PX_PER_MM * RASTER_SCALE));
  const canvas = document.createElement("canvas");
  canvas.width = wPx;
  canvas.height = hPx;
  const ctx = canvas.getContext("2d");
  if (!ctx) return null;
  ctx.drawImage(img, 0, 0, wPx, hPx);

  return {
    dataUrl: canvas.toDataURL("image/png"),
    widthMm: wMm,
    heightMm: hMm,
  };
}

function drawRule(doc: ReceiptPdf, y: number, width: number, dashed = true, gapAfter = 2.5): number {
  if (dashed) {
    doc.setLineDashPattern([0.6, 0.6], 0);
  }
  doc.setLineWidth(0.2);
  doc.line(MARGIN_X_MM, y, width - MARGIN_X_MM, y);
  doc.setLineDashPattern([], 0);
  return y + gapAfter;
}

function buildThermalReceiptPdf(
  doc: ReceiptPdf,
  receipt: PosThermalReceipt,
  logoRaster: TextRaster | null,
  pageHeightMm: number,
  printedAt = new Date(),
): void {
  const width = THERMAL_RECEIPT_WIDTH_MM;
  const centerX = width / 2;
  let y = 3;

  if (logoRaster) {
    const x = centerX - logoRaster.widthMm / 2;
    doc.addImage(logoRaster.dataUrl, "PNG", x, y, logoRaster.widthMm, logoRaster.heightMm);
    y += logoRaster.heightMm + 1.5;
  }

  doc.setFont("courier", "bold");
  doc.setFontSize(9);
  doc.text("Pyx Food Products", centerX, y, { align: "center" });
  y += LINE_MM;

  doc.setFont("courier", "normal");
  doc.setFontSize(7);
  doc.text("Walk-in Sales Receipt", centerX, y, { align: "center" });
  y += LINE_MM - 0.5;
  doc.text(receipt.receipt_no, centerX, y, { align: "center" });
  y += LINE_MM - 0.5;
  doc.text(printedAt.toLocaleString("en-PH"), centerX, y, { align: "center" });
  y += LINE_MM;

  y = drawRule(doc, y, width, true);

  doc.setFontSize(7);
  doc.text(`Buyer: ${receipt.buyer_name}`, MARGIN_X_MM, y);
  y += LINE_MM;
  if (receipt.seller_name?.trim()) {
    doc.text(`Seller: ${receipt.seller_name.trim()}`, MARGIN_X_MM, y);
    y += LINE_MM;
  }

  const priceColMm = 18;
  const labelWidth = width - MARGIN_X_MM * 2 - priceColMm;
  const rightX = width - MARGIN_X_MM;

  for (const item of receipt.items) {
    const label = itemLabelPdf(item);
    const priceRaster = renderTextRaster(itemPriceText(item), 7, false);
    const lines = doc.splitTextToSize(label, labelWidth);
    const lineList = Array.isArray(lines) ? lines : [lines];
    lineList.forEach((line, index) => {
      doc.text(line, MARGIN_X_MM, y);
      if (index === lineList.length - 1) {
        drawRasterRight(doc, priceRaster, rightX, y);
      }
      y += ITEM_LINE_MM;
    });
  }

  if (receipt.discount_percent != null && receipt.discount_percent > 0) {
    y = drawRule(doc, y, width, true);
    doc.text("Subtotal", MARGIN_X_MM, y);
    drawRasterRight(doc, renderTextRaster(peso(receipt.subtotal ?? receipt.total), 7), rightX, y);
    y += ITEM_LINE_MM;
    doc.text(`Discount (${receipt.discount_percent}%)`, MARGIN_X_MM, y);
    drawRasterRight(doc, renderTextRaster(`-${peso(receipt.discount_amount ?? 0)}`, 7), rightX, y);
    y += ITEM_LINE_MM;
  }

  y = drawRule(doc, y, width, false, 4);
  doc.setFont("courier", "bold");
  doc.setFontSize(10);
  doc.text("TOTAL", MARGIN_X_MM, y);
  drawRasterRight(doc, renderTextRaster(peso(receipt.total), 11, true), rightX, y);

  doc.setFont("courier", "normal");
  doc.setFontSize(4);
  doc.text(THERMAL_RECEIPT_FOOTER_TEXT, centerX, pageHeightMm - FOOTER_BOTTOM_MM, { align: "center" });
}

function printPdfBlob(blob: Blob): void {
  const url = URL.createObjectURL(blob);
  const iframe = document.createElement("iframe");
  iframe.setAttribute("aria-hidden", "true");
  iframe.setAttribute("title", "Receipt print");
  iframe.style.cssText =
    "position:fixed;left:0;top:0;width:0;height:0;border:0;opacity:0;pointer-events:none;z-index:-1";
  iframe.src = url;
  document.body.appendChild(iframe);

  const cleanup = () => {
    URL.revokeObjectURL(url);
    if (iframe.parentNode) {
      iframe.parentNode.removeChild(iframe);
    }
  };

  iframe.onload = () => {
    const win = iframe.contentWindow;
    if (!win) {
      cleanup();
      window.alert("Could not open the receipt for printing.");
      return;
    }
    win.focus();
    win.print();
    window.setTimeout(cleanup, 2000);
  };
}

async function buildAndPrintThermalPdf(receipt: PosThermalReceipt, logoSrc: string): Promise<void> {
  const { jsPDF } = await import("jspdf");
  const logoUrl = new URL(logoSrc, window.location.href).href;
  const logoAsset = await loadImageAsset(logoUrl);
  const logoRaster = logoAsset ? await rasterizeLogo(logoAsset, 10) : null;
  const pageHeight = estimateReceiptHeightMm(receipt);
  const doc = new jsPDF({
    unit: "mm",
    format: [THERMAL_RECEIPT_WIDTH_MM, pageHeight],
    compress: true,
  });
  buildThermalReceiptPdf(doc, receipt, logoRaster, pageHeight);
  printPdfBlob(doc.output("blob"));
}

/**
 * Print a C8-width (57 mm) thermal receipt as a PDF with embedded page size.
 * Avoids Chrome forcing A4 when printing HTML.
 */
export function printPosThermalReceipt(receipt: PosThermalReceipt, logoSrc: string): void {
  void buildAndPrintThermalPdf(receipt, logoSrc).catch(() => {
    window.alert("Could not prepare the receipt for printing.");
  });
}
