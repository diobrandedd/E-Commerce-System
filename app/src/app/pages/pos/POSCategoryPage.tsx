import { useState } from "react";
import { useNavigate } from "react-router";
import { motion, useReducedMotion } from "motion/react";
import { useAuth } from "@/app/context/AuthContext";
import { POSHeader } from "@/app/components/pos/POSHeader";
import { POSGuideModal } from "@/app/components/pos/POSGuideModal";
import { POSPageTransition } from "@/app/components/pos/posMotion";
import { runPosCategoryTour } from "@/app/components/tour/guides/posTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { posSysImgUrl } from "@/app/lib/posAssets";
import { cn } from "@/app/components/ui/utils";

const CATEGORIES = [
  { key: "honey", label: "Honey", image: "honey.png", active: true, to: "/pos/honey" },
  { key: "paragis", label: "Paragis Tea", image: "paragistea.png", active: true, to: "/pos/paragis" },
  { key: "brc", label: "Black Rice Coffee", image: "brc.png", active: true, to: "/pos/brc" },
  { key: "cacao", label: "Jar Cacao", image: "jarcacao.png", active: true, to: "/pos/cacao" },
  {
    key: "honey-comb",
    label: "Honey Comb",
    image: "honeycomb.png",
    active: true,
    to: "/pos/honey-comb",
    wide: true,
  },
] as const;

export function POSCategoryPage() {
  const navigate = useNavigate();
  const { logout } = useAuth();
  const [guideOpen, setGuideOpen] = useState(false);
  const reduceMotion = useReducedMotion();

  const handleExit = async () => {
    await logout();
    navigate("/", { replace: true });
  };

  return (
    <POSPageTransition className="min-h-screen bg-background flex flex-col" style={{ fontFamily: "'DM Sans', sans-serif" }}>
      <POSHeader title="Pyx POS" subtitle="Select product" onExit={handleExit} onGuide={() => setGuideOpen(true)} />

      <main className="flex-1 flex flex-col items-center justify-center p-4 sm:p-6">
        <p className="text-sm text-muted-foreground mb-5 text-center">Tap a product category</p>
        <div
          className="grid grid-cols-2 gap-3 sm:gap-4 max-w-lg w-full mx-auto auto-rows-fr"
          data-tour="pos-categories"
        >
          {CATEGORIES.map((cat, i) => (
            <motion.button
              key={cat.key}
              type="button"
              disabled={!cat.active}
              initial={reduceMotion ? false : { opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.06 + i * 0.05, duration: 0.32, ease: [0.22, 1, 0.36, 1] }}
              whileTap={cat.active && !reduceMotion ? { scale: 0.97 } : undefined}
              whileHover={cat.active && !reduceMotion ? { scale: 1.02, y: -3 } : undefined}
              onClick={() => {
                if ("to" in cat && cat.to) navigate(cat.to);
              }}
              className={cn(
                "group relative overflow-hidden rounded-2xl border text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C8900A] focus-visible:ring-offset-2",
                "flex flex-col min-h-[9.5rem] sm:min-h-[11rem]",
                "wide" in cat && cat.wide && "col-span-2 sm:min-h-[10rem]",
                cat.active
                  ? "border-[#C8900A]/35 bg-card hover:border-[#C8900A] hover:shadow-lg"
                  : "bg-secondary/30 border-border opacity-60 cursor-not-allowed",
              )}
              style={cat.active ? { boxShadow: "0 2px 12px rgba(0,0,0,0.06)" } : undefined}
            >
              <div className="relative flex-1 min-h-[6.5rem] sm:min-h-[7.5rem] bg-gradient-to-b from-[#faf6ef] to-[#f3ebe0] overflow-hidden">
                <img
                  src={posSysImgUrl(cat.image)}
                  alt=""
                  className={cn(
                    "absolute inset-0 w-full h-full object-cover transition-transform duration-300",
                    cat.active && !reduceMotion && "group-hover:scale-[1.03]",
                    cat.key === "honey" && "object-[center_35%]",
                    cat.key === "honey-comb" && "object-[center_40%]",
                  )}
                  loading={i < 2 ? "eager" : "lazy"}
                  decoding="async"
                />
                <div className="absolute inset-x-0 bottom-0 h-14 bg-gradient-to-t from-black/55 via-black/25 to-transparent pointer-events-none" />
              </div>
              <div className="absolute inset-x-0 bottom-0 px-3 py-2.5 sm:px-4 sm:py-3">
                <p className="text-sm sm:text-base font-bold text-white drop-shadow-sm">{cat.label}</p>
                {!cat.active && <p className="text-xs text-white/80 mt-0.5">Coming soon</p>}
              </div>
            </motion.button>
          ))}
        </div>
      </main>

      <POSGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        context="category"
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runPosCategoryTour());
        }}
      />
    </POSPageTransition>
  );
}
