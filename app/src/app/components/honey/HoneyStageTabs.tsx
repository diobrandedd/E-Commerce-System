import { NavLink } from "react-router";

const STAGES = [
  { label: "Warehouse", to: "/inventory/raw-materials/honey/warehouse" },
  { label: "Production", to: "/inventory/raw-materials/honey/production" },
  { label: "Packaging", to: "/inventory/raw-materials/honey/packaging" },
] as const;

export function HoneyStageTabs() {
  return (
    <nav
      className="flex gap-1 p-1 rounded-xl border border-border bg-secondary/30 w-fit max-w-full overflow-x-auto"
      aria-label="Honey inventory stages"
    >
      {STAGES.map(stage => (
        <NavLink
          key={stage.to}
          to={stage.to}
          className={({ isActive }) =>
            `text-xs font-semibold px-3 py-2 rounded-lg whitespace-nowrap transition-colors ${
              isActive
                ? "bg-card text-foreground shadow-sm"
                : "text-muted-foreground hover:text-foreground hover:bg-card/60"
            }`
          }
        >
          {stage.label}
        </NavLink>
      ))}
    </nav>
  );
}
