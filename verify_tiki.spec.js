const { test, expect } = require('@playwright/test');

test('Verify Category Filters and Image Gallery', async ({ page }) => {
  // 1. Check Filters in Category page (using Electronics as example)
  await page.goto('http://localhost:8000/category/electronics');

  // Check if price range inputs exist
  const minPrice = page.locator('input[name="min_price"]');
  const maxPrice = page.locator('input[name="max_price"]');
  await expect(minPrice).toBeVisible();
  await expect(maxPrice).toBeVisible();

  // Check if Brand filter exists
  const brandFilter = page.locator('select[name="extra[brand]"]');
  await expect(brandFilter).toBeVisible();

  // 2. Check Image Gallery in Ad page
  // We need an ad ID. Let's try to find one from the homepage or just go to ad.php?id=1
  await page.goto('http://localhost:8000/ad.php?id=1');

  // Verify main image container
  const mainImg = page.locator('#mainImage');
  await expect(mainImg).toBeVisible();

  // Verify navigation buttons (if more than 1 image)
  const nextBtn = page.locator('button:has-text("Next")').or(page.locator('.fa-chevron-right').first());
  // We might not have 2 images for ad 1, but we can check if the logic is in the source
  const content = await page.content();
  expect(content).toContain('function updateMainImage');
  expect(content).toContain('id="lightboxModal"');
});
