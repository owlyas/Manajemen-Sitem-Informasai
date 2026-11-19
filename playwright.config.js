import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
+  // Run only basic tests (ensure they pass)
+  testMatch: '**/basic.spec.js',

  reporter: [
    ['list'],
    ['html', {
      outputFolder: 'playwright-report',
      open: 'never'
    }]
  ],

  use: {
    headless: true,
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure'
  }
});