import { useEffect } from "react";

const HIDE_MS = 1000;

const SCROLL_SELECTOR =
  ".pyx-scroll, .pyx-scroll-dark, .overflow-y-auto:not(.no-pyx-scroll), .overflow-auto:not(.no-pyx-scroll)";

/** Document-level scroll listener — adds `pyx-scroll-active` to any themed scroll region. */
export function usePyxScrollDocument() {
  useEffect(() => {
    const timers = new WeakMap<Element, ReturnType<typeof setTimeout>>();

    const onScroll = (e: Event) => {
      const target = e.target;
      if (!(target instanceof HTMLElement) || !target.matches(SCROLL_SELECTOR)) return;

      target.classList.add("pyx-scroll-active");
      const prev = timers.get(target);
      if (prev) clearTimeout(prev);
      timers.set(
        target,
        setTimeout(() => {
          target.classList.remove("pyx-scroll-active");
          timers.delete(target);
        }, HIDE_MS),
      );
    };

    document.addEventListener("scroll", onScroll, { capture: true, passive: true });
    return () => document.removeEventListener("scroll", onScroll, { capture: true });
  }, []);
}
