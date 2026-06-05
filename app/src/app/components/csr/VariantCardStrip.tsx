import { useRef } from "react";

import { ChevronLeft, ChevronRight } from "lucide-react";

import {

  OrderRemarksScrollButton,

  VariantPresetButton,

} from "@/app/components/csr/orderRemarksMotion";

import type { PresetOption } from "@/app/lib/orderRemarksParse";



interface VariantCardStripProps {

  presets: PresetOption[];

  selected?: string;

  onSelect: (value: string) => void;

  disabled?: boolean;

  title?: string;

}



export function VariantCardStrip({

  presets,

  selected,

  onSelect,

  disabled,

  title,

}: VariantCardStripProps) {

  const scrollRef = useRef<HTMLDivElement>(null);



  const scroll = (dir: -1 | 1) => {

    const el = scrollRef.current;

    if (!el) return;

    el.scrollBy({ left: dir * 200, behavior: "smooth" });

  };



  if (presets.length === 0) return null;



  return (

    <div className="mt-2">

      {title && (

        <p className="text-xs font-semibold text-foreground mb-1.5">{title}</p>

      )}

      <div className="relative">

        <OrderRemarksScrollButton

          side="left"

          ariaLabel="Scroll presets left"

          disabled={disabled}

          onClick={() => scroll(-1)}

        >

          <ChevronLeft size={16} />

        </OrderRemarksScrollButton>

        <OrderRemarksScrollButton

          side="right"

          ariaLabel="Scroll presets right"

          disabled={disabled}

          onClick={() => scroll(1)}

        >

          <ChevronRight size={16} />

        </OrderRemarksScrollButton>

        <div

          ref={scrollRef}

          className="flex gap-2 overflow-x-auto px-9 py-1 scroll-smooth snap-x snap-mandatory scrollbar-thin"

        >

          {presets.map(p => (

            <VariantPresetButton

              key={p.value}

              label={p.label}

              stockLeft={p.stockLeft}

              active={selected === p.value}

              disabled={disabled}

              onClick={() => onSelect(p.value)}

            />

          ))}

        </div>

      </div>

    </div>

  );

}


