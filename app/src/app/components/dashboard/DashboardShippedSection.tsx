import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { PackageCheck, RotateCcw, Truck, XCircle } from "lucide-react";
import {
  AppErrorBanner,
  appFeedbackEase,
} from "@/app/components/layout/appFeedbackMotion";
import { DashboardStatCard } from "@/app/components/dashboard/DashboardStatCard";
import { DashboardSectionShell } from "@/app/components/dashboard/DashboardSectionShell";
import { Button } from "@/app/components/ui/button";
import { apiGet, ApiError } from "@/app/lib/api";

export type FulfillmentPeriod = "day" | "week" | "month";

interface FulfillmentSummary {
  filter: { period: string; from: string | null; to: string | null; label: string };
  shipped: number;
  packing: number;
  rts_opened: number;
  cancelled_or_hold: number;
}

const PERIOD_OPTIONS: { value: FulfillmentPeriod; label: string }[] = [
  { value: "day", label: "Today" },
  { value: "week", label: "This week" },
  { value: "month", label: "This month" },
];

export function DashboardShippedSection() {
  const [period, setPeriod] = useState<FulfillmentPeriod>("day");
  const [data, setData] = useState<FulfillmentSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const reduceMotion = useReducedMotion();

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<FulfillmentSummary>("dashboard.php", "shipped_summary", { period });
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load online fulfillment summary.");
    } finally {
      setLoading(false);
    }
  }, [period]);

  useEffect(() => {
    load();
  }, [load]);

  const val = (n: number | undefined) => (loading ? "…" : String(n ?? 0));
  const periodHint = data?.filter.label?.toLowerCase() ?? "this period";

  return (
    <DashboardSectionShell
      title="Online fulfillment"
      description={`Executive snapshot · ${data?.filter.label ?? "Today"}`}
      index={0}
      action={
        <div className="flex flex-col sm:flex-row items-center gap-2">
          <div className="flex flex-wrap justify-center gap-1.5">
            {PERIOD_OPTIONS.map(opt => (
              <Button
                key={opt.value}
                type="button"
                size="sm"
                variant={period === opt.value ? "default" : "outline"}
                onClick={() => setPeriod(opt.value)}
                disabled={loading && period === opt.value}
                className={period === opt.value ? "bg-[#C8900A] hover:bg-[#C8900A]/90 min-w-[5.5rem]" : "min-w-[5.5rem]"}
              >
                {opt.label}
              </Button>
            ))}
          </div>
          <Link
            to="/fulfillment/online-parcels"
            className="text-xs font-medium text-[#C8900A] hover:underline"
          >
            View parcels →
          </Link>
        </div>
      }
    >
      <div className="px-4 sm:px-6 py-5 sm:py-6">
        <AppErrorBanner message={error} className="mb-4" />

        <AnimatePresence mode="wait">
          <motion.div
            key={period}
            className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4"
            initial={reduceMotion ? false : { opacity: 0.7 }}
            animate={{ opacity: 1 }}
            transition={appFeedbackEase}
          >
            <DashboardStatCard
              icon={Truck}
              label="Shipped"
              value={val(data?.shipped)}
              hint={`Marked shipped · ${periodHint}`}
              index={0}
              centered
              to="/fulfillment/online-parcels"
            />
            <DashboardStatCard
              icon={PackageCheck}
              label="For packing"
              value={val(data?.packing)}
              hint="Awaiting ship (now)"
              index={0}
              centered
              to="/fulfillment/packing-materials"
              emphasize={!loading && (data?.packing ?? 0) > 0}
            />
            <DashboardStatCard
              icon={RotateCcw}
              label="RTS opened"
              value={val(data?.rts_opened)}
              hint={`New return cases · ${periodHint}`}
              index={0}
              centered
              to="/rts"
            />
            <DashboardStatCard
              icon={XCircle}
              label="Cancelled / on hold"
              value={val(data?.cancelled_or_hold)}
              hint="Courier cancel or paused (now)"
              index={0}
              centered
              to="/fulfillment/online-parcels"
              emphasize={!loading && (data?.cancelled_or_hold ?? 0) > 0}
            />
          </motion.div>
        </AnimatePresence>
      </div>
    </DashboardSectionShell>
  );
}
