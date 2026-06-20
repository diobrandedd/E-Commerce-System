import { MousePointerClick } from "lucide-react";
import { cn } from "@/app/components/ui/utils";

/** Non-destructive control for practice tours — never opens file pickers or calls APIs. */
export function PracticeTap({
  label,
  className,
  onClick,
}: {
  label: string;
  className?: string;
  onClick?: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "w-full rounded-xl border-2 border-dashed border-amber-400/80 bg-amber-50/90 px-4 py-4 text-sm font-semibold text-amber-900",
        "inline-flex items-center justify-center gap-2 transition-transform active:scale-[0.98] hover:bg-amber-100/90",
        className,
      )}
    >
      <MousePointerClick size={16} className="flex-shrink-0" />
      {label}
    </button>
  );
}
