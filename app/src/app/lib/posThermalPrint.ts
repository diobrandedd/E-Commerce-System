import { apiPost } from "@/app/lib/api";
import { peso, variantLabel, type HoneyCheckoutResult } from "@/app/lib/posHoney";

/**
 * Silent POS-58 print via ESC/POS → Windows printer (no Chrome print dialog).
 * Configure printer name in config.php: pos_thermal_printer (default "POS-58").
 */
export const THERMAL_RECEIPT_PAGE_SIZE = "C8" as const;
export const THERMAL_RECEIPT_WIDTH_MM = 58;
export const THERMAL_RECEIPT_FOOTER_TEXT = "This is not an Official Receipt";

/** 58mm ESC/POS characters per line */
const COLS = 32;

export type PosThermalReceipt = Pick<
  HoneyCheckoutResult,
  "receipt_no" | "total" | "buyer_name" | "seller_name" | "items" | "subtotal" | "discount_percent" | "discount_amount"
>;

function itemLabel(item: PosThermalReceipt["items"][number]): string {
  if (item.variant) {
    return `${variantLabel(item.variant)} · ${item.size} x${item.qty}`;
  }
  return `${item.name} x${item.qty}`;
}

function asciiSafe(value: string): string {
  return value
    .replace(/₱/g, "P")
    .replace(/[^\x20-\x7E]/g, "?");
}

function padRow(left: string, right: string, width = COLS): string {
  const l = asciiSafe(left);
  const r = asciiSafe(right);
  const space = width - l.length - r.length;
  if (space < 1) {
    return (l + " " + r).slice(0, width);
  }
  return l + " ".repeat(space) + r;
}

function center(text: string, width = COLS): string {
  const t = asciiSafe(text);
  if (t.length >= width) return t.slice(0, width);
  const left = Math.floor((width - t.length) / 2);
  return " ".repeat(left) + t;
}

function buildEscPosReceipt(receipt: PosThermalReceipt, printedAt = new Date()): Uint8Array {
  const out: number[] = [];
  const push = (...bytes: number[]) => {
    for (const b of bytes) out.push(b & 0xff);
  };
  const text = (s: string) => {
    const a = asciiSafe(s);
    for (let i = 0; i < a.length; i++) out.push(a.charCodeAt(i));
  };
  const line = (s = "") => {
    text(s);
    push(0x0a);
  };

  push(0x1b, 0x40); // ESC @ init
  push(0x1b, 0x61, 0x01); // center
  line("Pyx Food Products");
  line("Walk-in Sales Receipt");
  line(receipt.receipt_no);
  line(printedAt.toLocaleString("en-PH"));
  push(0x1b, 0x61, 0x00); // left
  line("-".repeat(COLS));
  line(`Buyer: ${receipt.buyer_name}`);
  if (receipt.seller_name?.trim()) {
    line(`Seller: ${receipt.seller_name.trim()}`);
  }
  line("");

  for (const item of receipt.items) {
    const label = itemLabel(item);
    const price =
      item.line_subtotal != null && item.line_subtotal > item.line_total
        ? peso(item.line_total)
        : peso(item.line_total);
    // Wrap long labels
    const maxLeft = COLS - 10;
    if (label.length <= maxLeft) {
      line(padRow(label, price));
    } else {
      line(label.slice(0, COLS));
      line(padRow(label.slice(COLS, COLS * 2) || "", price));
    }
  }

  if (receipt.discount_percent != null && receipt.discount_percent > 0) {
    line("-".repeat(COLS));
    line(padRow("Subtotal", peso(receipt.subtotal ?? receipt.total)));
    line(padRow(`Discount (${receipt.discount_percent}%)`, `-${peso(receipt.discount_amount ?? 0)}`));
  }

  line("=".repeat(COLS));
  push(0x1b, 0x45, 0x01); // bold on
  line(padRow("TOTAL", peso(receipt.total)));
  push(0x1b, 0x45, 0x00); // bold off
  line("");
  push(0x1b, 0x61, 0x01);
  line(center(THERMAL_RECEIPT_FOOTER_TEXT));
  push(0x1b, 0x61, 0x00);
  push(0x1b, 0x64, 0x04); // feed
  push(0x1d, 0x56, 0x41, 0x10); // partial cut

  return new Uint8Array(out);
}

function bytesToBase64(bytes: Uint8Array): string {
  const chunk = 0x8000;
  let binary = "";
  for (let i = 0; i < bytes.length; i += chunk) {
    const slice = bytes.subarray(i, i + chunk);
    binary += String.fromCharCode.apply(null, Array.from(slice) as unknown as number[]);
  }
  return btoa(binary);
}

/**
 * Print silently to the configured Windows POS printer (no browser print dialog).
 */
export function printPosThermalReceipt(receipt: PosThermalReceipt, _logoSrc: string): void {
  void (async () => {
    const escpos = buildEscPosReceipt(receipt);
    await apiPost("pos.php", "thermal_print", {
      escpos_base64: bytesToBase64(escpos),
    });
  })().catch((e: unknown) => {
    const msg =
      e && typeof e === "object" && "message" in e && typeof (e as { message: unknown }).message === "string"
        ? (e as { message: string }).message
        : "Silent print failed.";
    window.alert(
      msg +
        "\n\nSet the Windows printer name in config.php:\n" +
        "  'pos_thermal_printer' => 'POS-58(copy of 1)',\n" +
        "or share the printer as POS-58 / set POS_THERMAL_PORT=COMx.",
    );
  });
}
