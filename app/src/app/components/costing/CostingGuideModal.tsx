import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import {
  HONEY_FORMULA_LITERS_BY_SIZE,
  HONEY_LITERS_PER_US_GALLON,
} from "@/app/lib/honeyPricing";
import {
  ptBagsPerPouch,
  ptPouchesPerBag,
  ptTeaBagsPerBag,
  brcBagsPerPouch,
  brcPouchesPerBag,
  jarCacaoBagsPerJar,
  jarCacaoJarsPerBag,
  BRC_GRAMS_PER_POUCH,
  JAR_CACAO_GRAMS_PER_JAR,
  JAR_CACAO_TABLETS_PER_JAR,
} from "@/app/lib/deptProducts";

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

function GuideTable({
  headers,
  rows,
}: {
  headers: [string, string];
  rows: [string, string][];
}) {
  return (
    <table className="w-full text-[11px] border border-border rounded-lg overflow-hidden">
      <thead>
        <tr className="bg-secondary/50">
          <th className="text-left px-2 py-1.5 font-semibold text-foreground">{headers[0]}</th>
          <th className="text-left px-2 py-1.5 font-semibold text-foreground">{headers[1]}</th>
        </tr>
      </thead>
      <tbody>
        {rows.map(([a, b]) => (
          <tr key={a} className="border-t border-border">
            <td className="px-2 py-1.5">{a}</td>
            <td className="px-2 py-1.5 font-mono text-foreground">{b}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

export function CostingGuideModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const honeyRows: [string, string][] = [
    ["250 ml bottle", `${HONEY_FORMULA_LITERS_BY_SIZE["250ml"]} L`],
    ["350 ml bottle", `${HONEY_FORMULA_LITERS_BY_SIZE["350ml"]} L`],
    ["1 L (liter)", `${HONEY_FORMULA_LITERS_BY_SIZE.liter} L`],
    ["Gallon jar", `${HONEY_FORMULA_LITERS_BY_SIZE.gallon.toFixed(3)} L (1 US gal)`],
  ];

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="costing-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Costing guide
          </span>
        }
        titleId="costing-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="How production cost is calculated">
          <p>
            Each product has a <strong className="text-foreground">formula</strong> — lines that say how much of
            each stock goes into one sellable unit.
          </p>
          <p className="font-mono text-foreground bg-secondary/40 rounded-lg px-3 py-2">
            production cost = Σ (qty per unit × stock current price)
          </p>
          <p>
            <strong className="text-foreground">Baseline</strong> is frozen when the formula line is added.{" "}
            <strong className="text-foreground">Current</strong> updates when stock prices change.{" "}
            <strong className="text-foreground">Margin</strong> = selling price − current cost.
          </p>
        </GuideSection>

        <GuideSection title="Where prices come from">
          <ul className="list-disc pl-4 space-y-1">
            <li>
              <strong className="text-foreground">Honey raw</strong> — Supplier Master ₱/gallon → costing stock syncs as ₱/liter (÷{" "}
              {HONEY_LITERS_PER_US_GALLON.toFixed(3)}).
            </li>
            <li>
              <strong className="text-foreground">PT / BRC / Jar Cacao raw</strong> — Supplier Master ₱/bag (1 kg bag).
            </li>
            <li>
              <strong className="text-foreground">Packaging</strong> — Packaging Materials lot stock-in → WAC ₱/pc (or pouch).
            </li>
            <li>
              <strong className="text-foreground">Other materials</strong> — editable on Costing when adding formula lines (legacy stocks).
            </li>
          </ul>
        </GuideSection>

        <GuideSection title="Honey (variant × bottle size)">
          <p>
            Create one product per variant (Light / Amber / Dark) and size. Raw honey formula qty is in{" "}
            <strong className="text-foreground">liters</strong>:
          </p>
          <GuideTable headers={["Bottle size", "Raw honey in formula"]} rows={honeyRows} />
          <p>
            Example: ₱3,000/gallon → ₱{(3000 / HONEY_LITERS_PER_US_GALLON).toFixed(2)}/L. A 350 ml SKU uses 0.35 L → raw ≈ ₱
            {((3000 / HONEY_LITERS_PER_US_GALLON) * 0.35).toFixed(2)} per bottle (+ bottles, caps, labels on formula).
          </p>
        </GuideSection>

        <GuideSection title="Paragis Tea">
          <p>Supplier <strong className="text-foreground">1 kg bag</strong> → sellable <strong className="text-foreground">pouch</strong> (24 tea bags × 2 g = 48 g leaf).</p>
          <GuideTable
            headers={["Step", "Math"]}
            rows={[
              ["Tea bags per bag", `${ptTeaBagsPerBag()} bags (1000 ÷ 2 g)`],
              ["Pouches per bag (stock-in)", `~${ptPouchesPerBag().toFixed(2)} pouches`],
              ["Formula raw qty", `${ptBagsPerPouch()} bag/pouch`],
              ["Packaging", `1 pouch packaging / pouch`],
            ]}
          />
          <p>Example: ₱400/bag → leaf ≈ ₱{(400 * ptBagsPerPouch()).toFixed(2)}/pouch.</p>
        </GuideSection>

        <GuideSection title="Black Rice Coffee">
          <p>Supplier <strong className="text-foreground">1 kg bag</strong> → sellable <strong className="text-foreground">pouch</strong> ({BRC_GRAMS_PER_POUCH} g).</p>
          <GuideTable
            headers={["Step", "Math"]}
            rows={[
              ["Pouches per bag (stock-in)", `${brcPouchesPerBag()} pouches`],
              ["Formula raw qty", `${brcBagsPerPouch()} bag/pouch`],
              ["Packaging", `1 BRC pouch packaging / pouch`],
            ]}
          />
          <p>Example: ₱100/bag → raw ≈ ₱{(100 * brcBagsPerPouch()).toFixed(2)}/pouch.</p>
        </GuideSection>

        <GuideSection title="Jar Cacao">
          <p>
            Supplier <strong className="text-foreground">1 kg bag</strong> → sellable <strong className="text-foreground">jar</strong> ({JAR_CACAO_GRAMS_PER_JAR} g, {JAR_CACAO_TABLETS_PER_JAR} tablets inside — descriptive only).
          </p>
          <GuideTable
            headers={["Step", "Math"]}
            rows={[
              ["Jars per bag (stock-in)", `~${jarCacaoJarsPerBag().toFixed(2)} jars`],
              ["Formula raw qty", `${jarCacaoBagsPerJar()} bag/jar`],
              ["Packaging", `1 jar packaging / jar`],
            ]}
          />
          <p>Example: ₱500/bag → raw ≈ ₱{(500 * jarCacaoBagsPerJar()).toFixed(2)}/jar.</p>
        </GuideSection>

        <GuideSection title="Custom costs (labor, shipping, etc.)">
          <p>
            On any product, use <strong className="text-foreground">Add formula line → Custom cost</strong> to enter a
            name and ₱ amount (e.g. Labor ₱15/jar, Shipping ₱5/pouch). These are fixed on the formula — they do not pull
            from Supplier Master or Packaging Materials.
          </p>
        </GuideSection>

        <GuideSection title="End-to-end workflow">
          <ol className="list-decimal pl-4 space-y-1">
            <li>Set supplier prices (Supplier Master for honey / dept raw).</li>
            <li>Stock in packaging lots (Packaging Materials) for WAC.</li>
            <li>Stock in finished dept inventory (Products → stock-in bags → sell units).</li>
            <li>Build or verify formula on each costing product (auto-suggested qty for dept lines).</li>
            <li>Set selling price; margin updates live when costs move.</li>
          </ol>
        </GuideSection>
      </AppModalBody>
    </AppModal>
  );
}
