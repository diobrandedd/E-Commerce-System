import { posSysImgUrl } from "@/app/lib/posAssets";

const STORAGE_PREFIX = "pyx_pos_v1:";
const MAX_ENTRY_AGE_MS = 7 * 24 * 60 * 60 * 1000; // 7 days — discard very stale catalog blobs

interface CacheEnvelope<T> {
  savedAt: number;
  data: T;
}

/** Category + honey variant art served from `/rts/assets/sys_img/`. */
export const POS_WARMUP_IMAGES = [
  "honey.png",
  "paragistea.png",
  "brc.png",
  "jarcacao.png",
  "honeycomb.png",
  "light.png",
  "amber.png",
  "dark.png",
] as const;

function readEnvelope<T>(key: string): CacheEnvelope<T> | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = localStorage.getItem(STORAGE_PREFIX + key);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as CacheEnvelope<T>;
    if (!parsed || typeof parsed.savedAt !== "number" || parsed.data === undefined) {
      localStorage.removeItem(STORAGE_PREFIX + key);
      return null;
    }
    if (Date.now() - parsed.savedAt > MAX_ENTRY_AGE_MS) {
      localStorage.removeItem(STORAGE_PREFIX + key);
      return null;
    }
    return parsed;
  } catch {
    return null;
  }
}

export function readPosCache<T>(key: string): T | null {
  return readEnvelope<T>(key)?.data ?? null;
}

export function writePosCache<T>(key: string, data: T): void {
  if (typeof window === "undefined") return;
  try {
    const envelope: CacheEnvelope<T> = { savedAt: Date.now(), data };
    localStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(envelope));
  } catch {
    // Quota exceeded — ignore; network fetch still works.
  }
}

const warmedUrls = new Set<string>();

/** Prime the browser HTTP cache for POS images (persists across Chrome restarts). */
export function prefetchPosImages(urls: string[]): void {
  if (typeof window === "undefined") return;
  for (const url of urls) {
    if (!url || warmedUrls.has(url)) continue;
    warmedUrls.add(url);
    const img = new Image();
    img.decoding = "async";
    img.src = url;
  }
}

export function warmupPosAssets(extraUrls: string[] = []): void {
  const urls = [
    ...POS_WARMUP_IMAGES.map(f => posSysImgUrl(f)),
    ...extraUrls,
  ];
  prefetchPosImages(urls);
}

/**
 * Stale-while-revalidate: show cached catalog immediately, then refresh from API.
 * Cached data survives closing Chrome (localStorage). Images use HTTP cache via .htaccess.
 */
export async function loadPosCatalogCached<T>(
  cacheKey: string,
  fetcher: () => Promise<T>,
  onData: (data: T, fromCache: boolean) => void,
): Promise<void> {
  const cached = readPosCache<T>(cacheKey);
  if (cached !== null) {
    onData(cached, true);
  }

  try {
    const fresh = await fetcher();
    writePosCache(cacheKey, fresh);
    onData(fresh, false);
  } catch (e) {
    if (cached === null) throw e;
  }
}
