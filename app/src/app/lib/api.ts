/**
 * Tiny API client for the Pyx Tracker PHP JSON gateway.
 *
 * - Same-origin under Apache, so cookies (PHPSESSID) are sent automatically.
 * - All endpoints return `{ ok, data, error }`.
 * - For state-changing calls, a CSRF token is taken from the most recent
 *   `auth.php?action=me` response and sent in the `X-CSRF-Token` header.
 */

export const API_BASE = "/rts/api/v1";

let csrfToken: string | null = null;
let extraFetchHeaders: Record<string, string> = {};

export function setCsrfToken(token: string | null): void {
  csrfToken = token;
}

/** ngrok-skip-browser-warning etc., from auth `forwarding.fetch_headers`. */
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

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...extraFetchHeaders,
  };
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

  let payload: ApiResponse<T>;
  try {
    payload = (await response.json()) as ApiResponse<T>;
  } catch {
    throw new ApiError(`Bad response (${response.status}).`, response.status);
  }

  if (!payload.ok) {
    throw new ApiError(payload.error ?? `Request failed (${response.status}).`, response.status, payload.data);
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
