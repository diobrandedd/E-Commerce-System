type POSCheckoutIdentityFieldsProps = {
  buyerName: string;
  onBuyerNameChange: (value: string) => void;
  sellerName: string;
  onSellerNameChange: (value: string) => void;
  buyerCreds: string;
  onBuyerCredsChange: (value: string) => void;
  className?: string;
  labelClassName?: string;
  inputClassName?: string;
};

export function checkoutIdentityReady(buyerName: string, sellerName: string): boolean {
  return buyerName.trim() !== "" && sellerName.trim() !== "";
}

export function POSCheckoutIdentityFields({
  buyerName,
  onBuyerNameChange,
  sellerName,
  onSellerNameChange,
  buyerCreds,
  onBuyerCredsChange,
  className = "space-y-2",
  labelClassName = "block text-xs text-muted-foreground",
  inputClassName = "mt-1 w-full border rounded-lg px-3 py-2 text-sm",
}: POSCheckoutIdentityFieldsProps) {
  return (
    <div className={className}>
      <label className={labelClassName}>
        Customer name <span className="text-destructive">*</span>
        <input
          className={inputClassName}
          value={buyerName}
          onChange={e => onBuyerNameChange(e.target.value)}
          placeholder="Enter customer name"
          required
        />
      </label>
      <label className={labelClassName}>
        Seller <span className="text-destructive">*</span>
        <input
          className={inputClassName}
          value={sellerName}
          onChange={e => onSellerNameChange(e.target.value)}
          placeholder="Who sold this order?"
          required
        />
      </label>
      <label className={labelClassName}>
        Credentials (optional)
        <input
          className={inputClassName}
          value={buyerCreds}
          onChange={e => onBuyerCredsChange(e.target.value)}
          placeholder="ID / note"
        />
      </label>
    </div>
  );
}
