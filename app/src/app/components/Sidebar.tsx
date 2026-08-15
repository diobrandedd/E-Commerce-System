import { NavLink, useLocation, useNavigate } from "react-router";
import { useEffect, useState } from "react";
import { motion, useReducedMotion } from "motion/react";
import {
  LayoutDashboard, Package, Users, BarChart2, Settings, LogOut,
  Truck, DollarSign, RotateCcw, ClipboardList, Factory,
  CalendarDays, FileEdit, X, PlusCircle, ShoppingCart, ShoppingBag, UserCog, Tag, ScanLine,
} from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";
import {
  SidebarNavChevron,
  SidebarNavCollapse,
  SidebarNavToggleButton,
} from "@/app/components/layout/sidebarNavMotion";
import { useAuth, Role } from "@/app/context/AuthContext";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";
import { canGenerateHoneyBottleLabels } from "@/app/lib/honeyPermissions";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { onLiveRefresh } from "@/app/lib/liveRefresh";

interface NavLinkChild {
  label: string;
  to: string;
  /** When true, only highlight on exact path match (not child routes). */
  end?: boolean;
  badge?: number;
}

interface NavGroupChild {
  label: string;
  children: NavChild[];
  badge?: number;
}

type NavChild = NavLinkChild | NavGroupChild;

function isNavGroup(child: NavChild): child is NavGroupChild {
  return "children" in child;
}

interface NavItem {
  icon: React.ElementType;
  label: string;
  to: string;
  /** When true, only highlight on exact path match (not child routes). */
  end?: boolean;
  badge?: number;
  children?: NavChild[];
}

type NavSeparator = { kind: "separator" };
type NavEntry = NavItem | NavSeparator;

function isNavSeparator(entry: NavEntry): entry is NavSeparator {
  return "kind" in entry && entry.kind === "separator";
}

function honeyStageChildren(pendingLabels = 0): NavLinkChild[] {
  return [
    { label: "Warehouse", to: "/inventory/raw-materials/honey/warehouse" },
    {
      label: "Production",
      to: "/inventory/raw-materials/honey/production",
      badge: pendingLabels > 0 ? pendingLabels : undefined,
    },
    { label: "Packaging", to: "/inventory/raw-materials/honey/packaging" },
  ];
}

function rawMaterialsChildren(pendingLabels = 0): NavChild[] {
  return [
    { label: "Raw Honey", children: honeyStageChildren(pendingLabels), badge: pendingLabels > 0 ? pendingLabels : undefined },
    { label: "Dried Paragis", to: "/inventory/raw-materials/paragis" },
    { label: "Black Rice", to: "/inventory/raw-materials/black-rice" },
    { label: "Cacao", to: "/inventory/raw-materials/cacao" },
  ];
}

const PRODUCTS_CHILDREN: NavChild[] = [
  { label: "Honey", to: "/inventory/products/honey" },
  { label: "Honey Comb", to: "/inventory/products/honey-comb" },
  { label: "Paragis Tea", to: "/inventory/products/paragis-tea" },
  { label: "Black Rice Coffee", to: "/inventory/products/black-rice-coffee" },
  { label: "Cacao Tablets", to: "/inventory/products/cacao-tablets" },
];

function getNavItems(role: Role, dept: string, pendingCorrections: number, pendingBottleLabels: number): NavEntry[] {
  const labelBadge = pendingBottleLabels > 0 ? pendingBottleLabels : undefined;
  const inventoryChildren: NavChild[] = [
    { label: "Raw Materials", children: rawMaterialsChildren(pendingBottleLabels), badge: labelBadge },
    { label: "Packaging Materials", to: "/inventory/packaging-materials" },
    { label: "Supplies", to: "/inventory/supplies" },
    { label: "Products", children: PRODUCTS_CHILDREN },
  ];

  const inventoryChildrenExec: NavChild[] = [
    ...inventoryChildren,
    { label: "Monthly inventory", to: "/inventory/monthly-count" },
  ];

  const inventoryNav: NavItem = {
    icon: Package,
    label: "Inventory",
    to: "/inventory/raw-materials",
    badge: labelBadge,
    children: inventoryChildren,
  };

  const inventoryNavExec: NavItem = {
    icon: Package,
    label: "Inventory",
    to: "/inventory/raw-materials",
    badge: labelBadge,
    children: inventoryChildrenExec,
  };

  const honeyLabelsNav: NavItem = {
    icon: Tag,
    label: "Honey labels",
    to: "/inventory/raw-materials/honey/production?labels=1",
    badge: labelBadge,
  };

  const inventoryNavHr: NavItem = {
    icon: Package,
    label: "Inventory",
    to: "/inventory/raw-materials",
    children: inventoryChildren,
  };

  const requestsNav = (badge?: number): NavItem => ({
    icon: ClipboardList,
    label: "Requests",
    to: "/inventory/requests",
    badge: badge && badge > 0 ? badge : undefined,
  });

  const orderManagementNav = (navRole: Role): NavItem => ({
    icon: ClipboardList,
    label: "Order management",
    to: "/fulfillment/online-parcels",
    children: orderManagementChildren(navRole),
  });

  function orderManagementChildren(navRole: Role): NavChild[] {
    const onlineOrders: NavLinkChild = { label: "Online Orders", to: "/waybill/online-orders" };
    const bulkOrders: NavLinkChild = { label: "Bulk orders", to: "/sales/bulk-orders" };
    const forPacking: NavLinkChild = { label: "For packing", to: "/fulfillment/packing-materials" };
    const onlineParcels: NavLinkChild = { label: "Online parcels", to: "/fulfillment/online-parcels" };
    const partnerSold: NavLinkChild = { label: "Partner sold list", to: "/fulfillment/partner-sold-reconcile" };
    const courierSoa: NavLinkChild = { label: "Courier SOA", to: "/fulfillment/courier-soa-reconcile" };
    const historicalImport: NavLinkChild = {
      label: "Import historical orders",
      to: "/fulfillment/historical-orders-import",
    };

    switch (navRole) {
      case "executive":
        return [onlineOrders, bulkOrders, forPacking, onlineParcels, partnerSold, courierSoa, historicalImport];
      case "csr_tl":
        return [onlineOrders, onlineParcels, partnerSold, courierSoa, historicalImport, forPacking];
      case "csr_staff":
        return [onlineOrders, onlineParcels, partnerSold, courierSoa, historicalImport];
      case "prod_manager":
      case "staff":
        return [forPacking, onlineParcels, partnerSold, courierSoa, historicalImport];
      case "team_leader":
        return [forPacking];
      default:
        return [];
    }
  }

  const deliveriesItem: NavItem = {
    icon: Truck,
    label: "Deliveries",
    to: "/deliveries",
    children: [
      { label: "Add deliveries", to: "/deliveries", end: true },
      { label: "Pack scan", to: "/deliveries/pack-scan" },
      { label: "Review batches", to: "/deliveries/review" },
      { label: "Cancelled", to: "/deliveries/cancelled" },
    ],
  };

  const rtsInventory: NavLinkChild = { label: "RTS inventory", to: "/rts", end: true };
  const rtsRecords: NavLinkChild = { label: "RTS Records", to: "/rts/records" };
  const rtsDashboard: NavLinkChild = { label: "RTS Dashboard", to: "/rts/dashboard" };
  const rtsRecord: NavLinkChild = { label: "Record RTS", to: "/rts/record" };

  const rtsSwapped: NavLinkChild = { label: "Swapped parcels", to: "/rts/swapped" };
  const rtsLeaks: NavLinkChild = { label: "Leaks", to: "/rts/leaks" };
  const rtsDamaged: NavLinkChild = { label: "Damaged stock", to: "/rts/damaged" };

  const rtsNav = (children: NavLinkChild[]): NavItem => ({
    icon: RotateCcw,
    label: "RTS",
    to: "/rts",
    children,
  });

  const rtsNavFull = rtsNav([rtsInventory, rtsDamaged, rtsRecords, rtsSwapped, rtsLeaks, rtsDashboard, rtsRecord]);
  const rtsNavReadOnly = rtsNav([rtsInventory, rtsDamaged, rtsRecords, rtsSwapped, rtsLeaks, rtsDashboard]);

  const productSetupNav: NavItem = {
    icon: Factory,
    label: "Product setup",
    to: "/suppliers",
    children: [
      { label: "Supplier Master", to: "/suppliers", end: true },
      { label: "Product Costing", to: "/costing", end: true },
    ],
  };

  // Staff see raw materials + finished products for their own department only
  // (read-only — server enforces dept scope + hides pricing).
  const staffInventoryChildrenByDept: Record<string, NavChild[]> = {
    honey: [
      { label: "Raw Honey", children: honeyStageChildren(pendingBottleLabels), badge: labelBadge },
      { label: "Honey (products)", to: "/inventory/products/honey" },
    ],
    paragis: [
      { label: "Dried Paragis", to: "/inventory/raw-materials/paragis" },
      { label: "Paragis Tea (products)", to: "/inventory/products/paragis-tea" },
    ],
    cacao_brc: [
      { label: "Black Rice", to: "/inventory/raw-materials/black-rice" },
      { label: "Cacao", to: "/inventory/raw-materials/cacao" },
      { label: "Black Rice Coffee", to: "/inventory/products/black-rice-coffee" },
      { label: "Cacao Tablets", to: "/inventory/products/cacao-tablets" },
    ],
  };
  const staffInventoryNav: NavItem = {
    icon: Package,
    label: "Inventory",
    to: "/inventory/raw-materials",
    children: staffInventoryChildrenByDept[dept] ?? [
      { label: "Raw Materials", to: "/inventory/raw-materials" },
    ],
  };

  const barcodeVerifyNav: NavItem = {
    icon: ScanLine,
    label: "Verify barcode",
    to: "/verify-barcode",
  };

  switch (role) {
    case "executive":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        inventoryNavExec,
        { icon: ShoppingBag, label: "Sales", to: "/sales", end: true },
        orderManagementNav(role),
        deliveriesItem,
        rtsNavFull,
        productSetupNav,
        { kind: "separator" },
        { icon: BarChart2, label: "Reports", to: "/reports" },
        requestsNav(pendingCorrections),
        { icon: DollarSign, label: "AR Credits", to: "/ar" },
        { icon: Users, label: "Staff Management", to: "/staff" },
        { icon: Settings, label: "System Settings", to: "/settings" },
      ];
    case "prod_manager":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        inventoryNav,
        requestsNav(),
        { icon: BarChart2, label: "Reports", to: "/reports" },
        { icon: ShoppingBag, label: "Sales", to: "/sales", end: true },
        { icon: ClipboardList, label: "My AR Credits", to: "/ar" },
        orderManagementNav(role),
        deliveriesItem,
        rtsNavFull,
      ];
    case "hr":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        inventoryNavHr,
        requestsNav(),
        rtsNavReadOnly,
      ];
    case "team_leader":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        { icon: PlusCircle, label: "Stock Input", to: "/stock-input" },
        staffInventoryNav,
        { icon: CalendarDays, label: "Daily Count", to: "/daily-count" },
        { icon: ClipboardList, label: "Beginning Inventory", to: "/beginning-inventory" },
        { icon: FileEdit, label: "Edit Requests", to: "/edit-requests" },
        { icon: ShoppingCart, label: "My AR Credits", to: "/ar" },
        orderManagementNav(role),
        rtsNavReadOnly,
      ];
    case "csr_tl":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        honeyLabelsNav,
        orderManagementNav(role),
        rtsNavFull,
      ];
    case "staff":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        { icon: PlusCircle, label: "Stock Input", to: "/stock-input" },
        { icon: CalendarDays, label: "Daily Count", to: "/daily-count" },
        staffInventoryNav,
        deliveriesItem,
        orderManagementNav(role),
        rtsNavReadOnly,
        { icon: FileEdit, label: "Edit Requests", to: "/edit-requests" },
        { icon: ClipboardList, label: "My AR Credits", to: "/ar" },
      ];
    case "csr_staff":
      return [
        { icon: LayoutDashboard, label: "Dashboard", to: "/dashboard" },
        barcodeVerifyNav,
        honeyLabelsNav,
        orderManagementNav(role),
        rtsNavFull,
      ];
    default:
      return [];
  }
}

const ROLE_LABELS: Record<Role, string> = {
  executive: "Executive",
  prod_manager: "Production Manager",
  hr: "HR",
  team_leader: "Team Leader",
  staff: "Staff",
  csr_staff: "CSR Staff",
  csr_tl: "CSR Team Leader",
  pos: "POS Terminal",
};

const ROLE_BADGE_COLORS: Record<Role, string> = {
  executive: "bg-[#C8900A]/20 text-[#C8900A]",
  prod_manager: "bg-blue-500/20 text-blue-400",
  hr: "bg-teal-500/20 text-teal-300",
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

function groupKey(parent: string, label: string) {
  return `${parent}>${label}`;
}

const RAW_HONEY_WAREHOUSE = "/inventory/raw-materials/honey/warehouse";

function SidebarNestedChildren({
  children,
  parentKey,
  expandedGroups,
  toggleGroup,
  ensureExpanded,
  navigate,
  onClose,
}: {
  children: NavChild[];
  parentKey: string;
  expandedGroups: Set<string>;
  toggleGroup: (key: string) => void;
  ensureExpanded: (key: string) => void;
  navigate: (to: string) => void;
  onClose: () => void;
}) {
  const reduceMotion = useReducedMotion();

  return (
    <>
      {children.map(child => {
        if (isNavGroup(child)) {
          const key = groupKey(parentKey, child.label);
          const subExpanded = expandedGroups.has(key);
          const isRawHoney = child.label === "Raw Honey";
          const toggleClass = `w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-colors duration-150 cursor-pointer ${
            isRawHoney
              ? "text-white/80 hover:text-white hover:bg-white/8 font-medium"
              : "text-white/50 hover:text-white hover:bg-white/8"
          }`;
          const groupBadge =
            child.badge != null && child.badge > 0 ? (
              <span className="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">
                {child.badge > 99 ? "99+" : child.badge}
              </span>
            ) : null;
          return (
            <div key={key}>
              {isRawHoney ? (
                <motion.button
                  type="button"
                  whileTap={reduceMotion ? undefined : { scale: 0.98 }}
                  onClick={() => {
                    navigate(RAW_HONEY_WAREHOUSE);
                    ensureExpanded(key);
                    onClose();
                  }}
                  className={toggleClass}
                >
                  <span className="flex-1 text-left">{child.label}</span>
                  {groupBadge}
                  <SidebarNavChevron expanded={subExpanded} size={12} />
                </motion.button>
              ) : (
                <SidebarNavToggleButton
                  onClick={() => toggleGroup(key)}
                  className={toggleClass}
                >
                  <span className="flex-1 text-left">{child.label}</span>
                  {groupBadge}
                  <SidebarNavChevron expanded={subExpanded} size={12} />
                </SidebarNavToggleButton>
              )}
              <SidebarNavCollapse open={subExpanded} className="ml-4 mt-0.5">
                <div className="space-y-0.5 pb-0.5">
                  <SidebarNestedChildren
                    children={child.children}
                    parentKey={key}
                    expandedGroups={expandedGroups}
                    toggleGroup={toggleGroup}
                    ensureExpanded={ensureExpanded}
                    navigate={navigate}
                    onClose={onClose}
                  />
                </div>
              </SidebarNavCollapse>
            </div>
          );
        }
        return (
          <NavLink
            key={child.to}
            to={child.to}
            end={child.end}
            onClick={onClose}
            className={({ isActive }) =>
              `flex items-center gap-2 px-3 py-2 rounded-lg text-xs transition-all duration-150 cursor-pointer ${
                isActive ? "bg-[#C8900A] text-white font-semibold" : "text-white/50 hover:text-white hover:bg-white/8"
              }`
            }
          >
            <span className="flex-1">{child.label}</span>
            {child.badge != null && child.badge > 0 && (
              <span className="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">
                {child.badge > 99 ? "99+" : child.badge}
              </span>
            )}
          </NavLink>
        );
      })}
    </>
  );
}

export function Sidebar({ open, onClose }: SidebarProps) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(() => {
    const next = new Set<string>(["Inventory"]);
    if (location.pathname.includes("/inventory/raw-materials")) {
      next.add("Inventory>Raw Materials");
    }
    if (location.pathname.includes("/inventory/products")) {
      next.add("Inventory>Products");
      if (location.pathname.includes("/inventory/products/honey")) {
        next.add("Inventory>Products>Honey");
      }
    }
    if (location.pathname.includes("/inventory/raw-materials/honey")) {
      next.add("Inventory>Raw Materials>Raw Honey");
    }
    return next;
  });
  const [signingOut, setSigningOut] = useState(false);
  const { unreadCount: pendingCorrections } = useCorrectionNotifications();
  const [pendingBottleLabels, setPendingBottleLabels] = useState(0);
  const canSeeLabelBadge = canGenerateHoneyBottleLabels(user?.role, user?.dept);

  useEffect(() => {
    if (!canSeeLabelBadge) {
      setPendingBottleLabels(0);
      return;
    }
    let cancelled = false;
    const load = async () => {
      try {
        const res = await bottleBarcodesApi.pendingCount();
        if (!cancelled) setPendingBottleLabels(res.count);
      } catch {
        if (!cancelled) setPendingBottleLabels(0);
      }
    };
    void load();
    const timer = window.setInterval(() => void load(), 15000);
    const off = onLiveRefresh(() => void load());
    return () => {
      cancelled = true;
      window.clearInterval(timer);
      off();
    };
  }, [canSeeLabelBadge, location.pathname]);

  useEffect(() => {
    if (location.pathname.includes("/inventory") && !location.pathname.startsWith("/inventory/requests")) {
      setExpandedGroups(prev => {
        const next = new Set(prev);
        next.add("Inventory");
        if (location.pathname.includes("/inventory/raw-materials")) {
          next.add("Inventory>Raw Materials");
        }
        if (location.pathname.includes("/inventory/products")) {
          next.add("Inventory>Products");
          if (location.pathname.includes("/inventory/products/honey")) {
            next.add("Inventory>Products>Honey");
          }
        }
        if (location.pathname.includes("/inventory/raw-materials/honey")) {
          next.add("Inventory>Raw Materials>Raw Honey");
        }
        return next;
      });
    }
    if (
      location.pathname.startsWith("/fulfillment") ||
      location.pathname.startsWith("/waybill") ||
      location.pathname.startsWith("/sales/bulk")
    ) {
      setExpandedGroups(prev => {
        const next = new Set(prev);
        next.add("Order management");
        return next;
      });
    }
    if (location.pathname.startsWith("/deliveries")) {
      setExpandedGroups(prev => {
        const next = new Set(prev);
        next.add("Deliveries");
        return next;
      });
    }
    if (location.pathname.startsWith("/rts")) {
      setExpandedGroups(prev => {
        const next = new Set(prev);
        next.add("RTS");
        return next;
      });
    }
    if (location.pathname.startsWith("/suppliers") || location.pathname.startsWith("/costing")) {
      setExpandedGroups(prev => {
        const next = new Set(prev);
        next.add("Product setup");
        return next;
      });
    }
  }, [location.pathname]);

  const toggleGroup = (key: string) => {
    setExpandedGroups(prev => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

  const ensureExpanded = (key: string) => {
    setExpandedGroups(prev => {
      if (prev.has(key)) return prev;
      const next = new Set(prev);
      next.add(key);
      return next;
    });
  };

  if (!user) return null;
  const navItems = getNavItems(user.role, user.dept, pendingCorrections, pendingBottleLabels);
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
        className={`fixed lg:relative inset-y-0 left-0 z-30 w-64 shrink-0 h-dvh flex flex-col min-h-0 bg-[#2a1f00] transition-transform duration-300 ${
          open ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
        }`}
      >
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

        <div className="px-5 pt-4 pb-2">
          <span className={`inline-block text-[10px] font-semibold tracking-widest uppercase px-2.5 py-1 rounded-full ${ROLE_BADGE_COLORS[user.role]}`}>
            {ROLE_LABELS[user.role]}
          </span>
        </div>

        <nav className="pyx-scroll-dark flex-1 min-h-0 px-3 py-2 space-y-0.5 overflow-y-auto overscroll-contain">
          {navItems.map((entry, index) => {
            if (isNavSeparator(entry)) {
              return (
                <div key={`nav-sep-${index}`} className="my-2 mx-3 border-t border-white/10" aria-hidden />
              );
            }
            const { icon: Icon, label, to, children, badge, end } = entry;
            if (children) {
              const isExpanded = expandedGroups.has(label);
              return (
                <div key={label}>
                  <SidebarNavToggleButton
                    onClick={() => toggleGroup(label)}
                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/8 transition-colors duration-150 cursor-pointer"
                  >
                    <Icon size={17} />
                    <span className="flex-1 text-left">{label}</span>
                    {badge != null && badge > 0 && (
                      <span className="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">
                        {badge > 99 ? "99+" : badge}
                      </span>
                    )}
                    <SidebarNavChevron expanded={isExpanded} size={14} />
                  </SidebarNavToggleButton>
                  <SidebarNavCollapse open={isExpanded} className="ml-8 mt-0.5">
                    <div className="space-y-0.5 pb-0.5">
                      <SidebarNestedChildren
                        children={children}
                        parentKey={label}
                        expandedGroups={expandedGroups}
                        toggleGroup={toggleGroup}
                        ensureExpanded={ensureExpanded}
                        navigate={navigate}
                        onClose={onClose}
                      />
                    </div>
                  </SidebarNavCollapse>
                </div>
              );
            }
            return (
              <NavLink
                key={to}
                to={to}
                end={end}
                onClick={onClose}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 cursor-pointer ${
                    isActive ? "bg-[#C8900A] text-white font-semibold" : "text-white/60 hover:text-white hover:bg-white/8"
                  }`
                }
              >
                <Icon size={17} />
                <span className="flex-1">{label}</span>
                {badge != null && badge > 0 && (
                  <span className="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">
                    {badge > 99 ? "99+" : badge}
                  </span>
                )}
              </NavLink>
            );
          })}
        </nav>

        <div className="shrink-0 px-3 py-4 border-t border-white/10">
          <div className="flex items-center gap-3 px-3 py-2 mb-1">
            <div className="w-8 h-8 rounded-full bg-[#C8900A] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
              {initials}
            </div>
            <div className="min-w-0">
              <p className="text-white text-sm font-medium truncate">{user.name}</p>
            </div>
          </div>
          <NavLink
            to="/account"
            onClick={onClose}
            className={({ isActive }) =>
              `w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 cursor-pointer ${
                isActive ? "bg-[#C8900A] text-white font-semibold" : "text-white/50 hover:text-white hover:bg-white/8"
              }`
            }
          >
            <UserCog size={17} />
            <span className="flex-1">My Account</span>
          </NavLink>
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
