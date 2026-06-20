import type { DriveStep, Popover } from "driver.js";

type PopoverContent = Popover;

/** Centered intro/outro with no highlighted element. */
export function tourIntro(title: string, description: string): DriveStep {
  return {
    popover: {
      title,
      description,
      side: "over",
      align: "center",
    },
  };
}

/** Spotlight a control — user taps Next on the popover. */
export function tourSpotlight(element: string, popover: PopoverContent): DriveStep {
  return { element, popover };
}

/**
 * Hands-on step: advance when the user clicks the highlighted element (or Skip on the popover).
 * Use only for controls that do not navigate away from the current view.
 */
export function tourClickToContinue(
  element: string,
  popover: PopoverContent & { skipLabel?: string },
  options?: { onAdvance?: () => void },
): DriveStep {
  let cleanup: (() => void) | undefined;

  const baseDescription = popover.description ?? "";
  const handsOnHint = "Click the highlighted control, or tap Continue to move on.";

  const advanceAndNext = (driver: { moveNext: () => void }) => {
    options?.onAdvance?.();
    window.setTimeout(() => driver.moveNext(), 280);
  };

  return {
    element,
    popover: {
      ...popover,
      nextBtnText: popover.skipLabel ?? "Continue",
      description: baseDescription ? `${baseDescription} ${handsOnHint}` : handsOnHint,
      onNextClick: (_el, _step, { driver }) => {
        advanceAndNext(driver);
      },
    },
    onHighlighted: (_el, _step, { driver }) => {
      const target = document.querySelector<HTMLElement>(element);
      if (!target) return;
      const onClick = (e: Event) => {
        if (target !== e.currentTarget && !target.contains(e.target as Node)) return;
        cleanup?.();
        advanceAndNext(driver);
      };
      target.addEventListener("click", onClick, { capture: true });
      cleanup = () => target.removeEventListener("click", onClick, { capture: true });
    },
    onDeselected: () => {
      cleanup?.();
      cleanup = undefined;
    },
  };
}

/** Skip steps whose target is not in the DOM (hidden step, empty table, etc.). */
export function filterTourSteps(steps: DriveStep[]): DriveStep[] {
  return steps.filter(step => {
    if (!step.element) return true;
    if (typeof step.element === "string") {
      return Boolean(document.querySelector(step.element));
    }
    if (typeof step.element === "function") {
      try {
        return Boolean(step.element());
      } catch {
        return false;
      }
    }
    return true;
  });
}
