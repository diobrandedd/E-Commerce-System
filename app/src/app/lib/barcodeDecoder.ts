/**
 * Barcode decoding for camera scanning.
 *
 * html5-qrcode only uses the native BarcodeDetector when the browser exposes it
 * and otherwise falls back to a JavaScript ZXing port that is unreliable on
 * linear symbols. iOS Safari has no BarcodeDetector, so bottle stickers went
 * undecoded there. This module mirrors what dedicated scanner sites do: native
 * detector where present, ZXing compiled to WebAssembly everywhere else.
 */

import { prepareZXingModule, readBarcodes } from "zxing-wasm/reader";
import wasmUrl from "zxing-wasm/reader/zxing_reader.wasm?url";

type NativeBarcode = { rawValue?: string };
type NativeDetector = { detect: (source: CanvasImageSource) => Promise<NativeBarcode[]> };
type NativeDetectorCtor = {
  new (options?: { formats?: string[] }): NativeDetector;
  getSupportedFormats?: () => Promise<string[]>;
};

declare global {
  interface Window {
    BarcodeDetector?: NativeDetectorCtor;
  }
}

/** Formats we actually print on bottle stickers. */
const NATIVE_FORMATS = ["code_128", "code_39"];
const WASM_FORMATS = ["Code128", "Code39"] as const;

const MODULE_OVERRIDES = {
  locateFile: (path: string, prefix: string) => (path.endsWith(".wasm") ? wasmUrl : `${prefix}${path}`),
};

let modulePrepared = false;

function ensureWasmModule(warm: boolean): void {
  if (modulePrepared) return;
  modulePrepared = true;
  if (warm) {
    void (prepareZXingModule({ overrides: MODULE_OVERRIDES, fireImmediately: true }) as Promise<unknown>).catch(
      () => {
        // Fetch failures surface again on the first decode attempt.
      },
    );
    return;
  }
  prepareZXingModule({ overrides: MODULE_OVERRIDES });
}

let nativeDetector: NativeDetector | null | undefined;

function getNativeDetector(): NativeDetector | null {
  if (nativeDetector !== undefined) return nativeDetector;
  try {
    nativeDetector = window.BarcodeDetector ? new window.BarcodeDetector({ formats: NATIVE_FORMATS }) : null;
  } catch {
    nativeDetector = null;
  }
  return nativeDetector;
}

export function usingNativeDetector(): boolean {
  return getNativeDetector() !== null;
}

/** Warm the WASM decoder so the first scan is not delayed by a download. */
export function preloadBarcodeDecoder(): void {
  if (getNativeDetector()) return;
  ensureWasmModule(true);
}

/**
 * Decode a single frame. `canvas` feeds the native detector, `imageData` the
 * WASM decoder; both describe the same frame.
 */
export async function decodeBarcodeFrame(
  canvas: HTMLCanvasElement,
  imageData: ImageData,
): Promise<string | null> {
  const native = getNativeDetector();
  if (native) {
    try {
      const hits = await native.detect(canvas);
      const value = hits.map(h => h.rawValue?.trim()).find(v => Boolean(v));
      return value ?? null;
    } catch {
      // Detector construction succeeded but detection failed — drop to WASM.
      nativeDetector = null;
    }
  }

  ensureWasmModule(false);
  const results = await readBarcodes(imageData, {
    formats: [...WASM_FORMATS],
    tryHarder: true,
    maxNumberOfSymbols: 1,
  });
  const text = results.map(r => r.text?.trim()).find(v => Boolean(v));
  return text ?? null;
}
