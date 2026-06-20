import { isDepleted } from "@/app/lib/honey";

/** Shows on-hand quantity only (no initial/progress ratio). */
export function QtyCounter({ current, initial: _initial }: { current: string | number; initial?: string | number }) {
  const depleted = isDepleted(current);
  const n = Number(current);
  const label = Number.isFinite(n) ? String(n) : String(current);
  return (
    <span className={depleted ? "opacity-50 font-semibold text-foreground" : "font-semibold text-foreground"}>
      {label}
    </span>
  );
}
