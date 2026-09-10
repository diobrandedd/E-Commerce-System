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

test.describe("Bag-pool deduct Settings toggles", () => {
  test("Settings BOM cards show deduct switches", async ({ page }) => {
    await page.route("**/rts/api/v1/auth.php**", async route => {
      await fulfillJson(route, mePayload);
    });
    await page.route("**/rts/api/v1/settings.php**", async route => {
      const url = new URL(route.request().url());
      const action = url.searchParams.get("action");
      if (action === "pos_pins_list") {
        await fulfillJson(route, { pins: [] });
        return;
      }
      if (action === "honey_volume_get") {
        await fulfillJson(route, { ml_per_gallon: 3785.411784, default_ml_per_gallon: 3785.411784 });
        return;
      }
      if (action === "pt_bom_get") {
        await fulfillJson(route, {
          grams_per_tea_bag: 2,
          tea_bags_per_pouch: 24,
          grams_per_pouch: 48,
          bag_pool_deduct: true,
          default_grams_per_tea_bag: 2,
          default_tea_bags_per_pouch: 24,
        });
        return;
      }
      if (action === "brc_bom_get") {
        await fulfillJson(route, {
          grams_per_pouch: 100,
          grams_per_bag: 1000,
          pouches_per_bag: 10,
          bag_pool_deduct: false,
          default_grams_per_pouch: 100,
          default_grams_per_bag: 1000,
        });
        return;
      }
      if (action === "jar_cacao_bom_get") {
        await fulfillJson(route, {
          grams_per_jar: 220,
          grams_per_bag: 1000,
          jars_per_bag: 4.5455,
          bag_pool_deduct: true,
          default_grams_per_jar: 220,
          default_grams_per_bag: 1000,
        });
        return;
      }
      await fulfillJson(route, {});
    });
    await page.route("**/rts/api/v1/sku_barcodes.php**", async route => {
      await fulfillJson(route, { rows: [] });
    });

    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto("./settings");
    await expect(page.getByRole("heading", { name: /Settings/i }).first()).toBeVisible({
      timeout: 15_000,
    });

    await expect(page.getByTestId("pt-bag-pool-deduct-switch")).toBeVisible();
    await expect(page.getByTestId("brc-bag-pool-deduct-switch")).toBeVisible();
    await expect(page.getByTestId("cacao-bag-pool-deduct-switch")).toBeVisible();
    await expect(page.getByTestId("brc-bag-pool-deduct-switch")).toHaveAttribute(
      "data-state",
      "unchecked",
    );
  });

  test("raw page shows deduct-off note when flag false", async ({ page }) => {
    await page.route("**/rts/api/v1/auth.php**", async route => {
      await fulfillJson(route, mePayload);
    });
    await page.route("**/rts/api/v1/honey.php**", async route => {
      const url = new URL(route.request().url());
      if (url.searchParams.get("action") === "dept_raw_detail") {
        await fulfillJson(route, {
          slug: "paragis",
          product_key: "pt",
          title: "Paragis Tea",
          stock_name: "Paragis — raw",
          unit: "bag",
          inventory_unit: "pouch",
          sell_units_per_bag: 20.8333,
          bags_on_hand: 1,
          finished_on_hand: 10,
          finished_label: "Paragis Tea",
          wip_tea_bags: null,
          tea_bags_per_pouch: 24,
          supports_wip: false,
          bag_pool_workflow: true,
          bag_pool_deduct_enabled: false,
          bag_pool: {
            bags_on_hand: 1,
            bags_received_gross: 1,
            grams_remaining: 1000,
            grams_capacity: 1000,
            grams_per_bag: 1000,
            grams_per_pouch: 48,
            grams_per_tea_bag: 2,
            tea_bags_per_pouch: 24,
            pouches_per_bag: 20.8333,
            overdrawn: false,
          },
          produce_action: null,
          entries: [],
          production_entries: [],
        });
        return;
      }
      await fulfillJson(route, {});
    });
    await page.route("**/rts/api/v1/dept_pouch_barcodes.php**", async route => {
      await fulfillJson(route, { count: 0 });
    });

    await page.goto("./inventory/raw-materials/paragis");
    await expect(page.getByTestId("paragis-bag-pool")).toBeVisible({ timeout: 15_000 });
    await expect(page.getByTestId("paragis-bag-pool-deduct-off")).toContainText(/Scan deduct off/i);
  });
});
