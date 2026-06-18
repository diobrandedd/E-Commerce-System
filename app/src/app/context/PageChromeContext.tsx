import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from "react";

/** Centered content column widths — header and main share the same max width. */
export const CONTENT_WIDTH_CLASS = {
  default: "max-w-6xl",
  wide: "max-w-7xl",
  full: "max-w-none",
} as const;

export type PageLayoutContentWidth = keyof typeof CONTENT_WIDTH_CLASS;

export interface PageChromeState {
  title: string;
  subtitle?: string;
  actions?: ReactNode;
  contentWidth: PageLayoutContentWidth;
  notificationCount: number;
}

const defaultChrome: PageChromeState = {
  title: "",
  contentWidth: "default",
  notificationCount: 0,
};

type PageChromeContextValue = {
  chrome: PageChromeState;
  setChrome: (patch: Partial<PageChromeState>) => void;
};

const PageChromeContext = createContext<PageChromeContextValue | null>(null);

export function PageChromeProvider({ children }: { children: ReactNode }) {
  const [chrome, setChromeState] = useState<PageChromeState>(defaultChrome);

  const setChrome = useCallback((patch: Partial<PageChromeState>) => {
    setChromeState(prev => ({ ...prev, ...patch }));
  }, []);

  const value = useMemo(() => ({ chrome, setChrome }), [chrome, setChrome]);

  return (
    <PageChromeContext.Provider value={value}>{children}</PageChromeContext.Provider>
  );
}

export function usePageChrome() {
  const ctx = useContext(PageChromeContext);
  if (!ctx) {
    throw new Error("usePageChrome must be used within PageChromeProvider");
  }
  return ctx;
}
