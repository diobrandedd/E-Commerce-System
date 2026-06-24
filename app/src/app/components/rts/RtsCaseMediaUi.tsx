import { useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2, Play } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";
import {
  rtsGetCase,
  rtsLineDisplay,
  type RtsAttachment,
  type RtsCaseRow,
  type RtsLineItem,
  type RtsProductCatalog,
} from "@/app/lib/rtsApi";

export function rtsDocUrl(docBase: string, file: string) {
  return `${docBase}${file}`;
}

export function RtsMediaThumb({
  src,
  kind,
  title,
  className,
}: {
  src: string;
  kind: "image" | "video";
  title?: string;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "relative shrink-0 h-11 w-14 overflow-hidden rounded-md border border-border bg-secondary/30",
        className,
      )}
      title={title}
    >
      {kind === "video" ? (
        <>
          <video
            src={`${src}#t=0.1`}
            preload="metadata"
            muted
            playsInline
            className="h-full w-full object-cover"
          />
          <div className="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/25">
            <Play className="h-3.5 w-3.5 text-white fill-white" />
          </div>
        </>
      ) : (
        <img src={src} alt="" className="h-full w-full object-cover" loading="lazy" />
      )}
    </div>
  );
}

function firstAttachment(row: RtsCaseRow, kind: "image" | "video"): RtsAttachment | undefined {
  return row.documentation_attachments?.find(a => a.kind === kind);
}

/** Table cells: before-shipment thumb, RTS photo thumb, RTS video thumb. */
export function RtsCaseMediaTableCells({ row, docBase }: { row: RtsCaseRow; docBase: string }) {
  const beforeUrl = row.before_shipment?.available ? row.before_shipment.download_url ?? null : null;
  const image = firstAttachment(row, "image");
  const video = firstAttachment(row, "video");
  const imageUrl = image ? rtsDocUrl(docBase, image.file) : null;
  const videoUrl = video ? rtsDocUrl(docBase, video.file) : null;

  return (
    <>
      <td className="px-3 py-2">
        {beforeUrl ? (
          <RtsMediaThumb src={beforeUrl} kind="image" title="Before shipment" />
        ) : (
          <span className="text-[10px] text-muted-foreground">N/A</span>
        )}
      </td>
      <td className="px-3 py-2">
        {imageUrl ? (
          <RtsMediaThumb src={imageUrl} kind="image" title="RTS photo" />
        ) : (
          <span className="text-[10px] text-muted-foreground">—</span>
        )}
      </td>
      <td className="px-3 py-2">
        {videoUrl ? (
          <RtsMediaThumb src={videoUrl} kind="video" title="RTS video" />
        ) : (
          <span className="text-[10px] text-muted-foreground">—</span>
        )}
      </td>
    </>
  );
}

const mediaLabelClass = "text-xs font-medium text-muted-foreground uppercase tracking-wide";

export function RtsCaseMediaGallery({
  row,
  docBase,
  className,
}: {
  row: RtsCaseRow;
  docBase: string;
  className?: string;
}) {
  const beforeUrl = row.before_shipment?.available ? row.before_shipment.download_url ?? null : null;
  const attachments = row.documentation_attachments ?? [];

  return (
    <div className={cn("space-y-4", className)}>
      <div>
        <p className={cn(mediaLabelClass, "mb-2")}>Before shipment</p>
        {beforeUrl ? (
          <a href={beforeUrl} target="_blank" rel="noreferrer" className="block rounded-lg overflow-hidden border border-border max-w-xs">
            <img src={beforeUrl} alt="Before shipment" className="max-h-48 w-full object-cover" loading="lazy" />
          </a>
        ) : (
          <p className="text-sm text-muted-foreground">Not available</p>
        )}
      </div>

      {attachments.length > 0 && (
        <div>
          <p className={cn(mediaLabelClass, "mb-2")}>RTS documentation</p>
          <div className="grid gap-3 sm:grid-cols-2">
            {attachments.map((a, i) => {
              const url = rtsDocUrl(docBase, a.file);
              return (
                <div key={`${a.file}-${i}`} className="rounded-lg overflow-hidden border border-border bg-secondary/20">
                  {a.kind === "video" ? (
                    <video controls playsInline preload="metadata" src={url} className="w-full max-h-56 bg-black" />
                  ) : (
                    <a href={url} target="_blank" rel="noreferrer">
                      <img src={url} alt="" className="max-h-48 w-full object-cover" loading="lazy" />
                    </a>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}

export type RtsRegistryVariant = "swapped" | "leak";

export function RtsRegistryDetailModal({
  row,
  catalog,
  variant,
  onClose,
}: {
  row: RtsCaseRow | null;
  catalog: RtsProductCatalog | null;
  variant: RtsRegistryVariant;
  onClose: () => void;
}) {
  const [detail, setDetail] = useState<RtsCaseRow | null>(row);
  const [lines, setLines] = useState<RtsLineItem[]>(row?.lines ?? []);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!row) {
      setDetail(null);
      setLines([]);
      return;
    }
    setDetail(row);
    setLines(row.lines ?? []);
    setLoading(true);
    void rtsGetCase(row.tracking_number)
      .then(data => {
        if (data.case) {
          setDetail({ ...row, ...data.case });
        }
        if (data.lines.length) {
          setLines(data.lines);
        }
      })
      .finally(() => setLoading(false));
  }, [row]);

  const docBase = catalog?.doc_url_base ?? "";
  const labels = catalog?.product_labels ?? {};

  return (
    <AppModal
      open={!!row}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] overflow-y-auto"
      labelledBy="rts-registry-detail-title"
    >
      {detail && (
        <>
          <AppModalHeader
            title={detail.tracking_number}
            titleId="rts-registry-detail-title"
            subtitle={variant === "swapped" ? "Swapped parcel" : "Leaked / damaged"}
            onClose={onClose}
          />
          <AppModalBody className="space-y-4">
            <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 text-sm">
              <dt className="text-muted-foreground">Shop</dt>
              <dd className="capitalize">{detail.shop}</dd>
              <dt className="text-muted-foreground">Courier</dt>
              <dd>{catalog?.couriers[detail.courier_final] ?? detail.courier_final}</dd>
              <dt className="text-muted-foreground">Delivered</dt>
              <dd>{detail.date_delivered}</dd>
              <dt className="text-muted-foreground">Opened</dt>
              <dd>{detail.date_opened}</dd>
            </dl>

            {variant === "leak" && (
              <div className="rounded-lg border border-border p-3 bg-secondary/20 space-y-3">
                {loading ? (
                  <Loader2 size={14} className="animate-spin mt-2" />
                ) : (
                  <>
                    <div>
                      <p className={mediaLabelClass}>Damaged / leaked</p>
                      {lines.filter(l => l.line_role === "damaged").length ? (
                        <ul className="text-sm list-disc ml-4 mt-2 space-y-0.5">
                          {lines.filter(l => l.line_role === "damaged").map((l, i) => (
                            <li key={`d-${i}`}>{rtsLineDisplay(l, labels)}</li>
                          ))}
                        </ul>
                      ) : (
                        <p className="text-sm text-muted-foreground mt-2">None recorded.</p>
                      )}
                    </div>
                    <div>
                      <p className={mediaLabelClass}>Reusable (RTS sellable)</p>
                      {lines.filter(l => l.line_role !== "damaged").length ? (
                        <ul className="text-sm list-disc ml-4 mt-2 space-y-0.5">
                          {lines.filter(l => l.line_role !== "damaged").map((l, i) => (
                            <li key={`r-${i}`}>{rtsLineDisplay(l, labels)}</li>
                          ))}
                        </ul>
                      ) : (
                        <p className="text-sm text-muted-foreground mt-2">None recorded.</p>
                      )}
                    </div>
                  </>
                )}
              </div>
            )}

            {loading && !detail.before_shipment ? (
              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <Loader2 size={14} className="animate-spin" /> Loading media…
              </div>
            ) : (
              <RtsCaseMediaGallery row={detail} docBase={docBase} />
            )}

            <Link
              to={`/rts/record?edit=${encodeURIComponent(detail.tracking_number)}`}
              className="inline-block text-sm font-semibold text-[#C8900A] hover:underline"
            >
              Edit record
            </Link>
          </AppModalBody>
        </>
      )}
    </AppModal>
  );
}

export const RTS_MEDIA_TABLE_HEADERS = (
  <>
    <th className="text-left px-3 py-2">Before ship</th>
    <th className="text-left px-3 py-2">Photo</th>
    <th className="text-left px-3 py-2">Video</th>
  </>
);

export function rtsRegistryRowClassName() {
  return cn("border-t border-border cursor-pointer", appHoverRow);
}
