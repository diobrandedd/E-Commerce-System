import { useEffect } from "react";
import { onLiveRefresh } from "@/app/lib/liveRefresh";

/** Re-run `refresh` whenever any part of the app signals data changed (save, approve, etc.). */
export function useLiveRefresh(refresh: () => void | Promise<void>) {
  useEffect(() => {
    return onLiveRefresh(() => {
      void refresh();
    });
  }, [refresh]);
}
