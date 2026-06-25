import { useEffect } from "react";
import { Outlet } from "react-router";
import { warmupPosAssets } from "@/app/lib/posCache";

/** Shared POS shell — warms static product images on every POS route. */
export function POSLayout() {
  useEffect(() => {
    warmupPosAssets();
  }, []);

  return <Outlet />;
}
