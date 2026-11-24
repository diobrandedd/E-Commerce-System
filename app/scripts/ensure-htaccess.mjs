import { copyFileSync, existsSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const src = join(root, "public", ".htaccess");
const dest = join(root, "dist", ".htaccess");

if (!existsSync(src)) {
  console.error("ensure-htaccess: missing public/.htaccess");
  process.exit(1);
}
copyFileSync(src, dest);
