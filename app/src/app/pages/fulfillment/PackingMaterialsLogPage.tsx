import { createElement } from "react";
import { Navigate, useSearchParams } from "react-router";

/** Legacy route — redirects to unified For packing page with tab query. */
export function PackingMaterialsLogPage() {
  const [params] = useSearchParams();
  const tab = params.get("tab");
  const view = tab === "damage" ? "damage" : "takeouts";
  return createElement(Navigate, {
    to: `/fulfillment/packing-materials?view=${view}`,
    replace: true,
  });
}
