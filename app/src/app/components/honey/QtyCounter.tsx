import { fmtCounter, isDepleted } from "@/app/lib/honey";

export function QtyCounter({ current, initial }: { current: string | number; initial: string | number }) {
  const depleted = isDepleted(current);
  return (
    <span className={depleted ? "opacity-50" : ""}>
      <span className="font-semibold text-foreground">{fmtCounter(current, initial).split(" / ")[0]}</span>
      <span className="text-muted-foreground"> / {fmtCounter(current, initial).split(" / ")[1]}</span>
    </span>
  );
}
