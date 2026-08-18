import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type InputHTMLAttributes,
  type PointerEvent,
  type RefObject,
} from "react";

export function isCoarsePointer(): boolean {
  return typeof window !== "undefined" && window.matchMedia("(pointer: coarse)").matches;
}

/** Dismiss Gboard / iOS keyboard by blurring whatever text field still has focus. */
export function blurActiveFormField(): void {
  const el = document.activeElement;
  if (!(el instanceof HTMLElement)) return;
  const tag = el.tagName;
  if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") {
    el.blur();
  }
}

/**
 * Park focus on a non-editable node so Android does not keep the software keyboard up
 * while a camera scanner is open.
 */
export function useDismissSoftwareKeyboard(
  active: boolean,
  sinkRef: RefObject<HTMLElement | null>,
): void {
  useEffect(() => {
    if (!active) return;
    blurActiveFormField();
    const id = window.requestAnimationFrame(() => {
      blurActiveFormField();
      sinkRef.current?.focus({ preventScroll: true });
    });
    return () => window.cancelAnimationFrame(id);
  }, [active, sinkRef]);
}

type WedgeInputProps = Pick<
  InputHTMLAttributes<HTMLInputElement>,
  | "inputMode"
  | "autoComplete"
  | "autoCorrect"
  | "autoCapitalize"
  | "spellCheck"
  | "readOnly"
  | "enterKeyHint"
  | "onPointerDown"
  | "onBlur"
>;

/**
 * Scan/type field for hardware wedges + phone cameras.
 * Default `inputMode="none"` so focusing for a 2D imager does not open Gboard.
 * A finger tap switches to text so staff can still type a code.
 */
export function useScanWedgeField(cameraOpen: boolean, refocusToken?: unknown) {
  const inputRef = useRef<HTMLInputElement>(null);
  const cameraOpenRef = useRef(cameraOpen);
  cameraOpenRef.current = cameraOpen;
  const [typeMode, setTypeMode] = useState(false);

  useEffect(() => {
    if (cameraOpen) {
      setTypeMode(false);
      inputRef.current?.blur();
      blurActiveFormField();
      return;
    }
    if (!isCoarsePointer()) {
      inputRef.current?.focus({ preventScroll: true });
    }
  }, [cameraOpen, refocusToken]);

  const refocus = useCallback(() => {
    if (cameraOpenRef.current) return;
    const el = inputRef.current;
    if (!el) return;
    el.focus({ preventScroll: true });
  }, []);

  const inputProps: WedgeInputProps = {
    inputMode: typeMode ? "text" : "none",
    autoComplete: "off",
    autoCorrect: "off",
    autoCapitalize: "off",
    spellCheck: false,
    readOnly: cameraOpen,
    enterKeyHint: "done",
    onPointerDown: (e: PointerEvent<HTMLInputElement>) => {
      if (cameraOpenRef.current) return;
      if (e.pointerType === "touch" || e.pointerType === "pen") setTypeMode(true);
    },
    onBlur: () => setTypeMode(false),
  };

  return { inputRef, refocus, inputProps };
}
