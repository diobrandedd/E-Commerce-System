import { expect, test, type Route } from "@playwright/test";

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

test.describe("Deliveries photo-only", () => {
  test("Allows tracking without CSR parcel and does not call ship", async ({ page }) => {
    let shipCalled = false;

    await page.route("**/rts/api/v1/auth.php**", async route => {
      await fulfillJson(route, mePayload);
    });
    await page.route("**/rts/api/v1/deliveries.php**", async route => {
      const url = new URL(route.request().url());
      const action = url.searchParams.get("action");
      if (action === "batch_for_date") {
        await fulfillJson(route, { batch_id: 1, delivery_date: "2026-09-11", item_count: 0 });
        return;
      }
      if (action === "parcel_by_tracking") {
        await fulfillJson(route, {
          tracking_normalized: "SPX123",
          parcel: null,
          existing_recordings: [],
          already_recorded: false,
        });
        return;
      }
      if (action === "ship_parcel") {
        shipCalled = true;
        await fulfillJson(route, { parcel: null, already_shipped: false });
        return;
      }
      await fulfillJson(route, {});
    });

    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto("./deliveries");
    await expect(page.getByRole("heading", { name: /Deliveries/i }).first()).toBeVisible({
      timeout: 15_000,
    });
    await expect(page.getByText(/no inventory change/i)).toBeVisible();
    await expect(page.getByText(/CSR waybill list not required/i)).toBeVisible();

    await page.getByRole("button", { name: /Scan tracking/i }).click();
    await expect(page.getByText(/enter tracking manually/i)).toBeVisible({ timeout: 10_000 });
    const input = page.locator("label").filter({ hasText: /enter tracking manually/i }).locator("input");
    await input.fill("SPX123");
    await page.getByRole("button", { name: /Use tracking/i }).click();

    await expect(page.getByTestId("deliveries-photo-preview")).toBeVisible({ timeout: 10_000 });
    await expect(page.getByText("SPX123")).toBeVisible();
    await expect(page.getByText(/^Contents$/i)).toHaveCount(0);
    expect(shipCalled).toBe(false);
  });
});
