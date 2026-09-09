import { expect, test, type Page } from "@playwright/test";

const username = process.env.PYX_TEST_USER ?? "admin";
const password = process.env.PYX_TEST_PASS ?? "admin123";

async function signIn(page: Page) {
  await page.goto("./");
  const usernameField = page.getByLabel("Username");
  if (await usernameField.isVisible().catch(() => false)) {
    await usernameField.fill(username);
    await page.getByLabel("Password").fill(password);
    await page.getByRole("button", { name: "Sign in" }).click();
    await expect(usernameField).toBeHidden({ timeout: 15_000 });
  }
}

async function openRoute(page: Page, path: string) {
  await page.goto(`.${path}`);
  await expect(page.locator("main")).toBeVisible({ timeout: 15_000 });
}

async function expectNoDocumentOverflow(page: Page) {
  const sizes = await page.evaluate(() => ({
    viewport: window.innerWidth,
    document: document.documentElement.scrollWidth,
  }));
  expect(sizes.document).toBeLessThanOrEqual(sizes.viewport);
}

test.describe("raw materials responsive UI", () => {
  test.beforeEach(async ({ page }) => {
    await signIn(page);
  });

  test("navigates every Raw Honey stage with the correct responsive inventory view", async ({ page }, testInfo) => {
    const isMobile = testInfo.project.name === "mobile-chrome";
    const stages = [
      { path: "/inventory/raw-materials/honey/warehouse", mobile: "honey-warehouse-mobile-cards", desktop: "honey-warehouse-desktop-table" },
      { path: "/inventory/raw-materials/honey/production", mobile: "honey-production-mobile-cards", desktop: "honey-production-desktop-table" },
      { path: "/inventory/raw-materials/honey/packaging", mobile: "honey-packaging-mobile-cards", desktop: "honey-packaging-desktop-table" },
    ];

    for (const stage of stages) {
      await openRoute(page, stage.path);
      await expect(page.getByRole("heading", { name: "Raw Honey" })).toBeVisible();
      if (isMobile) {
        await expect(page.getByTestId(stage.mobile)).toBeVisible();
        await expect(page.getByTestId(stage.desktop)).toBeHidden();
      } else {
        await expect(page.getByTestId(stage.mobile)).toBeHidden();
        await expect(page.getByTestId(stage.desktop)).toBeVisible();
      }
      await expectNoDocumentOverflow(page);
    }

    if (isMobile) {
      const tabHeights = await page.getByRole("navigation", { name: "Honey inventory stages" }).getByRole("link").evaluateAll(
        links => links.map(link => link.getBoundingClientRect().height),
      );
      expect(tabHeights.every(height => height >= 44)).toBeTruthy();
    }
  });

  test("hands off Honey stages through the fluid shared transition", async ({ page }, testInfo) => {
    await openRoute(page, "/inventory/raw-materials/honey/warehouse");
    const supportsViewTransitions = await page.evaluate(() => "startViewTransition" in document);
    const isMobile = testInfo.project.name === "mobile-chrome";
    let mobileIndicatorStart = 0;
    if (isMobile) {
      const indicator = page.getByTestId("honey-stage-indicator");
      await expect(indicator).toBeVisible();
      mobileIndicatorStart = (await indicator.boundingBox())?.x ?? 0;
    }

    await page.evaluate(() => {
      const transitionDocument = document as Document & {
        startViewTransition?: (update: () => void) => { finished: Promise<void> };
      };
      const transitionWindow = window as Window & { __honeyStageTransitionCalls?: number };
      const original = transitionDocument.startViewTransition?.bind(document);
      transitionWindow.__honeyStageTransitionCalls = 0;
      if (original) {
        transitionDocument.startViewTransition = update => {
          transitionWindow.__honeyStageTransitionCalls =
            (transitionWindow.__honeyStageTransitionCalls ?? 0) + 1;
          return original(update);
        };
      }
    });

    await page
      .getByRole("navigation", { name: "Honey inventory stages" })
      .getByRole("link", { name: "Production" })
      .click();

    await expect(page).toHaveURL(/\/inventory\/raw-materials\/honey\/production$/);
    await expect(page.getByTestId("honey-stage-content")).toBeVisible();
    await expect(
      page
        .getByRole("navigation", { name: "Honey inventory stages" })
        .getByRole("link", { name: "Production" }),
    ).toHaveCSS("view-transition-name", "honey-stage-tab");
    if (isMobile) {
      const indicator = page.getByTestId("honey-stage-indicator");
      const activeLink = page
        .getByRole("navigation", { name: "Honey inventory stages" })
        .getByRole("link", { name: "Production" });
      await expect.poll(async () => {
        const indicatorBox = await indicator.boundingBox();
        const activeBox = await activeLink.boundingBox();
        return Math.abs((indicatorBox?.x ?? 0) - (activeBox?.x ?? 0));
      }).toBeLessThanOrEqual(1);
      expect((await indicator.boundingBox())?.x ?? 0).toBeGreaterThan(mobileIndicatorStart + 20);
    }

    const transitionCalls = await page.evaluate(
      () => (window as Window & { __honeyStageTransitionCalls?: number }).__honeyStageTransitionCalls ?? 0,
    );
    const usesNativeTransition = !isMobile && supportsViewTransitions;
    expect(transitionCalls).toBe(usesNativeTransition ? 1 : 0);

    await page.emulateMedia({ reducedMotion: "reduce" });
    await expect.poll(() => page.evaluate(() => matchMedia("(prefers-reduced-motion: reduce)").matches)).toBe(true);
    await page
      .getByRole("navigation", { name: "Honey inventory stages" })
      .getByRole("link", { name: "Packaging" })
      .click();
    await expect(page).toHaveURL(/\/inventory\/raw-materials\/honey\/packaging$/);
    const reducedMotionCalls = await page.evaluate(
      () => (window as Window & { __honeyStageTransitionCalls?: number }).__honeyStageTransitionCalls ?? 0,
    );
    expect(reducedMotionCalls).toBe(transitionCalls);
  });

  test("explains that Paragis barcode stock-in is inventory-only", async ({ page }, testInfo) => {
    await openRoute(page, "/inventory/raw-materials/paragis");
    await expect(page.getByRole("heading", { name: "Dried Paragis" })).toBeVisible();
    await expect(page.getByText(/Barcode stock-in does not deduct raw bags, WIP, or packaging/i)).toBeVisible();

    if (testInfo.project.name === "mobile-chrome") {
      await expect(page.getByRole("button", { name: /Scan pouches to inventory/i })).toBeVisible();
      await page.getByRole("button", { name: "More actions" }).click();
      const actions = page.getByRole("dialog", { name: "Raw material actions" });
      await expect(actions.getByRole("button", { name: /Use raw bags/i })).toBeVisible();
      await page.keyboard.press("Escape");
      await expect(actions).toBeHidden();
    }

    await page.getByRole("button", { name: "Guide" }).click();
    const guide = page.getByRole("dialog", { name: "Paragis production guide" });
    await expect(guide).toBeVisible();
    await expect(guide.getByText(/does not require or deduct Raw bags, Tea bags \(WIP\), or packaging/i)).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(guide).toBeHidden();
  });

  test("uses semantic Packaging detail actions", async ({ page }, testInfo) => {
    await page.route("**/rts/api/v1/bottle_barcodes.php?action=packaging_scan_report*", async route => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          ok: true,
          error: null,
          data: {
            dates: [],
            scan_date: "all",
            totals: { total: 10, by_variant: [] },
            by_scanner: [],
            rows: [{
              id: 77,
              supplier_code: "TEST-LOT",
              batch_no: "TEST-BATCH",
              bottling_date: "2026-09-09",
              variant: "light",
              current_liter: "4",
              initial_liter: "4",
              current_250ml: "3",
              initial_250ml: "3",
              current_350ml: "2",
              initial_350ml: "2",
              current_gallon: "1",
              initial_gallon: "1",
              scanned_liter: "4",
              scanned_250ml: "3",
              scanned_350ml: "2",
              scanned_gallon: "1",
            }],
          },
        }),
      });
    });

    await openRoute(page, "/inventory/raw-materials/honey/packaging");
    const detailName = testInfo.project.name === "mobile-chrome" ? "View scan details" : "Details";
    const detailButton = page.getByRole("button", { name: detailName });
    await expect(detailButton).toBeVisible();
    if (testInfo.project.name === "mobile-chrome") {
      expect((await detailButton.boundingBox())?.height ?? 0).toBeGreaterThanOrEqual(44);
    }
  });

  test("shows an actionable production load error", async ({ page }) => {
    await page.route("**/rts/api/v1/honey.php?action=production_list*", async route => {
      await route.fulfill({
        status: 503,
        contentType: "application/json",
        body: JSON.stringify({ ok: false, data: null, error: "Test production outage" }),
      });
    });

    await openRoute(page, "/inventory/raw-materials/honey/production");
    await expect(page.getByRole("alert")).toContainText("Test production outage");
    await expect(page.getByRole("button", { name: "Retry loading" })).toBeVisible();
  });
});
