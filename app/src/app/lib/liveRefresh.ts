/** Lightweight pub/sub so lists and notification badges refresh after any inventory mutation. */

type Listener = () => void;
const listeners = new Set<Listener>();

export function onLiveRefresh(listener: Listener): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

export function triggerLiveRefresh(): void {
  listeners.forEach(fn => {
    try {
      fn();
    } catch {
      /* ignore subscriber errors */
    }
  });
}
