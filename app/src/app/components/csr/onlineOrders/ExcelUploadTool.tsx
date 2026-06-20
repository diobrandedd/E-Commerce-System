import { useState } from "react";
import { CheckCircle2, Download, FileSpreadsheet, Loader2 } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { FulfillmentErrorBanner, FulfillmentPressable, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import { triggerWaybillStashDownload } from "@/app/lib/waybillApi";
import { onlineOrdersToolExcelRegister } from "@/app/lib/onlineOrdersApi";
import type { OnlineOrdersPrepResult } from "@/app/lib/onlineOrdersTypes";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";
import { PracticeTap } from "@/app/components/tour/PracticeTap";
import {
  practiceStageAtLeast,
  type OnlineOrdersPracticeStage,
} from "@/app/components/csr/onlineOrders/onlineOrdersPractice";
import { OnlineOrdersBackToToolsButton } from "@/app/components/csr/onlineOrders/OnlineOrdersNavButtons";

async function stashExcel(file: File): Promise<string> {
  const form = new FormData();
  form.append("kind", "excel");
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("waybill.php", "stash", form);
  return data.token;
}

export function ExcelUploadTool({
  onDone,
  onBack,
  practiceMode = false,
  practiceStage = "excel-upload",
}: {
  onDone: () => void;
  onBack: () => void;
  practiceMode?: boolean;
  practiceStage?: OnlineOrdersPracticeStage;
}) {
  const [excelFiles, setExcelFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [preview, setPreview] = useState<OnlineOrdersPrepResult | null>(null);

  const register = async () => {
    if (excelFiles.length === 0) {
      setError("Upload at least one iPYX Excel file.");
      return;
    }
    setBusy(true);
    setError(null);
    setPreview(null);
    try {
      const tokens: string[] = [];
      for (const f of excelFiles) {
        tokens.push(await stashExcel(f));
      }
      const prep = await onlineOrdersToolExcelRegister(tokens[0], tokens.length > 1 ? tokens : undefined);
      setPreview(prep);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not read Excel.");
    } finally {
      setBusy(false);
    }
  };

  if (practiceMode) {
    return (
      <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
        <OnlineOrdersBackToToolsButton onClick={onBack} />
        <p className="text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          Practice mode — no files uploaded
        </p>
        {practiceStageAtLeast(practiceStage, "excel-upload") && (
          <div data-tour="online-orders-excel-upload">
            <PracticeTap label="Practice: upload iPYX Excel" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "excel-validate") && (
          <div data-tour="online-orders-excel-validate">
            <PracticeTap label="Practice: validate Excel" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "excel-result") && (
          <div className="rounded-lg border border-green-200 bg-green-50/80 p-4 space-y-3" data-tour="online-orders-excel-result">
            <p className="text-sm text-green-800 font-medium">24 rows validated (sample)</p>
            <PracticeTap label="Practice: back to tools" />
          </div>
        )}
      </FulfillmentSectionEnter>
    );
  }

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <OnlineOrdersBackToToolsButton onClick={onBack} />
      <p className="text-sm text-muted-foreground">
        Upload partner iPYX Excel (.xlsx). We validate columns and row count — go back to tools and open PDF + Excel stamper to stamp.
      </p>
      <div data-tour="online-orders-excel-upload">
      <FileZone
        label="iPYX order sheets"
        hint="Partner column layout — one or more workbooks"
        accept=".xlsx,.xls"
        icon={FileSpreadsheet}
        files={excelFiles}
        onFiles={fs => {
          setExcelFiles(fs);
          setPreview(null);
        }}
        disabled={busy}
      />
      </div>
      <button
        type="button"
        disabled={busy || excelFiles.length === 0}
        onClick={() => void register()}
        data-tour="online-orders-excel-validate"
        className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
      >
        {busy ? <Loader2 size={16} className="animate-spin inline" /> : null}
        Validate Excel
      </button>

      {preview && (
        <div className="rounded-lg border border-green-200 bg-green-50/80 p-4 space-y-3" data-tour="online-orders-excel-result">
          <p className="text-sm text-green-800 flex items-center gap-2">
            <CheckCircle2 size={18} />
            {preview.row_count} row{preview.row_count === 1 ? "" : "s"} validated
          </p>
          <div className="flex flex-wrap gap-3">
            <FulfillmentPressable
              variant="outline"
              className="inline-flex items-center gap-2 px-4 py-2.5"
              onClick={() => triggerWaybillStashDownload(preview.excel_token)}
            >
              <Download size={16} /> Download Excel
            </FulfillmentPressable>
            <OnlineOrdersBackToToolsButton onClick={onDone} />
          </div>
        </div>
      )}

      <FulfillmentErrorBanner message={error} />
    </FulfillmentSectionEnter>
  );
}
