/**
 * Tiny API client for the Pyx Tracker PHP JSON gateway.
 *
 * - Same-origin under Apache, so cookies (PHPSESSID) are sent automatically.
 * - All endpoints return `{ ok, data, error }`.
 * - For state-changing calls, a CSRF token is taken from the most recent
 *   `auth.php?action=me` response and sent in the `X-CSRF-Token` header.
 */

import { triggerLiveRefresh } from "@/app/lib/liveRefresh";

export const API_BASE = "/rts/api/v1";

let csrfToken: string | null = null;
let extraFetchHeaders: Record<string, string> = {};

/** ngrok free tier returns HTML unless this header is sent on every API request. */
function ngrokFetchHeaders(): Record<string, string> {
  if (typeof window === "undefined") return {};
  const h = window.location.hostname.toLowerCase();
  if (h.includes("ngrok")) {
    return { "ngrok-skip-browser-warning": "1" };
  }
  return {};
}

function mergeApiHeaders(base: Record<string, string>): Record<string, string> {
  return { ...ngrokFetchHeaders(), ...extraFetchHeaders, ...base };
}

export function setCsrfToken(token: string | null): void {
  csrfToken = token;
}

/** Extra headers from auth `forwarding.fetch_headers` (merged with ngrok auto-detect). */
export function setForwardingFetchHeaders(headers: Record<string, string> | null | undefined): void {
  extraFetchHeaders = headers ?? {};
}

export function getCsrfToken(): string | null {
  return csrfToken;
}

export interface ApiResponse<T> {
  ok: boolean;
  data: T | null;
  error: string | null;
}

export class ApiError extends Error {
  constructor(message: string, public status: number, public data: unknown = null) {
    super(message);
    this.name = "ApiError";
  }
}

/** Parse `{ ok, data, error }` even when PHP warnings were prepended to the body. */
export async function readApiResponse<T>(response: Response): Promise<ApiResponse<T>> {
  const text = await response.text();
  if (!text.trim()) {
    throw new ApiError(
      `Empty response (${response.status}). The server may have crashed during processing — check Apache error log, Python install, and try again.`,
      response.status
    );
  }
  try {
    return JSON.parse(text) as ApiResponse<T>;
  } catch {
    const trimmed = text.trim();
    const jsonStart = trimmed.lastIndexOf("{");
    if (jsonStart >= 0) {
      try {
        return JSON.parse(trimmed.slice(jsonStart)) as ApiResponse<T>;
      } catch {
        /* fall through */
      }
    }
    const plain = trimmed
      .replace(/<br\s*\/?>/gi, "\n")
      .replace(/<[^>]+>/g, "")
      .replace(/\s+/g, " ")
      .trim();
    const msg = plain.length > 280 ? `${plain.slice(0, 280)}…` : plain;
    throw new ApiError(msg || `Bad response (${response.status}).`, response.status);
  }
}

interface ApiRequestOptions {
  method?: "GET" | "POST";
  body?: unknown;
  signal?: AbortSignal;
  params?: Record<string, string | number | boolean | null | undefined>;
}

export async function api<T = unknown>(
  path: string,
  action?: string,
  options: ApiRequestOptions = {}
): Promise<T> {
  const method = options.method ?? (options.body !== undefined ? "POST" : "GET");

  const url = new URL(`${API_BASE}/${path.replace(/^\/+/, "")}`, window.location.origin);
  if (action) url.searchParams.set("action", action);
  if (options.params) {
    for (const [k, v] of Object.entries(options.params)) {
      if (v === null || v === undefined || v === "") continue;
      url.searchParams.set(k, String(v));
    }
  }

  const headers = mergeApiHeaders({
    Accept: "application/json",
  });
  if (method !== "GET") {
    headers["Content-Type"] = "application/json";
    if (csrfToken) headers["X-CSRF-Token"] = csrfToken;
  }

  let response: Response;
  try {
    response = await fetch(url.toString(), {
      method,
      headers,
      credentials: "same-origin",
      body: method === "GET" || options.body === undefined ? undefined : JSON.stringify(options.body ?? {}),
      signal: options.signal,
    });
  } catch (e) {
    throw new ApiError("Network error. Is XAMPP/Apache running?", 0);
  }

  const payload = await readApiResponse<T>(response);

  if (!payload.ok) {
    throw new ApiError(payload.error ?? `Request failed (${response.status}).`, response.status, payload.data);
  }
  if (method !== "GET") {
    triggerLiveRefresh();
  }
  return payload.data as T;
}

export function apiGet<T = unknown>(
  path: string,
  action?: string,
  params?: Record<string, string | number | boolean | null | undefined>,
  signal?: AbortSignal
): Promise<T> {
  return api<T>(path, action, { method: "GET", params, signal });
}

export function apiPost<T = unknown>(path: string, action: string, body?: unknown, signal?: AbortSignal): Promise<T> {
  return api<T>(path, action, { method: "POST", body, signal });
}

/** Multipart POST (e.g. supplier create with ID photo). Do not set Content-Type — browser sets boundary. */
export async function apiPostForm<T = unknown>(
  path: string,
  action: string,
  form: FormData,
  signal?: AbortSignal,
  options?: { refresh?: boolean }
): Promise<T> {
  const url = new URL(`${API_BASE}/${path.replace(/^\/+/, "")}`, window.location.origin);
  url.searchParams.set("action", action);
  const headers = mergeApiHeaders({ Accept: "application/json" });
  if (csrfToken) headers["X-CSRF-Token"] = csrfToken;

  const response = await fetch(url.toString(), {
    method: "POST",
    headers,
    credentials: "same-origin",
    body: form,
    signal,
  });

  const payload = await readApiResponse<T>(response);
  if (!payload.ok) {
    throw new ApiError(payload.error ?? `Request failed (${response.status}).`, response.status, payload.data);
  }
  if (options?.refresh !== false) {
    triggerLiveRefresh();
  }
  return payload.data as T;
}
