/** Resize + JPEG compress before deliveries upload (phone photos are often 3–8 MB). */

const MAX_EDGE_PX = 1280;
const JPEG_QUALITY = 0.82;
const SKIP_IF_UNDER_BYTES = 500_000;

export async function compressDeliveryPhoto(file: File): Promise<File> {
  if (!file.type.startsWith("image/")) {
    return file;
  }

  let bitmap: ImageBitmap | null = null;
  try {
    bitmap = await createImageBitmap(file);
    const { width, height } = bitmap;
    const longest = Math.max(width, height);
    const scale = longest > MAX_EDGE_PX ? MAX_EDGE_PX / longest : 1;

    if (scale >= 1 && file.size < SKIP_IF_UNDER_BYTES && file.type === "image/jpeg") {
      return file;
    }

    const targetW = Math.max(1, Math.round(width * scale));
    const targetH = Math.max(1, Math.round(height * scale));
    const canvas = document.createElement("canvas");
    canvas.width = targetW;
    canvas.height = targetH;
    const ctx = canvas.getContext("2d");
    if (!ctx) {
      return file;
    }
    ctx.drawImage(bitmap, 0, 0, targetW, targetH);

    const blob = await new Promise<Blob | null>((resolve) => {
      canvas.toBlob(resolve, "image/jpeg", JPEG_QUALITY);
    });
    if (!blob || blob.size >= file.size) {
      return file;
    }

    const stem = file.name.replace(/\.[^.]+$/, "") || "parcel";
    return new File([blob], `${stem}.jpg`, { type: "image/jpeg", lastModified: Date.now() });
  } catch {
    return file;
  } finally {
    bitmap?.close();
  }
}
