import { peso, variantLabel, type HoneyCheckoutResult } from "@/app/lib/posHoney";

/**
 * POS receipt print via Chrome print dialog (iframe + window.print).
 * Silent Winspool/GDI paths were unreliable on this POS-58 driver.
 */
export const THERMAL_RECEIPT_PAGE_SIZE = "C8" as const;
export const THERMAL_RECEIPT_WIDTH_MM = 58;
export const THERMAL_RECEIPT_FOOTER_TEXT = "This is not an Official Receipt";

const COLS = 28; // keep prices left of the physical cut edge (~.00 was clipping at 32)

export type PosThermalReceipt = Pick<
  HoneyCheckoutResult,
  "receipt_no" | "total" | "buyer_name" | "seller_name" | "items" | "subtotal" | "discount_percent" | "discount_amount"
>;

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function itemLabel(item: PosThermalReceipt["items"][number]): string {
  if (item.variant) {
    return `${variantLabel(item.variant)} · ${item.size} x${item.qty}`;
  }
  return `${item.name} x${item.qty}`;
}

function padRow(left: string, right: string, width = COLS): string {
  const space = width - left.length - right.length;
  if (space < 1) {
    return (left + " " + right).slice(0, width);
  }
  return left + " ".repeat(space) + right;
}

function center(text: string, width = COLS): string {
  if (text.length >= width) return text.slice(0, width);
  const left = Math.floor((width - text.length) / 2);
  return " ".repeat(left) + text;
}

function buildReceiptHtml(receipt: PosThermalReceipt, logoSrc: string, printedAt = new Date()): string {
  const lines: string[] = [];
  lines.push(center("Pyx Food Products"));
  lines.push(center("Walk-in Sales Receipt"));
  lines.push(center(receipt.receipt_no));
  lines.push(center(printedAt.toLocaleString("en-PH")));
  lines.push("-".repeat(COLS));
  lines.push(`Buyer: ${receipt.buyer_name}`);
  if (receipt.seller_name?.trim()) {
    lines.push(`Seller: ${receipt.seller_name.trim()}`);
  }
  lines.push("");

  for (const item of receipt.items) {
    const label = itemLabel(item);
    const price = peso(item.line_total);
    const maxLeft = COLS - 10;
    if (label.length <= maxLeft) {
      lines.push(padRow(label, price));
    } else {
      lines.push(label.slice(0, COLS));
      lines.push(padRow(label.slice(COLS, COLS * 2) || "", price));
    }
  }

  if (receipt.discount_percent != null && receipt.discount_percent > 0) {
    lines.push("-".repeat(COLS));
    lines.push(padRow("Subtotal", peso(receipt.subtotal ?? receipt.total)));
    lines.push(padRow(`Discount (${receipt.discount_percent}%)`, `-${peso(receipt.discount_amount ?? 0)}`));
  }

  lines.push("=".repeat(COLS));
  lines.push(padRow("TOTAL", peso(receipt.total)));

  const body = lines.map(l => escapeHtml(l) || "&nbsp;").join("<br>");
  const logo = logoSrc
    ? `<img class="logo" src="${escapeHtml(logoSrc)}" alt="Pyx" />`
    : "";
  const footer = escapeHtml(THERMAL_RECEIPT_FOOTER_TEXT);

  // Fixed 58×210mm page — right padding keeps ₱x.00 off the cut edge.
  // Footer is CSS-centered (not COLS-truncated) so the full disclaimer prints.
  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>POS Receipt</title>
  <style>
    @page { size: 58mm 210mm; margin: 2mm 5mm 2mm 2mm; }
    html, body {
      margin: 0; padding: 0;
      width: 48mm;
      background: #fff;
      color: #000;
      font-family: "Courier New", Courier, monospace;
      font-size: 10px;
      line-height: 1.25;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .wrap {
      width: 48mm;
      padding: 1mm 3mm 1mm 0;
      box-sizing: border-box;
    }
    .logo {
      display: block;
      margin: 0 auto 2mm;
      max-width: 26mm;
      max-height: 12mm;
      object-fit: contain;
    }
    .mono { white-space: pre-wrap; word-break: break-word; }
    .footer {
      margin-top: 3mm;
      text-align: center;
      white-space: normal;
      word-break: break-word;
      font-size: 9px;
      line-height: 1.3;
    }
  </style>
</head>
<body>
  <div class="wrap">
    ${logo}
    <div class="mono">${body}</div>
    <div class="footer">${footer}</div>
  </div>
</body>
</html>`;
}

/**
 * Open Chrome print dialog for the receipt (POS-58 / system printer picker).
 */
export function printPosThermalReceipt(receipt: PosThermalReceipt, logoSrc: string): void {
  const html = buildReceiptHtml(receipt, logoSrc);
  const iframe = document.createElement("iframe");
  iframe.setAttribute("aria-hidden", "true");
  iframe.style.cssText =
    "position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;";
  document.body.appendChild(iframe);

  const win = iframe.contentWindow;
  const doc = iframe.contentDocument;
  if (!win || !doc) {
    iframe.remove();
    window.alert("Could not open the print preview. Allow pop-ups / try again.");
    return;
  }

  doc.open();
  doc.write(html);
  doc.close();

  const cleanup = () => {
    window.setTimeout(() => iframe.remove(), 800);
  };

  const runPrint = () => {
    try {
      win.focus();
      win.print();
    } catch {
      window.alert("Print dialog failed to open. Try again.");
    } finally {
      cleanup();
    }
  };

  // Images (logo) need a tick to load before print on some browsers
  window.setTimeout(runPrint, logoSrc ? 250 : 50);
}
