import { expect, test, type Page, type Route } from "@playwright/test";

const mockUser = {
  id: 1,
  username: "e2e",
  name: "E2E Executive",
  email: "e2e@example.com",
  role: "executive",
  php_role: "executive",
  dept: "all",
  dept_id: null,
  dept_name: "All departments",
  permissions: [] as string[],
};

const mePayload = {
  authenticated: true,
  mode: "user" as const,
  csrf: "e2e-csrf-token",
  forwarding: {
    enabled: false,
    public_url: "",
    host: "",
    label: "",
    allow_localhost_without_redirect: true,
    fetch_headers: {},
  },
  user: mockUser,
};

async function fulfillJson(route: Route, data: unknown) {
  await route.fulfill({
    status: 200,
    contentType: "application/json",
    body: JSON.stringify({ ok: true, error: null, data }),
  });
}

test.describe("Inventory check required docs", () => {
  test("SKU scan requires batch + bottling date before commit", async ({ page }) => {
    let scanBody: Record<string, unknown> | null = null;

    await page.route("**/rts/api/v1/auth.php**", async route => {
      await fulfillJson(route, mePayload);
    });
    await page.route("**/rts/api/v1/inventory_check.php**", async route => {
      const url = new URL(route.request().url());
      const action = url.searchParams.get("action");
      if (action === "context") {
        await fulfillJson(route, {
          user_id: 1,
          can_view_all: true,
          can_manage: true,
          event: {
            id: 9,
            status: "open",
            opened_at: "2026-09-10 10:00:00",
            opened_by: 1,
            opened_by_name: "E2E",
          },
          summary: { event: { id: 9, status: "open" }, rows: [], total_units: 0, flagged_units: 0 },
        });
        return;
      }
      if (action === "scan_log") {
        await fulfillJson(route, { rows: [], page: 1, pages: 1, total: 0 });
        return;
      }
      if (action === "scan") {
        const post = route.request().postDataJSON() as Record<string, unknown>;
        scanBody = post;
        await fulfillJson(route, {
          result: "ok",
          message: "Counted 1.",
          scan: {
            id: 101,
            code: "HONEYSKU1",
            kind: "honey",
            product_key: "honey",
            product_label: "Honey",
            variant: "amber",
            size: "gallon",
            size_label: "Gallon",
            batch_no: post.batch_no,
            bottling_date: post.bottling_date,
            counted_qty: 1,
            flag: "ok",
            scanned_by_name: "E2E",
            scanned_at: "2026-09-10 10:01:00",
          },
          summary: {
            event: { id: 9, status: "open" },
            rows: [],
            total_units: 1,
            flagged_units: 0,
          },
        });
        return;
      }
      await fulfillJson(route, {});
    });
    await page.route("**/rts/api/v1/sku_barcodes.php**", async route => {
      const url = new URL(route.request().url());
      if (url.searchParams.get("action") === "lookup") {
        await fulfillJson(route, {
          lookup: {
            lookup_kind: "sku",
            kind: "honey",
            label: "Honey gallon amber",
            product_label: "Honey",
            variant: "amber",
            size: "gallon",
            size_label: "Gallon",
          },
        });
        return;
      }
      await fulfillJson(route, {});
    });

    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto("./inventory/check");
    await expect(page.getByRole("heading", { name: /Check inventory/i }).first()).toBeVisible({
      timeout: 15_000,
    });

    await page.getByRole("button", { name: /Add to list/i }).click();
    await expect(page.getByTestId("inventory-check-scan-input")).toBeVisible();

    const scanInput = page.getByTestId("inventory-check-scan-input");
    await scanInput.fill("HONEYSKU1");
    await Promise.all([
      page.waitForResponse(
        res => res.url().includes("sku_barcodes.php") && res.ok(),
        { timeout: 15_000 },
      ),
      scanInput.press("Enter"),
    ]);
    await expect(page.getByTestId("inventory-check-pending-docs")).toBeVisible({ timeout: 15_000 });
    await expect(page.getByTestId("inventory-check-batch-input")).toBeVisible();
    await expect(page.getByTestId("inventory-check-bottling-date")).toBeVisible();

    await page.getByTestId("inventory-check-batch-input").fill("B-100");
    await page.getByTestId("inventory-check-bottling-date").fill("2026-09-01");
    await Promise.all([
      page.waitForResponse(
        res => res.url().includes("inventory_check.php") && res.url().includes("action=scan") && res.ok(),
        { timeout: 15_000 },
      ),
      page.getByRole("button", { name: /Confirm line/i }).click(),
    ]);

    await expect(page.getByTestId("inventory-check-pending-docs")).toHaveCount(0);
    await expect(page.getByText(/Last confirmed|Scan complete|Honey gallon/i).first()).toBeVisible();
    expect(scanBody?.batch_no).toBe("B-100");
    expect(scanBody?.bottling_date).toBe("2026-09-01");
  });

  test("Unique sticker auto-saves from metadata without docs form", async ({ page }) => {
    let scanBody: Record<string, unknown> | null = null;

    await page.route("**/rts/api/v1/auth.php**", async route => {
      await fulfillJson(route, mePayload);
    });
    await page.route("**/rts/api/v1/inventory_check.php**", async route => {
      const url = new URL(route.request().url());
      const action = url.searchParams.get("action");
      if (action === "context") {
        await fulfillJson(route, {
          user_id: 1,
          can_view_all: true,
          can_manage: true,
          event: {
            id: 9,
            status: "open",
            opened_at: "2026-09-10 10:00:00",
            opened_by: 1,
            opened_by_name: "E2E",
          },
          summary: { event: { id: 9, status: "open" }, rows: [], total_units: 0, flagged_units: 0 },
        });
        return;
      }
      if (action === "scan_log") {
        await fulfillJson(route, { rows: [], page: 1, pages: 1, total: 0 });
        return;
      }
      if (action === "scan") {
        const post = route.request().postDataJSON() as Record<string, unknown>;
        scanBody = post;
        await fulfillJson(route, {
          result: "ok",
          message: "Counted.",
          scan: {
            id: 202,
            code: "5M4W2H",
            kind: "dept",
            product_key: "brc",
            product_label: "Black Rice Coffee",
            variant: "Black Rice Coffee",
            size: "pouch",
            size_label: "Pouch",
            batch_no: post.batch_no ?? "JP081126",
            bottling_date: post.bottling_date ?? "2026-08-11",
            counted_qty: 1,
            flag: "ok",
            scanned_by_name: "E2E",
            scanned_at: "2026-09-10 10:01:00",
          },
          summary: {
            event: { id: 9, status: "open" },
            rows: [],
            total_units: 1,
            flagged_units: 0,
          },
        });
        return;
      }
      await fulfillJson(route, {});
    });
    await page.route("**/rts/api/v1/sku_barcodes.php**", async route => {
      const url = new URL(route.request().url());
      if (url.searchParams.get("action") === "lookup") {
        await fulfillJson(route, {
          lookup: {
            lookup_kind: "unique_dept",
            kind: "dept",
            label: "Black Rice Coffee",
            product_label: "Black Rice Coffee",
            code: "5M4W2H",
            batch_no: "JP081126",
            bottling_date: "2026-08-11",
            batch_date: "2026-08-11",
          },
        });
        return;
      }
      await fulfillJson(route, {});
    });

    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto("./inventory/check");
    await expect(page.getByRole("heading", { name: /Check inventory/i }).first()).toBeVisible({
      timeout: 15_000,
    });

    await page.getByRole("button", { name: /Add to list/i }).click();
    const scanInput = page.getByTestId("inventory-check-scan-input");
    await expect(scanInput).toBeVisible();
    await scanInput.fill("5M4W2H");
    await Promise.all([
      page.waitForResponse(
        res => res.url().includes("inventory_check.php") && res.url().includes("action=scan") && res.ok(),
        { timeout: 15_000 },
      ),
      scanInput.press("Enter"),
    ]);

    await expect(page.getByTestId("inventory-check-pending-docs")).toHaveCount(0);
    await expect(page.getByTestId("inventory-check-scan-success")).toContainText(/Scan complete/i);
    expect(scanBody?.code).toBe("5M4W2H");
    expect(scanBody?.batch_no).toBe("JP081126");
    expect(scanBody?.bottling_date).toBe("2026-08-11");
  });
});
