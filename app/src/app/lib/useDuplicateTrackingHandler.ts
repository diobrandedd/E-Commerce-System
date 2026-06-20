import { useCallback, useState } from "react";
import { ApiError } from "@/app/lib/api";
import { duplicateConflictsFromApiError } from "@/app/lib/duplicateTrackingUtils";
import type { DuplicateTrackingConflict } from "@/app/lib/onlineOrdersTypes";

export function useDuplicateTrackingHandler() {
  const [duplicateConflicts, setDuplicateConflicts] = useState<DuplicateTrackingConflict[]>([]);
  const [showDuplicateModal, setShowDuplicateModal] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const clearDuplicateState = useCallback(() => {
    setDuplicateConflicts([]);
    setShowDuplicateModal(false);
  }, []);

  const clearErrors = useCallback(() => {
    setError(null);
    clearDuplicateState();
  }, [clearDuplicateState]);

  /** Returns true when the error was a duplicate-tracking conflict (modal shown). */
  const handleApiError = useCallback((e: unknown, fallback: string): boolean => {
    const dups = duplicateConflictsFromApiError(e);
    if (dups) {
      setDuplicateConflicts(dups);
      setShowDuplicateModal(true);
      setError(null);
      return true;
    }
    setError(e instanceof ApiError ? e.message : fallback);
    return false;
  }, []);

  return {
    duplicateConflicts,
    showDuplicateModal,
    setShowDuplicateModal,
    error,
    setError,
    clearErrors,
    handleApiError,
  };
}
