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

function brcDetail(overdrawn: boolean) {
  if (overdrawn) {
    return {
      slug: "black-rice",
      product_key: "brc",
      title: "Black Rice",
      stock_name: "Black Rice Coffee — raw",
      unit: "bag",
      inventory_unit: "pouch",
      sell_units_per_bag: 10,
      bags_on_hand: -0.05,
      finished_on_hand: 40,
      finished_label: "Black Rice Coffee",
      wip_tea_bags: null,
      tea_bags_per_pouch: null,
      supports_wip: false,
      bag_pool_workflow: true,
      bag_pool: {
        bags_on_hand: -0.05,
        bags_received_gross: 1,
        grams_remaining: -50,
        grams_capacity: 1000,
        grams_per_bag: 1000,
        grams_per_pouch: 100,
        grams_per_tea_bag: null,
        tea_bags_per_pouch: null,
        pouches_per_bag: 10,
        overdrawn: true,
      },
      produce_action: null,
      entries: [],
      production_entries: [],
    };
  }
  return {
    slug: "black-rice",
    product_key: "brc",
    title: "Black Rice",
    stock_name: "Black Rice Coffee — raw",
    unit: "bag",
    inventory_unit: "pouch",
    sell_units_per_bag: 10,
    bags_on_hand: 1.5,
    finished_on_hand: 12,
    finished_label: "Black Rice Coffee",
    wip_tea_bags: null,
    tea_bags_per_pouch: null,
    supports_wip: false,
    bag_pool_workflow: true,
    bag_pool: {
      bags_on_hand: 1.5,
      bags_received_gross: 2,
      grams_remaining: 1500,
      grams_capacity: 2000,
      grams_per_bag: 1000,
      grams_per_pouch: 100,
      grams_per_tea_bag: null,
      tea_bags_per_pouch: null,
      pouches_per_bag: 10,
      overdrawn: false,
    },
    produce_action: null,
    entries: [],
    production_entries: [],
  };
}

async function installBrcMocks(page: Page, overdrawn: boolean) {
  await page.route("**/rts/api/v1/auth.php**", async route => {
    await fulfillJson(route, mePayload);
  });
  await page.route("**/rts/api/v1/honey.php**", async route => {
    const url = new URL(route.request().url());
    if (url.searchParams.get("action") === "dept_raw_detail") {
      await fulfillJson(route, brcDetail(overdrawn));
      return;
    }
    await fulfillJson(route, {});
  });
  await page.route("**/rts/api/v1/dept_pouch_barcodes.php**", async route => {
    await fulfillJson(route, { count: 0 });
  });
}

async function openBrc(page: Page) {
  await page.goto("./inventory/raw-materials/black-rice");
  await expect(page.getByRole("heading", { name: /Black Rice/i }).first()).toBeVisible({
    timeout: 15_000,
  });
}

test.describe("BRC bag pool UI", () => {
  test("shows bag pool progress and bag-pool Do next copy", async ({ page }, testInfo) => {
    await installBrcMocks(page, false);
    await openBrc(page);

    const pool = page.getByTestId("brc-bag-pool");
    await expect(pool).toBeVisible();
    await expect(pool.getByText(/1,?500(\.0)? g/)).toBeVisible();
    await expect(pool.getByText(/100(\.0)? g per pouch/i)).toBeVisible();
    await expect(page.getByText(/Stock raw bags into the bag pool/i)).toBeVisible();
    await expect(page.getByRole("button", { name: /^Produce pouches$/i })).toHaveCount(0);

    await page.screenshot({
      path: `test-results/brc-bag-pool-${testInfo.project.name}.png`,
      fullPage: true,
    });
  });

  test("overdrawn pool shows warning state", async ({ page }) => {
    await installBrcMocks(page, true);
    await openBrc(page);
    await expect(page.getByTestId("brc-bag-pool")).toContainText(/overdrawn/i);
    await expect(page.getByText(/Scans can continue while overdrawn/i)).toBeVisible();
  });
});
