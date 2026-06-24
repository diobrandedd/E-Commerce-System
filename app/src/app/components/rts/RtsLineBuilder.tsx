import { useState } from "react";
import type { RtsLineItem, RtsProductCatalog } from "@/app/lib/rtsApi";
import { rtsLineDisplay } from "@/app/lib/rtsApi";

const HONEY_MAIN = new Set(["honey", "honey_with_other"]);
const COMBO_HONEY = new Set(["pouch_pt_with_honey", "jar_cacao_with_honey"]);
const COMBO_OTHER = new Set(["honey_with_other"]);

interface RtsLineBuilderProps {
  catalog: RtsProductCatalog;
  lines: RtsLineItem[];
  onChange: (lines: RtsLineItem[]) => void;
  title?: string;
  description?: string;
}

export function RtsLineBuilder({ catalog, lines, onChange, title = "Product lines", description }: RtsLineBuilderProps) {
  const [productType, setProductType] = useState("honey");
  const [honeySize, setHoneySize] = useState("liter");
  const [honeyVariant, setHoneyVariant] = useState("light");
  const [comboProduct, setComboProduct] = useState(catalog.combo_products[0] ?? "");
  const [comboQty, setComboQty] = useState("1");
  const [comboHoneySize, setComboHoneySize] = useState("liter");
  const [comboHoneyVariant, setComboHoneyVariant] = useState("light");
  const [qty, setQty] = useState("1");
  const [isBonus, setIsBonus] = useState(false);

  const addLine = () => {
    const line: RtsLineItem = {
      product_type: productType,
      quantity: Math.max(0.01, parseFloat(qty) || 1),
      is_bonus: isBonus,
    };
    if (HONEY_MAIN.has(productType)) {
      line.honey_size = honeySize;
      line.honey_variant = honeyVariant;
    }
    if (COMBO_OTHER.has(productType) && comboProduct) {
      line.combo_product = comboProduct;
      line.combo_quantity = Math.max(0.01, parseFloat(comboQty) || 1);
    }
    if (COMBO_HONEY.has(productType)) {
      line.combo_honey_size = comboHoneySize;
      line.combo_honey_variant = comboHoneyVariant;
      line.combo_quantity = Math.max(0.01, parseFloat(comboQty) || 1);
    }
    onChange([...lines, line]);
    setIsBonus(false);
  };

  const removeLine = (idx: number) => onChange(lines.filter((_, i) => i !== idx));

  return (
    <div className="space-y-3 rounded-xl border border-border p-4 bg-card">
      <div>
        <p className="text-sm font-semibold">{title}</p>
        {description ? <p className="text-xs text-muted-foreground mt-0.5">{description}</p> : null}
      </div>
      {lines.length > 0 && (
        <ul className="text-sm space-y-1">
          {lines.map((l, i) => (
            <li key={i} className="flex justify-between gap-2 border-b border-border/60 py-1">
              <span>{rtsLineDisplay(l, catalog.product_labels)}</span>
              <button type="button" onClick={() => removeLine(i)} className="text-xs text-red-600">Remove</button>
            </li>
          ))}
        </ul>
      )}
      <div className="grid gap-2 sm:grid-cols-2">
        <label className="text-xs">
          Product
          <select value={productType} onChange={e => setProductType(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
            {catalog.product_types.map(t => (
              <option key={t} value={t}>{catalog.product_labels[t] ?? t}</option>
            ))}
          </select>
        </label>
        <label className="text-xs">
          Quantity
          <input type="number" min={0.01} step={0.01} value={qty} onChange={e => setQty(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm" />
        </label>
        {HONEY_MAIN.has(productType) && (
          <>
            <label className="text-xs">
              Honey size
              <select value={honeySize} onChange={e => setHoneySize(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                {catalog.honey_sizes.map(s => (
                  <option key={s} value={s}>{catalog.honey_size_labels[s] ?? s}</option>
                ))}
              </select>
            </label>
            <label className="text-xs">
              Variant
              <select value={honeyVariant} onChange={e => setHoneyVariant(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                {catalog.honey_variants.map(v => (
                  <option key={v} value={v}>{catalog.honey_variant_labels[v] ?? v}</option>
                ))}
              </select>
            </label>
          </>
        )}
        {COMBO_OTHER.has(productType) && (
          <>
            <label className="text-xs">
              Paired product
              <select value={comboProduct} onChange={e => setComboProduct(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                {catalog.combo_products.map(c => (
                  <option key={c} value={c}>{catalog.combo_product_labels[c] ?? c}</option>
                ))}
              </select>
            </label>
            <label className="text-xs">
              Combo qty
              <input type="number" min={0.01} value={comboQty} onChange={e => setComboQty(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm" />
            </label>
          </>
        )}
        {COMBO_HONEY.has(productType) && (
          <>
            <label className="text-xs">
              Paired honey size
              <select value={comboHoneySize} onChange={e => setComboHoneySize(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                {catalog.honey_sizes.map(s => (
                  <option key={s} value={s}>{catalog.honey_size_labels[s] ?? s}</option>
                ))}
              </select>
            </label>
            <label className="text-xs">
              Paired honey variant
              <select value={comboHoneyVariant} onChange={e => setComboHoneyVariant(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm">
                {catalog.honey_variants.map(v => (
                  <option key={v} value={v}>{catalog.honey_variant_labels[v] ?? v}</option>
                ))}
              </select>
            </label>
            <label className="text-xs">
              Combo qty
              <input type="number" min={0.01} value={comboQty} onChange={e => setComboQty(e.target.value)} className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm" />
            </label>
          </>
        )}
      </div>
      <div className="flex flex-wrap gap-2">
        <label className="text-xs flex items-center gap-1">
          <input type="checkbox" checked={isBonus} onChange={e => setIsBonus(e.target.checked)} /> Bonus item
        </label>
        <button type="button" onClick={addLine} className="text-xs font-semibold px-3 py-1.5 rounded-lg text-white" style={{ background: "#C8900A" }}>
          Add to list
        </button>
      </div>
    </div>
  );
}
