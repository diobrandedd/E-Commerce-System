import { useLayoutEffect, useRef, useState, useEffect, ReactNode } from "react";
import { Outlet, useNavigate } from "react-router";
import { Menu, Bell } from "lucide-react";
import { Sidebar } from "./Sidebar";
import { useAuth } from "@/app/context/AuthContext";
import {
  PageChromeProvider,
  usePageChrome,
  CONTENT_WIDTH_CLASS,
  type PageLayoutContentWidth,
} from "@/app/context/PageChromeContext";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";
import { formatEditRequestCategory, formatEditRequestField } from "@/app/lib/editRequestsApi";
import { cn } from "@/app/components/ui/utils";

export type { PageLayoutContentWidth } from "@/app/context/PageChromeContext";

interface PageLayoutProps {
  title: string;
  subtitle?: string;
  actions?: ReactNode;
  children: ReactNode;
  notificationCount?: number;
  /** Centered column width. Default `default` (max-w-6xl). Use `full` only for wide data grids. */
  contentWidth?: PageLayoutContentWidth;
}

/** Persistent shell: sidebar + header + routed main content. Mounted once per authenticated session. */
export function AppShell() {
  return (
    <PageChromeProvider>
      <AppShellFrame />
    </PageChromeProvider>
  );
}

function AppShellFrame() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [bellOpen, setBellOpen] = useState(false);
  const bellRef = useRef<HTMLDivElement>(null);
  const { user } = useAuth();
  const navigate = useNavigate();
  const { chrome } = usePageChrome();
  const isExec = user?.role === "executive";
  const { unreadCount, unreadList, refresh: refreshNotifications } = useCorrectionNotifications();

  const { title, subtitle, actions, contentWidth = "default", notificationCount = 0 } = chrome;
  const badgeCount = isExec ? unreadCount : notificationCount;
  const contentChromeClass = cn(
    "w-full mx-auto px-4 sm:px-6",
    CONTENT_WIDTH_CLASS[contentWidth],
  );

  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (bellRef.current && !bellRef.current.contains(e.target as Node)) setBellOpen(false);
    };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const openCorrectionRequest = (id: number) => {
    setBellOpen(false);
    navigate(`/inventory/requests?request=${id}`);
  };

  const openSupplyRequest = (id: number) => {
    setBellOpen(false);
    navigate(`/inventory/requests?supply_request=${id}#supply-requests-section`);
  };

  return (
    <div
      className="h-dvh flex overflow-hidden bg-background"
      style={{ fontFamily: "'DM Sans', sans-serif" }}
    >
      <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">
        <header
          className="shrink-0 z-10 bg-card border-b border-border"
          style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
        >
          <div className={cn(contentChromeClass, "flex items-center justify-between py-4")}>
            <div className="flex items-center gap-3 min-w-0">
              <button
                type="button"
                className="lg:hidden text-muted-foreground hover:text-foreground"
                onClick={() => setSidebarOpen(true)}
              >
                <Menu size={20} />
              </button>
              <div>
                <h2 className="text-base font-semibold text-foreground">{title}</h2>
                {subtitle && <p className="text-xs text-muted-foreground hidden sm:block">{subtitle}</p>}
              </div>
            </div>
            <div className="flex items-center gap-3 shrink-0">
              {actions}
              <div className="relative" ref={bellRef}>
                <button
                  type="button"
                  onClick={() => {
                    setBellOpen(v => {
                      const next = !v;
                      if (next && isExec) void refreshNotifications();
                      return next;
                    });
                  }}
                  className="relative p-2 rounded-lg hover:bg-secondary transition-colors text-muted-foreground hover:text-foreground"
                  aria-label="Notifications"
                >
                  <Bell size={18} />
                  {badgeCount > 0 && (
                    <span className="absolute top-0.5 right-0.5 min-w-[1rem] h-4 px-1 bg-red-500 rounded-full text-white text-[9px] font-bold flex items-center justify-center">
                      {badgeCount > 99 ? "99+" : badgeCount}
                    </span>
                  )}
                </button>
                {bellOpen && isExec && (
                  <div
                    className="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto bg-card border border-border rounded-xl shadow-lg z-50"
                    style={{ boxShadow: "0 8px 24px rgba(0,0,0,0.12)" }}
                  >
                    <div className="px-4 py-3 border-b border-border flex items-center justify-between">
                      <p className="text-sm font-semibold">Notifications</p>
                      <button
                        type="button"
                        onClick={() => {
                          setBellOpen(false);
                          navigate("/inventory/requests");
                        }}
                        className="text-xs text-[#C8900A] font-semibold hover:underline"
                      >
                        View all
                      </button>
                    </div>
                    {unreadList.length === 0 ? (
                      <p className="px-4 py-6 text-sm text-muted-foreground text-center">No unread notifications</p>
                    ) : (
                      <ul className="divide-y divide-border">
                        {unreadList.map(r => (
                          <li key={`${r.kind}-${r.id}`}>
                            <button
                              type="button"
                              onClick={() => {
                                if (r.kind === "correction") {
                                  void openCorrectionRequest(r.id);
                                  return;
                                }
                                if (r.kind === "supply_request") {
                                  openSupplyRequest(r.id);
                                  return;
                                }
                                setBellOpen(false);
                                if (r.kind === "edit_request") {
                                  navigate("/inventory/requests#edit-requests-section");
                                  return;
                                }
                                navigate("/inventory/requests#discount-approvals-section");
                              }}
                              className="w-full text-left px-4 py-3 hover:bg-secondary/50 transition-colors"
                            >
                              {r.kind === "correction" ? (
                                <>
                                  <p className="text-xs font-semibold text-foreground capitalize">{r.stage}</p>
                                  <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                    {r.summary ?? r.reason}
                                  </p>
                                  <p className="text-[10px] text-muted-foreground mt-1">
                                    {r.requested_by_name} · {r.created_at}
                                  </p>
                                </>
                              ) : r.kind === "supply_request" ? (
                                <>
                                  <p className="text-xs font-semibold text-foreground">
                                    Supply issue request · {r.stock_name}
                                  </p>
                                  <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                    {r.requester_name ? `${r.requester_name} · ` : ""}
                                    {r.amount} {r.unit} · {r.reason}
                                  </p>
                                  <p className="text-[10px] text-muted-foreground mt-1">
                                    {r.requested_by_name} · {r.created_at}
                                  </p>
                                </>
                              ) : r.kind === "edit_request" ? (
                                <>
                                  <p className="text-xs font-semibold text-foreground">
                                    Edit request · {formatEditRequestCategory(r.table_name)}
                                  </p>
                                  <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                    {formatEditRequestField(r.table_name, r.field_name)} · {r.reason}
                                  </p>
                                  <p className="text-[10px] text-muted-foreground mt-1">
                                    {r.requested_by_name} · {r.created_at}
                                  </p>
                                </>
                              ) : (
                                <>
                                  <p className="text-xs font-semibold text-foreground">
                                    Discount approval · {r.discount_percent}% off
                                  </p>
                                  <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                    {r.request_no} · {r.buyer_name} · Total{" "}
                                    {new Intl.NumberFormat(undefined, {
                                      style: "currency",
                                      currency: "PHP",
                                      minimumFractionDigits: 2,
                                    }).format(r.total_amount)}
                                  </p>
                                  <p className="text-[10px] text-muted-foreground mt-1">
                                    {r.cashier_label} · {r.created_at}
                                  </p>
                                </>
                              )}
                            </button>
                          </li>
                        ))}
                      </ul>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
        </header>

        <main className="pyx-scroll flex-1 min-h-0 overflow-y-auto overscroll-contain min-w-0 overflow-x-hidden">
          <div className={cn(contentChromeClass, "py-4 sm:py-6")}>
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}

/**
 * Sets header chrome for the persistent AppShell. Renders page body only (no duplicate sidebar).
 */
export function PageLayout({
  title,
  subtitle,
  actions,
  children,
  notificationCount = 0,
  contentWidth = "default",
}: PageLayoutProps) {
  const { setChrome } = usePageChrome();

  useLayoutEffect(() => {
    setChrome({ title, subtitle, actions, contentWidth, notificationCount });
  }, [title, subtitle, actions, contentWidth, notificationCount, setChrome]);

  return <>{children}</>;
}
