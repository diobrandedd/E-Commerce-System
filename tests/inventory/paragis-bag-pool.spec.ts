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

async function installAuthMock(page: Page) {
  await page.route("**/rts/api/v1/auth.php**", async route => {
    await fulfillJson(route, mePayload);
  });
}

function bagPoolDetail(overdrawn: boolean) {
  if (overdrawn) {
    return {
      slug: "paragis",
      product_key: "pt",
      title: "Dried Paragis",
      stock_name: "Paragis leaf — raw",
      unit: "bag",
      inventory_unit: "pouch",
      sell_units_per_bag: 20.83,
      bags_on_hand: -0.05,
      finished_on_hand: 40,
      finished_label: "Paragis Tea",
      wip_tea_bags: 0,
      tea_bags_per_pouch: 24,
      supports_wip: false,
      bag_pool_workflow: true,
      bag_pool: {
        bags_on_hand: -0.05,
        bags_received_gross: 1,
        grams_remaining: -50,
        grams_capacity: 1000,
        grams_per_bag: 1000,
        grams_per_pouch: 48,
        grams_per_tea_bag: 2,
        tea_bags_per_pouch: 24,
        pouches_per_bag: 20.83,
        overdrawn: true,
      },
      produce_action: null,
      entries: [],
      production_entries: [],
    };
  }
  return {
    slug: "paragis",
    product_key: "pt",
    title: "Dried Paragis",
    stock_name: "Paragis leaf — raw",
    unit: "bag",
    inventory_unit: "pouch",
    sell_units_per_bag: 20.83,
    bags_on_hand: 1.5,
    finished_on_hand: 12,
    finished_label: "Paragis Tea",
    wip_tea_bags: 0,
    tea_bags_per_pouch: 24,
    supports_wip: false,
    bag_pool_workflow: true,
    bag_pool: {
      bags_on_hand: 1.5,
      bags_received_gross: 2,
      grams_remaining: 1500,
      grams_capacity: 2000,
      grams_per_bag: 1000,
      grams_per_pouch: 48,
      grams_per_tea_bag: 2,
      tea_bags_per_pouch: 24,
      pouches_per_bag: 20.83,
      overdrawn: false,
    },
    produce_action: null,
    entries: [],
    production_entries: [],
  };
}

async function installParagisMocks(page: Page, overdrawn: boolean) {
  await installAuthMock(page);
  await page.route("**/rts/api/v1/honey.php**", async route => {
    const url = new URL(route.request().url());
    if (url.searchParams.get("action") === "dept_raw_detail") {
      await fulfillJson(route, bagPoolDetail(overdrawn));
      return;
    }
    await fulfillJson(route, {});
  });
  await page.route("**/rts/api/v1/dept_pouch_barcodes.php**", async route => {
    await fulfillJson(route, { count: 0 });
  });
}

async function openParagis(page: Page) {
  await page.goto("./inventory/raw-materials/paragis");
  await expect(page.getByRole("heading", { name: /Dried Paragis|Paragis/i }).first()).toBeVisible({
    timeout: 15_000,
  });
}

test.describe("Paragis bag pool UI", () => {
  test("shows bag pool progress and hides WIP actions", async ({ page }, testInfo) => {
    await installParagisMocks(page, false);
    await openParagis(page);

    const pool = page.getByTestId("paragis-bag-pool");
    await expect(pool).toBeVisible();
    await expect(pool.getByText(/1,?500(\.0)? g/)).toBeVisible();
    await expect(pool.getByText(/48(\.0)? g per pouch/i)).toBeVisible();
    await expect(page.getByText(/Stock raw bags into the bag pool/i)).toBeVisible();
    await expect(page.getByRole("button", { name: /Use raw bags/i })).toHaveCount(0);
    await expect(page.getByRole("button", { name: /WIP waste/i })).toHaveCount(0);
    await expect(page.getByText(/Tea bags \(WIP\)/i)).toHaveCount(0);

    await page.screenshot({
      path: `test-results/paragis-bag-pool-${testInfo.project.name}.png`,
      fullPage: true,
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("./inventory/raw-materials/paragis");
    await expect(page.getByTestId("paragis-bag-pool")).toBeVisible();
    await page.screenshot({
      path: `test-results/paragis-bag-pool-${testInfo.project.name}-mobile.png`,
      fullPage: true,
    });
  });

  test("overdrawn pool shows warning state", async ({ page }, testInfo) => {
    await installParagisMocks(page, true);
    await openParagis(page);
    await expect(page.getByTestId("paragis-bag-pool")).toContainText(/overdrawn/i);
    await expect(page.getByText(/Scans can continue while overdrawn/i)).toBeVisible();
    await page.screenshot({
      path: `test-results/paragis-bag-pool-overdrawn-${testInfo.project.name}.png`,
      fullPage: true,
    });
  });
});
