/** Short Web Audio feedback for barcode / tracking scans. */

export type ScanBeepKind = "ok" | "dup" | "bad";

let sharedCtx: AudioContext | null = null;

function getAudioContext(): AudioContext | null {
  try {
    const AC =
      window.AudioContext ??
      (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;
    if (!AC) return null;
    if (!sharedCtx || sharedCtx.state === "closed") {
      sharedCtx = new AC();
    }
    if (sharedCtx.state === "suspended") {
      void sharedCtx.resume();
    }
    return sharedCtx;
  } catch {
    return null;
  }
}

function tone(
  ctx: AudioContext,
  {
    frequency,
    type,
    start,
    duration,
    peak = 0.09,
  }: {
    frequency: number;
    type: OscillatorType;
    start: number;
    duration: number;
    peak?: number;
  },
) {
  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  osc.type = type;
  osc.frequency.value = frequency;
  gain.gain.setValueAtTime(0.0001, start);
  gain.gain.exponentialRampToValueAtTime(peak, start + 0.01);
  gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
  osc.connect(gain);
  gain.connect(ctx.destination);
  osc.start(start);
  osc.stop(start + duration + 0.02);
}

/** Success beep, or lower/harsher error beep for duplicates and failures. */
export function playScanBeep(kind: ScanBeepKind = "ok"): void {
  const ctx = getAudioContext();
  if (!ctx) return;
  const t0 = ctx.currentTime;
  try {
    if (kind === "ok") {
      tone(ctx, { frequency: 980, type: "sine", start: t0, duration: 0.09, peak: 0.1 });
      return;
    }
    if (kind === "dup") {
      // Two mid beeps — distinct from success, less harsh than hard fail.
      tone(ctx, { frequency: 420, type: "triangle", start: t0, duration: 0.08, peak: 0.09 });
      tone(ctx, { frequency: 420, type: "triangle", start: t0 + 0.12, duration: 0.08, peak: 0.09 });
      return;
    }
    // Hard error: short low buzz pair.
    tone(ctx, { frequency: 180, type: "square", start: t0, duration: 0.12, peak: 0.07 });
    tone(ctx, { frequency: 140, type: "square", start: t0 + 0.14, duration: 0.14, peak: 0.07 });
  } catch {
    /* ignore — audio may be blocked until a user gesture */
  }
}
