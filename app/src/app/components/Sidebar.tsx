import { NavLink, useNavigate } from "react-router";
import { useState } from "react";
import {
  LayoutDashboard, Package, Users, BarChart2, Settings, LogOut,
  ChevronDown, Truck, DollarSign, RotateCcw, ShoppingCart, ClipboardList,
  CalendarDays, FileEdit, X, PlusCircle,
} from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";
import { useAuth, Role } from "@/app/context/AuthContext";

interface NavItem {
  icon: React.ElementType;
  label: string;
  to: string;
  children?: { label: string; to: string }[];
}

function getNavItems(role: Role, dept: string): NavItem[] {
  switch (role) {
    case "executive":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        {
          icon: Package, label: "Inventory", to: "/inventory",
          children: [
            { label: "Honey", to: "/inventory/honey" },
            { label: "Paragis", to: "/inventory/paragis" },
            { label: "Cacao / BRC", to: "/inventory/cacao-brc" },
          ],
        },
        { icon: Users, label: "Staff Management", to: "/staff" },
        { icon: BarChart2, label: "Reports", to: "/reports" },
        { icon: Truck, label: "Supplier Master", to: "/suppliers" },
        { icon: DollarSign, label: "Product Costing", to: "/costing" },
        { icon: ClipboardList, label: "AR Credits", to: "/ar" },
        { icon: RotateCcw, label: "RTS Overview", to: "/rts" },
        { icon: Settings, label: "System Settings", to: "/settings" },
      ];
    case "prod_manager":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        {
          icon: Package, label: "Inventory", to: "/inventory",
          children: [
            { label: "Honey", to: "/inventory/honey" },
            { label: "Paragis", to: "/inventory/paragis" },
            { label: "Cacao / BRC", to: "/inventory/cacao-brc" },
          ],
        },
        { icon: BarChart2, label: "Reports", to: "/reports" },
        { icon: ClipboardList, label: "My AR Credits", to: "/ar" },
        { icon: RotateCcw, label: "RTS Overview", to: "/rts" },
      ];
    case "team_leader":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        { icon: PlusCircle, label: "Stock Input", to: "/stock-input" },
        { icon: Package, label: "Inventory", to: `/inventory/${dept}` },
        { icon: CalendarDays, label: "Daily Count", to: "/daily-count" },
        { icon: ClipboardList, label: "Beginning Inventory", to: "/beginning-inventory" },
        { icon: FileEdit, label: "Edit Requests", to: "/edit-requests" },
        { icon: ShoppingCart, label: "My AR Credits", to: "/ar" },
      ];
    case "csr_tl":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        { icon: RotateCcw, label: "RTS Records", to: "/rts" },
      ];
    case "staff":
      return [
        { icon: PlusCircle, label: "Stock Input", to: "/stock-input" },
        { icon: CalendarDays, label: "Daily Count", to: "/daily-count" },
        { icon: FileEdit, label: "Edit Requests", to: "/edit-requests" },
        { icon: ShoppingCart, label: "My AR Credits", to: "/ar" },
      ];
    case "csr_staff":
      return [
        { icon: RotateCcw, label: "Record RTS", to: "/rts/record" },
      ];
    default:
      return [];
  }
}

const ROLE_LABELS: Record<Role, string> = {
  executive: "Executive",
  prod_manager: "Production Manager",
  team_leader: "Team Leader",
  staff: "Staff",
  csr_staff: "CSR Staff",
  csr_tl: "CSR Team Leader",
  pos: "POS Terminal",
};

const ROLE_BADGE_COLORS: Record<Role, string> = {
  executive: "bg-[#C8900A]/20 text-[#C8900A]",
  prod_manager: "bg-blue-500/20 text-blue-400",
  team_leader: "bg-green-500/20 text-green-400",
  staff: "bg-purple-500/20 text-purple-400",
  csr_staff: "bg-pink-500/20 text-pink-400",
  csr_tl: "bg-pink-500/20 text-pink-400",
  pos: "bg-gray-500/20 text-gray-300",
};

interface SidebarProps {
  open: boolean;
  onClose: () => void;
}

export function Sidebar({ open, onClose }: SidebarProps) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [expandedGroup, setExpandedGroup] = useState<string | null>("Inventory");
  const [signingOut, setSigningOut] = useState(false);

  if (!user) return null;
  const navItems = getNavItems(user.role, user.dept);
  const initials = user.name.split(" ").map(w => w[0]).join("").toUpperCase().slice(0, 2);

  const handleLogout = async () => {
    if (signingOut) return;
    setSigningOut(true);
    try {
      await logout();
    } finally {
      setSigningOut(false);
      navigate("/", { replace: true });
    }
  };

  return (
    <>
      {open && (
        <div className="fixed inset-0 bg-black/30 z-20 lg:hidden" onClick={onClose} />
      )}
      <aside
        className={`fixed lg:static inset-y-0 left-0 z-30 w-64 flex flex-col bg-[#2a1f00] transition-transform duration-300 ${
          open ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
        }`}
      >
        {/* Logo */}
        <div className="flex items-center gap-3 px-5 py-5 border-b border-white/10">
          <img src={pyxLogo} alt="Pyx" className="w-10 h-10 object-contain flex-shrink-0" />
          <div className="min-w-0">
            <p className="text-white text-sm font-bold leading-tight">Pyx Tracker</p>
            <p className="text-white/40 text-[10px] tracking-wide uppercase truncate">Management System</p>
          </div>
          <button className="ml-auto lg:hidden text-white/50 hover:text-white" onClick={onClose}>
            <X size={18} />
          </button>
        </div>

        {/* Role badge */}
        <div className="px-5 pt-4 pb-2">
          <span className={`inline-block text-[10px] font-semibold tracking-widest uppercase px-2.5 py-1 rounded-full ${ROLE_BADGE_COLORS[user.role]}`}>
            {ROLE_LABELS[user.role]}
          </span>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 py-2 space-y-0.5 overflow-y-auto">
          {navItems.map(({ icon: Icon, label, to, children }) => {
            if (children) {
              const isExpanded = expandedGroup === label;
              return (
                <div key={label}>
                  <button
                    onClick={() => setExpandedGroup(isExpanded ? null : label)}
                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/8 transition-all duration-150"
                  >
                    <Icon size={17} />
                    <span className="flex-1 text-left">{label}</span>
                    <ChevronDown size={14} className={`transition-transform duration-200 ${isExpanded ? "rotate-180" : ""}`} />
                  </button>
                  {isExpanded && (
                    <div className="ml-8 mt-0.5 space-y-0.5">
                      {children.map(child => (
                        <NavLink
                          key={child.to}
                          to={child.to}
                          onClick={onClose}
                          className={({ isActive }) =>
                            `block px-3 py-2 rounded-lg text-xs transition-all duration-150 ${
                              isActive ? "bg-[#C8900A] text-white font-semibold" : "text-white/50 hover:text-white hover:bg-white/8"
                            }`
                          }
                        >
                          {child.label}
                        </NavLink>
                      ))}
                    </div>
                  )}
                </div>
              );
            }
            return (
              <NavLink
                key={to}
                to={to}
                onClick={onClose}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 ${
                    isActive ? "bg-[#C8900A] text-white font-semibold" : "text-white/60 hover:text-white hover:bg-white/8"
                  }`
                }
              >
                <Icon size={17} />
                {label}
              </NavLink>
            );
          })}
        </nav>

        {/* User + logout */}
        <div className="px-3 py-4 border-t border-white/10">
          <div className="flex items-center gap-3 px-3 py-2 mb-1">
            <div className="w-8 h-8 rounded-full bg-[#C8900A] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
              {initials}
            </div>
            <div className="min-w-0">
              <p className="text-white text-sm font-medium truncate">{user.name}</p>
              <p className="text-white/40 text-xs truncate">{user.email}</p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            disabled={signingOut}
            className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/50 hover:text-white hover:bg-white/8 transition-all duration-150 disabled:opacity-60"
          >
            <LogOut size={17} />
            {signingOut ? "Signing out…" : "Sign out"}
          </button>
        </div>
      </aside>
    </>
  );
}
