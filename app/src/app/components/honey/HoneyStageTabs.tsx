import { type MouseEvent, type ReactNode, useLayoutEffect, useRef, useState } from "react";
import { flushSync } from "react-dom";
import { motion, useReducedMotion } from "motion/react";
import { NavLink, useLocation, useNavigate } from "react-router";

const STAGES = [
  { label: "Warehouse", to: "/inventory/raw-materials/honey/warehouse" },
  { label: "Production", to: "/inventory/raw-materials/honey/production" },
  { label: "Packaging", to: "/inventory/raw-materials/honey/packaging" },
] as const;

export function HoneyStageTabs() {
  const location = useLocation();
  const navigate = useNavigate();
  const reduceMotion = useReducedMotion();
  const currentIndex = STAGES.findIndex(stage => location.pathname === stage.to);
  const routeDirection = Number(
    (location.state as { honeyStageDirection?: number } | null)?.honeyStageDirection ?? 0,
  );
  const navRef = useRef<HTMLElement>(null);
  const linkRefs = useRef<Array<HTMLAnchorElement | null>>([]);
  const [indicator, setIndicator] = useState<{
    current: { x: number; width: number };
    previous: { x: number; width: number };
  } | null>(null);

  useLayoutEffect(() => {
    const nav = navRef.current;
    const current = linkRefs.current[currentIndex];
    if (!nav || !current || currentIndex < 0) return;

    const navRect = nav.getBoundingClientRect();
    const previousIndex = Math.min(
      STAGES.length - 1,
      Math.max(0, currentIndex - routeDirection),
    );
    const previous = linkRefs.current[previousIndex] ?? current;
    const currentRect = current.getBoundingClientRect();
    const previousRect = previous.getBoundingClientRect();

    setIndicator({
      current: { x: currentRect.left - navRect.left, width: currentRect.width },
      previous: { x: previousRect.left - navRect.left, width: previousRect.width },
    });
  }, [currentIndex, routeDirection]);

  const navigateStage = (event: MouseEvent<HTMLAnchorElement>, stageIndex: number) => {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      stageIndex === currentIndex
    ) {
      return;
    }

    const stage = STAGES[stageIndex];
    const direction = stageIndex > currentIndex ? 1 : -1;
    const documentWithTransitions = document as Document & {
      startViewTransition?: (update: () => void) => { finished: Promise<void> };
    };

    const prefersReducedMotion =
      reduceMotion || window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const useMobileHandoff = window.matchMedia("(max-width: 639px)").matches;
    if (prefersReducedMotion || useMobileHandoff || !documentWithTransitions.startViewTransition) return;

    event.preventDefault();
    document.documentElement.dataset.honeyStageDirection = direction > 0 ? "forward" : "back";
    const transition = documentWithTransitions.startViewTransition(() => {
      flushSync(() => {
        navigate(stage.to, { state: { honeyStageDirection: direction } });
      });
    });
    void transition.finished.finally(() => {
      delete document.documentElement.dataset.honeyStageDirection;
    });
  };

  return (
    <nav
      ref={navRef}
      className="relative flex gap-1 p-1 rounded-xl border border-border bg-secondary/30 w-fit max-w-full overflow-x-auto"
      aria-label="Honey inventory stages"
    >
      {indicator ? (
        <motion.span
          aria-hidden
          data-testid="honey-stage-indicator"
          className="pointer-events-none absolute inset-y-1 left-0 z-0 rounded-lg bg-card shadow-sm sm:hidden"
          initial={
            reduceMotion
              ? false
              : { x: indicator.previous.x, width: indicator.previous.width, opacity: 0.7 }
          }
          animate={{ x: indicator.current.x, width: indicator.current.width, opacity: 1 }}
          transition={{ type: "spring", stiffness: 460, damping: 36, mass: 0.72 }}
        />
      ) : null}
      {STAGES.map((stage, stageIndex) => (
        <NavLink
          key={stage.to}
          ref={node => {
            linkRefs.current[stageIndex] = node;
          }}
          to={stage.to}
          state={{ honeyStageDirection: stageIndex > currentIndex ? 1 : -1 }}
          onClick={event => navigateStage(event, stageIndex)}
          style={({ isActive }) => isActive ? { viewTransitionName: "honey-stage-tab" } : undefined}
          className={({ isActive }) =>
            `relative z-10 inline-flex min-h-11 items-center text-xs font-semibold px-3 py-2 rounded-lg whitespace-nowrap transition-[color,background-color,box-shadow] duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring ${
              isActive
                ? "text-foreground sm:bg-card sm:shadow-sm"
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

export function HoneyStageContent({ children }: { children: ReactNode }) {
  const location = useLocation();
  const reduceMotion = useReducedMotion();
  const direction = Number(
    (location.state as { honeyStageDirection?: number } | null)?.honeyStageDirection ?? 0,
  );
  const nativeTransitionActive =
    typeof document !== "undefined" &&
    Boolean(document.documentElement.dataset.honeyStageDirection);
  const shouldEnter = !reduceMotion && !nativeTransitionActive && direction !== 0;

  return (
    <motion.div
      data-testid="honey-stage-content"
      style={{ viewTransitionName: "honey-stage-content" }}
      initial={
        shouldEnter
          ? {
              opacity: 0.38,
              x: direction > 0 ? 28 : -28,
              clipPath: direction > 0 ? "inset(0 0 0 7%)" : "inset(0 7% 0 0)",
            }
          : false
      }
      animate={{ opacity: 1, x: 0, clipPath: "inset(0 0 0 0)" }}
      transition={{ duration: 0.34, ease: [0.16, 1, 0.3, 1] }}
    >
      {children}
    </motion.div>
  );
}

