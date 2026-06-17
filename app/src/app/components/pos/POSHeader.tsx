import { LogOut, ArrowLeft, BookOpen } from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";

export function POSHeader({
  title,
  subtitle,
  onBack,
  onExit,
  onGuide,
}: {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  onExit: () => void;
  onGuide?: () => void;
}) {
  return (
    <header className="bg-[#2a1f00] px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
      <div className="flex items-center gap-3 min-w-0">
        {onBack && (
          <button
            type="button"
            onClick={onBack}
            data-tour="pos-header-back"
            className="text-white/70 hover:text-white p-1 -ml-1"
            aria-label="Back"
          >
            <ArrowLeft size={20} />
          </button>
        )}
        <img src={pyxLogo} alt="Pyx" className="w-9 h-9 object-contain flex-shrink-0" />
        <div className="min-w-0">
          <p className="text-white text-sm font-bold leading-tight truncate">{title}</p>
          {subtitle && <p className="text-white/40 text-[10px] truncate">{subtitle}</p>}
        </div>
      </div>
      <div className="flex items-center gap-2 flex-shrink-0">
        {onGuide && (
          <button
            type="button"
            onClick={onGuide}
            className="inline-flex items-center gap-1 text-white/60 hover:text-white text-xs font-medium px-2 py-1 rounded-lg border border-white/20 hover:border-white/40 transition-colors"
          >
            <BookOpen size={13} /> Guide
          </button>
        )}
        <button
          type="button"
          onClick={onExit}
          data-tour="pos-header-exit"
          className="flex items-center gap-1.5 text-white/50 hover:text-white text-sm transition-colors"
        >
          <LogOut size={16} /> Exit
        </button>
      </div>
    </header>
  );
}
