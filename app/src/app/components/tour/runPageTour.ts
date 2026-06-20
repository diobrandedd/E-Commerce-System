import { driver, type DriveStep, type PopoverDOM } from "driver.js";
import "driver.js/dist/driver.css";
import "./pyxTour.css";

export type PageTourOptions = {
  steps: DriveStep[];
  onComplete?: () => void;
};

function prefersReducedMotion(): boolean {
  return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
}

function replayPopoverEnter(popover: PopoverDOM) {
  const { wrapper, progress } = popover;
  wrapper.classList.remove("pyx-tour-popover-step-enter");
  progress.classList.remove("pyx-tour-progress-tick");
  void wrapper.offsetWidth;
  wrapper.classList.add("pyx-tour-popover-step-enter");
  progress.classList.add("pyx-tour-progress-tick");
}

function pulseTarget(element: Element | undefined) {
  if (!element || element.id === "driver-dummy-element" || prefersReducedMotion()) return;
  element.classList.remove("pyx-tour-target-land");
  void element.getBoundingClientRect();
  element.classList.add("pyx-tour-target-land");
  window.setTimeout(() => element.classList.remove("pyx-tour-target-land"), 500);
}

/**
 * Start a click-by-click page tour — dimmed overlay, highlighted target, arrow + tooltip.
 * Import this module (not driver.js directly) so Pyx styles load once.
 */
export function runPageTour({ steps, onComplete }: PageTourOptions) {
  const reduceMotion = prefersReducedMotion();

  const tour = driver({
    steps,
    animate: !reduceMotion,
    smoothScroll: true,
    allowClose: true,
    overlayOpacity: 0.55,
    stagePadding: 10,
    stageRadius: 12,
    showProgress: true,
    progressText: "{{current}} of {{total}}",
    nextBtnText: "Next",
    prevBtnText: "Back",
    doneBtnText: "Done",
    popoverClass: "pyx-tour-popover",
    popoverOffset: 12,
    onPopoverRender: (popover) => {
      if (!reduceMotion) replayPopoverEnter(popover);
    },
    onHighlighted: (element) => {
      pulseTarget(element);
    },
    onDestroyed: () => {
      onComplete?.();
    },
  });

  tour.drive();
  return tour;
}

/** Close guide modal first, then start tour after paint so targets are visible. */
export function startPageTourAfterModalClose(run: () => void, delayMs = 280) {
  window.setTimeout(run, delayMs);
}
