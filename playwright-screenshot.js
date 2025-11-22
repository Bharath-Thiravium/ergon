const { chromium } = require('playwright');
// Usage: node playwright-screenshot.js <output_filename>
const out = process.argv[2] || 'screenshot.png';
(async ()=>{
  const browser = await chromium.launch({ args: ['--no-sandbox'] });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await page.goto('http://localhost/ergon/finance', { waitUntil: 'networkidle' });
  await page.screenshot({ path: out, fullPage: true });
  console.log('screenshot saved:', out);
  await browser.close();
})();
