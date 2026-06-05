import { useMemo } from "react";
import { Pencil } from "lucide-react";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";
import { honeyItemLineText, shopeeFinalItemsForRow } from "@/app/lib/honeyItemVariant";
import type { ShopeeRowResolution } from "@/app/components/csr/onlineOrders/ItemResolverCarousel";

interface ShopeeItemsReviewPanelProps {
  rows: PdfToExcelRow[];
  resolutions: Record<number, ShopeeRowResolution>;
  onEdit: (page: number) => void;
  disabled?: boolean;
}

/** Final review before building Excel — full batch list; edit any row if needed. */
export function ShopeeItemsReviewPanel({
  rows,
  resolutions,
  onEdit,
  disabled,
}: ShopeeItemsReviewPanelProps) {
  const reviewRows = useMemo(
    () => [...rows].sort((a, b) => a.page - b.page),
    [rows],
  );

  if (reviewRows.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">No item lines to review.</p>
    );
  }

  return (
    <div className="space-y-3">
      <p className="text-sm font-semibold text-foreground">Review item lines before building Excel</p>
      <p className="text-xs text-muted-foreground">
        {reviewRows.length} item{reviewRows.length === 1 ? "" : "s"} — check each row matches what packers will put on
        the label. Edit anything that looks wrong.
      </p>
      <div className="rounded-xl border border-border overflow-hidden max-h-[min(70vh,520px)] overflow-y-auto">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 text-left sticky top-0 z-10">
            <tr>
              <th className="px-3 py-2 font-medium">Page</th>
              <th className="px-3 py-2 font-medium">Tracking</th>
              <th className="px-3 py-2 font-medium">Original</th>
              <th className="px-3 py-2 font-medium">Final item</th>
              <th className="px-3 py-2 w-20" />
            </tr>
          </thead>
          <tbody>
            {reviewRows.map(row => {
              const originalItems = honeyItemLineText(row.items);
              const finalItems = shopeeFinalItemsForRow(row, resolutions[row.page]);
              const edited = Boolean(finalItems && originalItems && finalItems !== originalItems);
              return (
                <tr key={row.page} className="border-t border-border">
                  <td className="px-3 py-2 text-muted-foreground">{row.page}</td>
                  <td className="px-3 py-2 font-mono text-xs">{row.tracking || "—"}</td>
                  <td
                    className="px-3 py-2 text-xs text-muted-foreground max-w-[180px] truncate"
                    title={originalItems}
                  >
                    {originalItems || "—"}
                  </td>
                  <td
                    className={`px-3 py-2 max-w-[200px] truncate ${edited ? "font-medium text-green-900" : "text-foreground"}`}
                    title={finalItems}
                  >
                    {finalItems || "—"}
                  </td>
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
