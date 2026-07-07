import { cn } from "@/app/components/ui/utils";
import { honeyStockStatus, type HoneyStockLevel } from "@/app/lib/honey";

const levelClasses: Record<HoneyStockLevel, string> = {
  full: "bg-green-100 text-green-800",
  medium: "bg-yellow-100 text-yellow-800",
  low: "bg-orange-100 text-orange-800",
  depleted: "bg-red-100 text-red-800",
};

export function HoneyStockStatusBadge({
  current,
  initial,
}: {
  current: string | number;
  initial: string | number;
}) {
  const { level, label } = honeyStockStatus(current, initial);
  return (
    <span
      className={cn(
        "inline-flex text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap",
        levelClasses[level],
      )}
    >
      {label}
    </span>
  );
}
