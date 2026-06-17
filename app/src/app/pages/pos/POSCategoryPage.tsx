import { useState } from "react";
import { useNavigate } from "react-router";
import { motion } from "motion/react";
import { useAuth } from "@/app/context/AuthContext";
import { POSHeader } from "@/app/components/pos/POSHeader";
import { POSGuideModal } from "@/app/components/pos/POSGuideModal";
import { POSPageTransition } from "@/app/components/pos/posMotion";
import { runPosCategoryTour } from "@/app/components/tour/guides/posTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";

const CATEGORIES = [
  { key: "honey", label: "Honey", active: true, to: "/pos/honey" },
  { key: "paragis", label: "Paragis Tea", active: true, to: "/pos/paragis" },
  { key: "brc", label: "Black Rice Coffee", active: true, to: "/pos/brc" },
  { key: "cacao", label: "Jar Cacao", active: true, to: "/pos/cacao" },
] as const;

export function POSCategoryPage() {
  const navigate = useNavigate();
  const { logout } = useAuth();
  const [guideOpen, setGuideOpen] = useState(false);

  const handleExit = async () => {
    await logout();
    navigate("/", { replace: true });
  };

  return (
    <POSPageTransition className="min-h-screen bg-background flex flex-col" style={{ fontFamily: "'DM Sans', sans-serif" }}>
      <POSHeader title="Pyx POS" subtitle="Select product" onExit={handleExit} onGuide={() => setGuideOpen(true)} />

      <main className="flex-1 flex flex-col items-center justify-center p-4 sm:p-6">
        <p className="text-sm text-muted-foreground mb-4 text-center">Tap a product category</p>
        <div className="grid grid-cols-2 gap-2 sm:gap-3 max-w-md w-full mx-auto" data-tour="pos-categories">
          {CATEGORIES.map((cat, i) => (
            <motion.button
              key={cat.key}
              type="button"
              disabled={!cat.active}
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.08 + i * 0.06, duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
              whileTap={cat.active ? { scale: 0.96 } : undefined}
              whileHover={cat.active ? { scale: 1.02, y: -2 } : undefined}
              onClick={() => {
                if ("to" in cat && cat.to) navigate(cat.to);
              }}
              className={`rounded-xl border p-4 sm:p-5 text-left min-h-[4.5rem] flex flex-col justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C8900A] focus-visible:ring-offset-2 ${
                cat.active
                  ? "bg-card border-[#C8900A]/40 hover:border-[#C8900A] hover:bg-black/[0.04] hover:shadow-md"
                  : "bg-secondary/30 border-border opacity-60 cursor-not-allowed"
              }`}
              style={cat.active ? { boxShadow: "0 1px 4px rgba(0,0,0,0.05)" } : undefined}
            >
              <p className="text-sm sm:text-base font-bold text-foreground">{cat.label}</p>
              {!cat.active && <p className="text-xs text-muted-foreground mt-1">Coming soon</p>}
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
