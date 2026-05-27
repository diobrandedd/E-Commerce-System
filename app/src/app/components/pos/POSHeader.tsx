import { LogOut, ArrowLeft } from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";

export function POSHeader({
  title,
  subtitle,
  onBack,
  onExit,
}: {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  onExit: () => void;
}) {
  return (
    <header className="bg-[#2a1f00] px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
      <div className="flex items-center gap-3 min-w-0">
        {onBack && (
          <button
            type="button"
            onClick={onBack}
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
      <button
        type="button"
        onClick={onExit}
        className="flex items-center gap-1.5 text-white/50 hover:text-white text-sm transition-colors flex-shrink-0"
      >
        <LogOut size={16} /> Exit
      </button>
    </header>
  );
}
