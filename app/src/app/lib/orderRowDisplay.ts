import type { OrderRow } from "@/app/lib/orderRemarksApi";
import type { ParsedRemark } from "@/app/lib/orderRemarksParse";

export interface OrderCardDisplay {
  receiver: string;
  phone: string;
  place: string;
  province: string;
  address: string;
  rowNum: number;
}

export function getOrderCardDisplay(order: OrderRow, parsed: ParsedRemark): OrderCardDisplay {
  const receiver = (order.receiver || "").trim();
  const phone = (order.phone || "").trim();
  const city = (order.city || "").trim();
  const region = (order.region || "").trim();
  const province = (order.province || "").trim();
  const placeParts = [city, region].filter(Boolean);
  const place = placeParts.join(", ");

  const fromRemark = (parsed.address || "").trim();
  const fromExcel = (order.address || "").trim();
  let address = fromRemark || fromExcel;
  if (fromRemark && fromExcel && fromRemark !== fromExcel) {
    if (!fromRemark.toLowerCase().includes(fromExcel.toLowerCase().slice(0, 20))) {
      address = fromRemark;
    }
  }

  return {
    receiver,
    phone,
    place,
    province,
    address,
    rowNum: order.rowNum,
  };
}

export function formatPreviewLine(order: OrderRow, parsed: ParsedRemark): string {
  const d = getOrderCardDisplay(order, parsed);
  const parts: string[] = [];
  if (d.receiver) parts.push(d.receiver);
  if (d.place) parts.push(d.place);
  if (parts.length === 0) return (order.remarks || "").slice(0, 60);
  return parts.join(" · ");
}
