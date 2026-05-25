import { useState, useRef, useEffect, ReactNode } from "react";
import { useNavigate } from "react-router";
import { Menu, Bell } from "lucide-react";
import { Sidebar } from "./Sidebar";
import { useAuth } from "@/app/context/AuthContext";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";

interface PageLayoutProps {
  title: string;
  subtitle?: string;
  actions?: ReactNode;
  children: ReactNode;
  notificationCount?: number;
}

export function PageLayout({ title, subtitle, actions, children, notificationCount = 0 }: PageLayoutProps) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [bellOpen, setBellOpen] = useState(false);
  const bellRef = useRef<HTMLDivElement>(null);
  const { user } = useAuth();
  const navigate = useNavigate();
  const isExec = user?.role === "executive";
  const { unreadCount, unreadList, markViewed, refresh: refreshNotifications } = useCorrectionNotifications();

  const badgeCount = isExec ? unreadCount : notificationCount;

  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (bellRef.current && !bellRef.current.contains(e.target as Node)) setBellOpen(false);
    };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const openRequest = async (id: number) => {
    setBellOpen(false);
    await markViewed(id);
    navigate(`/inventory/requests?request=${id}`);
  };

  return (
    <div className="min-h-screen flex bg-background" style={{ fontFamily: "'DM Sans', sans-serif" }}>
      <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="flex-1 flex flex-col min-w-0">
        <header
          className="sticky top-0 z-10 bg-card border-b border-border flex items-center justify-between px-6 py-4"
          style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
        >
          <div className="flex items-center gap-3">
            <button
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
          <div className="flex items-center gap-3">
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
                    <p className="text-sm font-semibold">Correction requests</p>
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
                    <p className="px-4 py-6 text-sm text-muted-foreground text-center">No unread requests</p>
                  ) : (
                    <ul className="divide-y divide-border">
                      {unreadList.map(r => (
                        <li key={r.id}>
                          <button
                            type="button"
                            onClick={() => openRequest(r.id)}
                            className="w-full text-left px-4 py-3 hover:bg-secondary/50 transition-colors"
                          >
                            <p className="text-xs font-semibold text-foreground capitalize">{r.stage}</p>
                            <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                              {r.summary ?? r.reason}
                            </p>
                            <p className="text-[10px] text-muted-foreground mt-1">
                              {r.requested_by_name} · {r.created_at}
                            </p>
                          </button>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="flex-1 p-6 overflow-y-auto">{children}</main>
      </div>
    </div>
  );
}
