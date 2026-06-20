import { TrendingDown, TrendingUp } from "lucide-react";

export const peso = (n: number | string) => {
  const v = typeof n === "string" ? parseFloat(n) : n;
  if (!Number.isFinite(v)) return "—";
  return `₱${v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

export function CostBadge({ current, baseline }: { current: number; baseline: number }) {
  if (!Number.isFinite(current) || !Number.isFinite(baseline) || baseline === 0) {
    return <span className="text-xs text-muted-foreground">—</span>;
  }
  const diff = current - baseline;
  const pct = ((diff / baseline) * 100).toFixed(1);
  if (Math.abs(diff) < 0.01) return <span className="text-xs text-muted-foreground">No change</span>;
  return (
    <span
      className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${
        diff > 0 ? "bg-red-100 text-red-600" : "bg-green-100 text-green-700"
      }`}
    >
      {diff > 0 ? <TrendingUp size={11} /> : <TrendingDown size={11} />}
      {diff > 0 ? "+" : ""}
      {pct}%
    </span>
  );
}
