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

async function installHoneyMocks(page: Page, stage: "production" | "warehouse") {
  await page.route("**/rts/api/v1/auth.php**", async route => {
    await fulfillJson(route, mePayload);
  });
  await page.route("**/rts/api/v1/honey.php**", async route => {
    const url = new URL(route.request().url());
    const action = url.searchParams.get("action");
    if (stage === "production" && action === "production_list") {
      await fulfillJson(route, {
        rows: [
          {
            id: 11,
            supplier_code: "NF082826",
            arrival_date: "2026-08-28",
            stock_out_date: "2026-09-04",
            variant: "light",
            current_qty: "10",
            initial_qty: "10",
          },
          {
            id: 12,
            supplier_code: "EJ070126",
            arrival_date: "2026-07-01",
            stock_out_date: "2026-07-14",
            variant: "amber",
            current_qty: "-0.27",
            initial_qty: "20",
          },
        ],
      });
      return;
    }
    if (stage === "warehouse" && action === "warehouse_list") {
      await fulfillJson(route, {
        rows: [
          {
            id: 21,
            supplier_code: "NF052526",
            supplier_name: "NF",
            arrival_date: "2026-05-25",
            variant: "light",
            current_qty: "0.73",
            initial_qty: "5",
            cost_per_gallon: 100,
            selling_price: 150,
          },
        ],
      });
      return;
    }
    await fulfillJson(route, { rows: [] });
  });
  await page.route("**/rts/api/v1/settings.php**", async route => {
    await fulfillJson(route, { ml_per_gallon: 3785.411784 });
  });
  await page.route("**/rts/api/v1/bottle_barcodes.php**", async route => {
    await fulfillJson(route, { rows: [], page: 1, pages: 1 });
  });
}

test.describe("Honey lot gallon progress", () => {
  test("production table shows progress for remaining and overdrawn lots", async ({ page }) => {
    await installHoneyMocks(page, "production");
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto("./inventory/raw-materials/honey/production");
    await expect(page.getByRole("heading", { name: /Raw Honey/i }).first()).toBeVisible({
      timeout: 15_000,
    });

    const table = page.getByTestId("honey-production-desktop-table");
    const full = table.getByTestId("honey-production-gal-progress-11");
    await expect(full).toBeVisible();
    await expect(full).toContainText("10");
    await expect(full).toContainText("100%");

    const over = table.getByTestId("honey-production-gal-progress-12");
    await expect(over).toBeVisible();
    await expect(over).toContainText("over");
    await expect(table.getByText("Overdrawn").first()).toBeVisible();
  });

  test("warehouse table shows gallon progress", async ({ page }) => {
    await installHoneyMocks(page, "warehouse");
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto("./inventory/raw-materials/honey/warehouse");
    await expect(page.getByRole("heading", { name: /Raw Honey/i }).first()).toBeVisible({
      timeout: 15_000,
    });

    const table = page.getByTestId("honey-warehouse-desktop-table");
    const bar = table.getByTestId("honey-warehouse-gal-progress-21");
    await expect(bar).toBeVisible();
    await expect(bar).toContainText("0.7");
    await expect(bar).toContainText("% of");
  });
});
