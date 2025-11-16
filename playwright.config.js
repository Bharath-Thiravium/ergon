// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/visual',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html', { outputFolder: 'tests/visual/playwright-report' }],
    ['json', { outputFile: 'tests/visual/results.json' }]
  ],
  
  use: {
    baseURL: 'http://localhost/ergon',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure'
  },

  // Configure visual comparison
  expect: {
    // Threshold for visual comparisons (0-1, where 0 is identical)
    toHaveScreenshot: { 
      threshold: 0.2,  // Allow minor anti-aliasing differences
      mode: 'strict'
    },
    toMatchSnapshot: { 
      threshold: 0.2,
      mode: 'strict'
    }
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    
    // Uncomment for cross-browser testing
    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },
    
    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },
    
    // Mobile testing
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
  ],

  webServer: {
    // Assumes Laragon is running on localhost
    command: 'echo "Laragon should be running on localhost"',
    url: 'http://localhost/ergon',
    reuseExistingServer: true,
    timeout: 5000
  },
});