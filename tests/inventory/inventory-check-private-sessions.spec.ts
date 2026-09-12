import { expect, test, type Route } from "@playwright/test";

const mockUser = {
  id: 7,
  username: "test_1",
  name: "Test One",
  email: "t@example.com",
  role: "prod_manager",
  php_role: "prod_manager",
  dept: "all",
  dept_id: null,
  dept_name: "All",
  permissions: ["inventory.check.scan", "inventory.check.manage", "inventory.check.view_all"],
};

const mePayload = {
  authenticated: true,
  mode: "user" as const,
  csrf: "e2e-csrf",
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

test.describe("Inventory check private session UX", () => {
  test("Add modal has batch field; history compare selected for view_all", async ({ page }) => {
    let previewMerged = false;
    await page.route("**/rts/api/v1/auth.php**", async route => {
      await fulfillJson(route, mePayload);
    });
    await page.route("**/rts/api/v1/inventory_check.php**", async route => {
      const url = new URL(route.request().url());
      const action = url.searchParams.get("action");
      const postAction = route.request().method() === "POST"
        ? new URL(route.request().url()).searchParams.get("action")
        : null;
      const act = action || postAction;

      if (act === "context") {
        await fulfillJson(route, {
          can_view_all: true,
          can_manage: true,
          user_id: 7,
          event: {
            id: 11,
            count_date: "2026-09-11",
            status: "open",
            opened_by: 7,
            opened_by_name: "Test One",
            opened_at: "2026-09-11 09:00:00",
            closed_by_name: null,
            closed_at: null,
            scan_count: 0,
            scanner_count: 0,
            compare_run_count: 0,
            note: null,
          },
          summary: null,
        });
        return;
      }
      if (act === "scan_log") {
        await fulfillJson(route, { rows: [], page: 1, pages: 1, total: 0 });
        return;
      }
      if (act === "event_list") {
        await fulfillJson(route, {
          rows: [
            {
              id: 1,
              count_date: "2026-09-11",
              status: "closed",
              opened_by: 7,
              opened_by_name: "Test One",
              opened_at: "2026-09-11 08:00:00",
              closed_by_name: "Test One",
              closed_at: "2026-09-11 09:00:00",
              scan_count: 2,
              scanner_count: 1,
              compare_run_count: 1,
              note: null,
            },
            {
              id: 2,
              count_date: "2026-09-10",
              status: "closed",
              opened_by: 4,
              opened_by_name: "Other",
              opened_at: "2026-09-10 08:00:00",
              closed_by_name: "Other",
              closed_at: "2026-09-10 09:00:00",
              scan_count: 3,
              scanner_count: 1,
              compare_run_count: 1,
              note: null,
            },
          ],
          total: 2,
          page: 1,
          per_page: 25,
        });
        return;
      }
      if (act === "compare_preview_merged") {
        previewMerged = true;
        await fulfillJson(route, {
          preview: true,
          run: {
            id: 0,
            event_id: 1,
            event_ids: [1, 2],
            scope: "Merged · Honey",
            line_count: 1,
            mismatch_count: 1,
            counted_total: 10,
            system_total: 90,
            run_by_name: "",
            run_at: "2026-09-11 10:00:00",
          },
          lines: [
            {
              id: 1,
              kind: "honey",
              product_key: "honey",
              product_label: "Honey",
              packaging_row_id: null,
              supplier_code: null,
              batch_no: "B1",
              bottling_date: null,
              variant: "amber",
              size: "liter",
              size_label: "1L",
              counted_qty: 10,
              system_qty: 90,
              variance: -80,
            },
          ],
          event: {
            id: 1,
            count_date: "2026-09-11",
            status: "closed",
            opened_by: 7,
            opened_by_name: "Test One",
            opened_at: "2026-09-11 08:00:00",
            closed_by_name: "Test One",
            closed_at: "2026-09-11 09:00:00",
            scan_count: 2,
            scanner_count: 1,
            compare_run_count: 1,
            note: null,
          },
        });
        return;
      }
      await fulfillJson(route, {});
    });

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("./inventory/check");
    await expect(page.getByRole("heading", { name: /Check inventory/i }).first()).toBeVisible({
      timeout: 15_000,
    });

    await page.getByRole("button", { name: /Add to list/i }).click();
    await expect(page.getByTestId("inventory-check-batch-input")).toBeVisible();
    await page.getByRole("button", { name: /^Done$/i }).click();

    await page.getByRole("button", { name: /^History$/i }).click();
    await expect(page.getByText(/All audit history/i)).toBeVisible();
    await page.getByRole("checkbox", { name: /Select audit 2026-09-11/i }).check();
    await page.getByRole("checkbox", { name: /Select audit 2026-09-10/i }).check();
    await page.getByRole("button", { name: /Compare selected/i }).click();
    await expect(page.getByText(/Audit vs system inventory|Merged/i).first()).toBeVisible({
      timeout: 10_000,
    });
    expect(previewMerged).toBe(true);
  });
});
