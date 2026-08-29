import { cn } from "@/app/components/ui/utils";

export function RequestNotesCell({
  notes,
  className,
}: {
  notes: string | null | undefined;
  className?: string;
}) {
  return (
    <td className={cn("px-3 py-2 max-w-[14rem] align-top", className)}>
      {notes?.trim() ? (
        <span className="text-xs text-muted-foreground line-clamp-3 break-words" title={notes}>
          {notes}
        </span>
      ) : (
        <span className="text-xs text-muted-foreground">—</span>
      )}
    </td>
  );
}

export function RequestNotesInline({ notes }: { notes: string | null | undefined }) {
  if (!notes?.trim()) return null;
  return (
    <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2 break-words" title={notes}>
      {notes}
    </p>
  );
}
