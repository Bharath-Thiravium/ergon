const { test, expect } = require('@playwright/test');

// Define pages to test - adjust URLs based on your Laragon setup
const PAGES = [
  { name: 'Dashboard-Admin', url: 'http://localhost/ergon/views/dashboard/admin.php' },
  { name: 'Dashboard-User', url: 'http://localhost/ergon/views/dashboard/user.php' },
  { name: 'Users-View', url: 'http://localhost/ergon/views/users/view.php' },
  { name: 'Project-Management', url: 'http://localhost/ergon/views/admin/project_management.php' },
  { name: 'Attendance-Admin', url: 'http://localhost/ergon/views/attendance/admin_index.php' },
  { name: 'Daily-Planner', url: 'http://localhost/ergon/views/daily_workflow/unified_daily_planner.php' },
  { name: 'Analytics-Dashboard', url: 'http://localhost/ergon/views/analytics/dashboard.php' }
];

test.describe('ERGON UI Visual Regression Tests', () => {
  
  // Configure test timeout and viewport
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });
  });

  for (const pageInfo of PAGES) {
    test(`Visual check → ${pageInfo.name}`, async ({ page }) => {
      
      // Navigate to page with extended timeout
      await page.goto(pageInfo.url, { 
        waitUntil: 'networkidle',
        timeout: 30000 
      });

      // Wait for CSS to load and animations to settle
      await page.waitForTimeout(2000);

      // Hide dynamic elements that change between runs
      await page.addStyleTag({
        content: `
          .notification-time,
          .timestamp,
          [data-time],
          .session-id,
          .csrf-token {
            visibility: hidden !important;
          }
        `
      });

      // Take full page screenshot
      const screenshot = await page.screenshot({
        path: `tests/visual/results/${pageInfo.name}-full.png`,
        fullPage: true,
        animations: 'disabled'
      });

      // Compare with baseline
      await expect(screenshot).toMatchSnapshot(`${pageInfo.name}-full.png`);

      // Test specific UI components
      
      // Header component
      const header = page.locator('.main-header');
      if (await header.isVisible()) {
        await expect(header).toHaveScreenshot(`${pageInfo.name}-header.png`);
      }

      // Card components
      const cards = page.locator('.card, .kpi-card, .admin-card, .user-card');
      const cardCount = await cards.count();
      if (cardCount > 0) {
        for (let i = 0; i < Math.min(cardCount, 5); i++) {
          await expect(cards.nth(i)).toHaveScreenshot(`${pageInfo.name}-card-${i}.png`);
        }
      }

      // Table components
      const tables = page.locator('.table-responsive, .table');
      if (await tables.first().isVisible()) {
        await expect(tables.first()).toHaveScreenshot(`${pageInfo.name}-table.png`);
      }

      // Button groups
      const buttonGroups = page.locator('.btn-group, .ab-container');
      if (await buttonGroups.first().isVisible()) {
        await expect(buttonGroups.first()).toHaveScreenshot(`${pageInfo.name}-buttons.png`);
      }
    });
  }

  // Test dark theme if available
  test('Dark theme visual check', async ({ page }) => {
    await page.goto('http://localhost/ergon/views/dashboard/admin.php');
    
    // Try to enable dark theme
    const themeToggle = page.locator('[data-theme-toggle], .theme-toggle, #darkModeToggle');
    if (await themeToggle.isVisible()) {
      await themeToggle.click();
      await page.waitForTimeout(1000);
      
      const screenshot = await page.screenshot({
        path: 'tests/visual/results/dark-theme.png',
        fullPage: true,
        animations: 'disabled'
      });
      
      await expect(screenshot).toMatchSnapshot('dark-theme.png');
    }
  });

  // Test responsive layouts
  test('Mobile responsive check', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE
    
    await page.goto('http://localhost/ergon/views/dashboard/admin.php');
    await page.waitForTimeout(1500);
    
    const screenshot = await page.screenshot({
      path: 'tests/visual/results/mobile-responsive.png',
      fullPage: true,
      animations: 'disabled'
    });
    
    await expect(screenshot).toMatchSnapshot('mobile-responsive.png');
  });
});