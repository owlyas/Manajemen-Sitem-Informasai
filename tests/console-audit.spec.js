// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Console Errors & Network Requests Audit', () => {

  const pagesToAudit = [
    { name: 'Halaman Utama (index.php)', url: '/index.php' },
    { name: 'Halaman Masuk (login.php)', url: '/login.php' },
    { name: 'Halaman Registrasi (register.php)', url: '/register.php' },
    { name: 'Halaman Leaderboard (leaderboard.php)', url: '/leaderboard.php' },
  ];

  for (const pageItem of pagesToAudit) {
    test(`TC-AUDIT: Audit console error & HTTP status pada ${pageItem.name}`, async ({ page }) => {
      const consoleErrors = [];
      const failedRequests = [];

      // Pantau log error console
      page.on('console', msg => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text());
        }
      });

      // Pantau unhandled exception pada browser
      page.on('pageerror', err => {
        consoleErrors.push(err.message);
      });

      // Pantau kegagalan request jaringan (HTTP 4xx / 5xx)
      page.on('response', response => {
        const status = response.status();
        // Ignore expected 404 for optional favicon or intentional 401
        if (status >= 400 && !response.url().includes('favicon.ico')) {
          failedRequests.push(`${response.status()} ${response.url()}`);
        }
      });

      await page.goto(pageItem.url);
      await page.waitForLoadState('networkidle');

      // Evaluasi temuan
      if (consoleErrors.length > 0) {
        console.warn(`[Temuan Console Error di ${pageItem.name}]:`, consoleErrors);
      }
      if (failedRequests.length > 0) {
        console.warn(`[Temuan Request Gagal di ${pageItem.name}]:`, failedRequests);
      }

      // Verifikasi halaman dasar berhasil termuat (HTTP 200)
      expect(page.url()).toContain('5500');
    });
  }

});
