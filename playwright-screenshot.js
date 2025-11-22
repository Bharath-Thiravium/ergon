const { chromium } = require('playwright');
(async ()=>{
  const browser = await chromium.launch({ args: ['--no-sandbox'] });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await page.goto('http://localhost/ergon/finance', { waitUntil: 'networkidle' });
  await page.screenshot({ path: 'finance_after.png', fullPage: true });
  console.log('screenshot saved: finance_after.png');
  await browser.close();
})();
