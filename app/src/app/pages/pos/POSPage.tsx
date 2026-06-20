import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { Plus, Minus, Trash2, Printer, LogOut, AlertCircle, Loader2 } from "lucide-react";
import pyxLogo from "@/imports/pyxfoodpr.png";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { useAuth } from "@/app/context/AuthContext";

interface POSProduct {
  id: number;
  name: string;
  size: string | null;
  variant: string | null;
  selling_price: string;
  dept_name: string;
}

interface CartItem { product: POSProduct; qty: number }

interface CheckoutResult {
  sale_id: number;
  receipt_no: string;
  total: number;
  buyer_name: string;
  items: { product_id: number; qty: number; unit_price: number; line_total: number; name: string }[];
}

const peso = (n: number) => `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function POSPage() {
  const navigate = useNavigate();
  const { logout } = useAuth();
  const [products, setProducts] = useState<POSProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [cart, setCart] = useState<CartItem[]>([]);
  const [buyer, setBuyer] = useState("");
  const [buyerCreds, setBuyerCreds] = useState("");
  const [checkingOut, setCheckingOut] = useState(false);
  const [receipt, setReceipt] = useState<CheckoutResult | null>(null);

  useEffect(() => {
    (async () => {
      try {
        const payload = await apiGet<{ products: POSProduct[] }>("pos.php", "products");
        setProducts(payload.products);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load products.");
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const addToCart = (product: POSProduct) => {
    setCart(prev => {
      const existing = prev.find(i => i.product.id === product.id);
      if (existing) return prev.map(i => i.product.id === product.id ? { ...i, qty: i.qty + 1 } : i);
      return [...prev, { product, qty: 1 }];
    });
  };

  const updateQty = (id: number, delta: number) => {
    setCart(prev => prev.map(i => i.product.id === id ? { ...i, qty: i.qty + delta } : i).filter(i => i.qty > 0));
  };

  const total = cart.reduce((s, i) => s + parseFloat(i.product.selling_price) * i.qty, 0);

  const handleCheckout = async () => {
    if (cart.length === 0) return;
    setCheckingOut(true);
    setError(null);
    try {
      const result = await apiPost<CheckoutResult>("pos.php", "checkout", {
        items: cart.map(i => ({ product_id: i.product.id, qty: i.qty })),
        buyer_name: buyer,
        buyer_credentials: buyerCreds,
      });
      setReceipt(result);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Checkout failed.");
    } finally {
      setCheckingOut(false);
    }
  };

  const handleExit = async () => {
    await logout();
    navigate("/", { replace: true });
  };

  if (receipt) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center p-4" style={{ fontFamily: "'DM Mono', monospace" }}>
        <div className="bg-card rounded-xl border border-border p-8 w-full max-w-sm" style={{ boxShadow: "0 4px 24px rgba(0,0,0,0.1)" }}>
          <div className="text-center mb-6">
            <img src={pyxLogo} alt="Pyx" className="w-16 h-16 object-contain mx-auto mb-3" />
            <p className="text-sm font-bold text-foreground">Pyx Food Products</p>
            <p className="text-xs text-muted-foreground">Walk-in Sales Receipt</p>
            <p className="text-xs text-muted-foreground mt-1">{receipt.receipt_no}</p>
            <p className="text-xs text-muted-foreground">{new Date().toLocaleString("en-PH")}</p>
          </div>
          <div className="border-t border-dashed border-border pt-4 mb-4">
            <p className="text-xs text-muted-foreground mb-3">Buyer: <span className="text-foreground font-semibold">{receipt.buyer_name}</span></p>
            <div className="space-y-2">
              {receipt.items.map((item, idx) => (
                <div key={idx} className="flex justify-between text-xs">
                  <span className="text-foreground">{item.name} x{item.qty}</span>
                  <span className="font-semibold text-foreground">{peso(item.line_total)}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="border-t-2 border-border pt-3 flex justify-between items-center mb-6">
            <span className="font-bold text-foreground">TOTAL</span>
            <span className="text-xl font-bold" style={{ color: "#C8900A" }}>{peso(receipt.total)}</span>
          </div>
          <div className="flex gap-3">
            <button onClick={() => window.print()} className="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg text-sm font-semibold text-white" style={{ background: "#C8900A" }}>
              <Printer size={15} /> Print
            </button>
            <button onClick={() => { setCart([]); setBuyer(""); setBuyerCreds(""); setReceipt(null); }} className="flex-1 py-3 rounded-lg text-sm font-semibold border border-border hover:bg-secondary transition-colors text-muted-foreground">
              New Sale
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background flex flex-col" style={{ fontFamily: "'DM Sans', sans-serif" }}>
      <header className="bg-[#2a1f00] px-6 py-4 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <img src={pyxLogo} alt="Pyx" className="w-9 h-9 object-contain" />
          <div>
            <p className="text-white text-sm font-bold leading-tight">Pyx POS</p>
            <p className="text-white/40 text-[10px]">Walk-in Sales</p>
          </div>
        </div>
        <button onClick={handleExit} className="flex items-center gap-1.5 text-white/50 hover:text-white text-sm transition-colors">
          <LogOut size={16} /> Exit POS
        </button>
      </header>

      {error && (
        <div className="bg-red-50 border-b border-red-200 px-6 py-2 text-sm text-red-700 flex items-center gap-2">
          <AlertCircle size={14} /> {error}
        </div>
      )}

      <div className="flex-1 flex flex-col lg:flex-row gap-0">
        <div className="flex-1 p-5 overflow-y-auto">
          <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-4">Select products</h2>
          {loading ? (
            <div className="text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading products…</div>
          ) : products.length === 0 ? (
            <p className="text-sm text-muted-foreground">No products with a selling price yet.</p>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3">
              {products.map(p => {
                const inCart = cart.find(i => i.product.id === p.id);
                return (
                  <button
                    key={p.id}
                    onClick={() => addToCart(p)}
                    className={`bg-card rounded-xl border p-4 text-left transition-all duration-150 hover:shadow-md active:scale-[0.97] ${inCart ? "border-[#C8900A]" : "border-border"}`}
                    style={inCart ? { boxShadow: "0 0 0 2px #C8900A20" } : { boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
                  >
                    <p className="text-sm font-semibold text-foreground leading-tight">{p.name}</p>
                    {(p.size || p.variant) && (
                      <p className="text-xs text-muted-foreground mt-0.5">{[p.size, p.variant].filter(Boolean).join(" · ")}</p>
                    )}
                    <p className="text-base font-bold mt-2" style={{ color: "#C8900A" }}>{peso(parseFloat(p.selling_price))}</p>
                    <p className="text-[10px] text-muted-foreground mt-1">{p.dept_name}</p>
                    {inCart && (
                      <span className="mt-2 inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#C8900A] text-white">
                        {inCart.qty} in cart
                      </span>
                    )}
                  </button>
                );
              })}
            </div>
          )}
        </div>

        <div className="w-full lg:w-80 bg-card border-t lg:border-t-0 lg:border-l border-border flex flex-col">
          <div className="px-5 py-4 border-b border-border">
            <h2 className="text-sm font-semibold text-foreground">Cart</h2>
          </div>

          <div className="flex-1 overflow-y-auto px-5 py-4 space-y-3">
            {cart.length === 0 ? (
              <p className="text-sm text-muted-foreground text-center py-8">No items added yet.</p>
            ) : (
              cart.map(item => (
                <div key={item.product.id} className="flex items-center gap-3">
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-foreground truncate">{item.product.name}</p>
                    {(item.product.size || item.product.variant) && (
                      <p className="text-xs text-muted-foreground">{[item.product.size, item.product.variant].filter(Boolean).join(" · ")}</p>
                    )}
                    <p className="text-xs font-semibold" style={{ color: "#C8900A" }}>{peso(parseFloat(item.product.selling_price) * item.qty)}</p>
                  </div>
                  <div className="flex items-center gap-1 flex-shrink-0">
                    <button onClick={() => updateQty(item.product.id, -1)} className="w-7 h-7 rounded-lg border border-border flex items-center justify-center text-muted-foreground hover:bg-secondary transition-colors">
                      <Minus size={12} />
                    </button>
                    <span className="w-7 text-center text-sm font-semibold">{item.qty}</span>
                    <button onClick={() => updateQty(item.product.id, 1)} className="w-7 h-7 rounded-lg border border-border flex items-center justify-center text-muted-foreground hover:bg-secondary transition-colors">
                      <Plus size={12} />
                    </button>
                    <button onClick={() => setCart(prev => prev.filter(i => i.product.id !== item.product.id))} className="w-7 h-7 rounded-lg flex items-center justify-center text-red-400 hover:bg-red-50 transition-colors ml-1">
                      <Trash2 size={12} />
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>

          <div className="px-5 py-4 border-t border-border space-y-3">
            <input value={buyer} onChange={e => setBuyer(e.target.value)} placeholder="Buyer name" className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] transition-colors" />
            <input value={buyerCreds} onChange={e => setBuyerCreds(e.target.value)} placeholder="Buyer credentials (optional)" className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A] transition-colors" />
            <div className="flex justify-between items-center">
              <span className="text-sm text-muted-foreground">Total</span>
              <span className="text-xl font-bold" style={{ color: "#C8900A" }}>{peso(total)}</span>
            </div>
            <button
              onClick={handleCheckout}
              disabled={cart.length === 0 || checkingOut}
              className="w-full py-3.5 rounded-lg text-sm font-bold text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed"
              style={{ background: "#C8900A" }}
            >
              {checkingOut ? "Processing…" : "Generate receipt"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
