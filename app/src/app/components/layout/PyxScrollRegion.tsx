import { type ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";

type PyxScrollVariant = "light" | "dark";

interface PyxScrollRegionProps {
  children: ReactNode;
  className?: string;
  variant?: PyxScrollVariant;
  as?: "div" | "main" | "nav" | "section";
}

/**
 * Explicit scroll container — thumb fade-in is handled globally via usePyxScrollDocument.
 * Use `variant="dark"` on sidebar / dark panels; default `light` elsewhere.
 * Most regions only need `overflow-y-auto` (auto-themed); use this when you want an explicit wrapper.
 */
export function PyxScrollRegion({
  children,
  className,
  variant = "light",
  as: Tag = "div",
}: PyxScrollRegionProps) {
  return (
    <Tag
      className={cn(
        variant === "dark" ? "pyx-scroll-dark" : "pyx-scroll",
        "overflow-y-auto overscroll-contain",
        className,
      )}
    >
      {children}
    </Tag>
  );
}
