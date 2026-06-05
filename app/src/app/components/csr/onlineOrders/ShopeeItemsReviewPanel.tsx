import { Pencil } from "lucide-react";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";
import { shopeeFinalItemsForRow, shopeeRowNeedsItemResolution } from "@/app/lib/honeyItemVariant";
import type { ShopeeRowResolution } from "@/app/components/csr/onlineOrders/ItemResolverCarousel";

interface ShopeeItemsReviewPanelProps {
  rows: PdfToExcelRow[];
  resolutions: Record<number, ShopeeRowResolution>;
  onEdit: (page: number) => void;
  disabled?: boolean;
}

/** Final review before building Excel — edit any ambiguous row. */
export function ShopeeItemsReviewPanel({
  rows,
  resolutions,
  onEdit,
  disabled,
}: ShopeeItemsReviewPanelProps) {
  const reviewRows = rows.filter(r => shopeeRowNeedsItemResolution(r, undefined) || !r.items?.trim());

  if (reviewRows.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">All item lines came from the PDF with variants.</p>
    );
  }

  return (
    <div className="space-y-3">
      <p className="text-sm font-semibold text-foreground">Review item lines before building Excel</p>
      <p className="text-xs text-muted-foreground">
        Check each row matches what packers will put on the label. Edit anything that looks wrong.
      </p>
      <div className="rounded-xl border border-border overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 text-left">
            <tr>
              <th className="px-3 py-2 font-medium">Page</th>
              <th className="px-3 py-2 font-medium">Tracking</th>
              <th className="px-3 py-2 font-medium">From PDF</th>
              <th className="px-3 py-2 font-medium">Final item</th>
              <th className="px-3 py-2 w-20" />
            </tr>
          </thead>
          <tbody>
            {reviewRows.map(row => {
              const finalItems = shopeeFinalItemsForRow(row, resolutions[row.page]);
              return (
                <tr key={row.page} className="border-t border-border">
                  <td className="px-3 py-2 text-muted-foreground">{row.page}</td>
                  <td className="px-3 py-2 font-mono text-xs">{row.tracking || "—"}</td>
                  <td className="px-3 py-2 text-xs text-amber-900 max-w-[140px] truncate" title={row.items}>
                    {row.items || "—"}
                  </td>
                  <td className="px-3 py-2 font-medium text-green-900">{finalItems || "—"}</td>
                  <td className="px-3 py-2">
                    <button
                      type="button"
                      disabled={disabled}
                      onClick={() => onEdit(row.page)}
                      className="text-xs font-semibold text-[#C8900A] flex items-center gap-1 disabled:opacity-50"
                    >
                      <Pencil size={12} /> Edit
                    </button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
