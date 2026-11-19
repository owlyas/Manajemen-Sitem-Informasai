// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Authentication & Authorization Tests', () => {

  test.beforeEach(async ({ context }) => {
    // Bersihkan sesi / cookies sebelum setiap test
    await context.clearCookies();
  });

  test('TC-AUTH-01 (Positive): Login dengan kredensial valid berhasil masuk', async ({ page }) => {
    await page.goto('/login.php');

    await expect(page).toHaveTitle(/Masuk/);

    await page.fill('input#email', 'user@bloombooks.com');
    await page.fill('input#password', 'user123');
    await page.click('button[type="submit"]');

    // Expected: Dialihkan ke user dashboard dan nama user terlihat
    await expect(page).toHaveURL(/user\/dashboard\.php/);
    await expect(page.locator('h1')).toContainText(/Nasya Reader/);
  });

  test('TC-AUTH-02 (Negative): Login dengan password salah menampilkan pesan error', async ({ page }) => {
    await page.goto('/login.php');

    await page.fill('input#email', 'user@bloombooks.com');
    await page.fill('input#password', 'wrongpassword_12345');
    await page.click('button[type="submit"]');

    // Expected: Pesan error muncul di card login
    const errorAlert = page.locator('.text-rose-700, .bg-rose-50');
    await expect(errorAlert).toBeVisible();
    await expect(errorAlert).toContainText(/Alamat email atau kata sandi yang Anda masukkan salah/i);
    await expect(page).toHaveURL(/login\.php/);
  });

  test('TC-AUTH-03 (Negative): Login dengan email tidak terdaftar menampilkan pesan error', async ({ page }) => {
    await page.goto('/login.php');

    await page.fill('input#email', 'unregistered_ghost_email@example.com');
    await page.fill('input#password', 'anypassword123');
    await page.click('button[type="submit"]');

    // Expected: Pesan error penolakan autentikasi muncul
    const errorAlert = page.locator('.text-rose-700, .bg-rose-50');
    await expect(errorAlert).toBeVisible();
    await expect(errorAlert).toContainText(/Alamat email atau kata sandi yang Anda masukkan salah/i);
  });

  test.skip('TC-AUTH-04 (Negative): Registrasi dengan konfirmasi password tidak cocok', async ({ page }) => {
    await page.goto('/register.php');

    await page.fill('input#nama', 'Tester User');
    await page.fill('input#email', `test_${Date.now()}@test.com`);
    await page.fill('input#password', 'passwordValid123');
    await page.fill('input#confirm_password', 'passwordBedaTotal999');
    await page.click('button[type="submit"]');

    // Expected: Validasi menolak dan pesan error tampil
    const errorAlert = page.locator('.text-rose-700, .bg-rose-50');
    await expect(errorAlert).toBeVisible();
    await expect(errorAlert).toContainText(/Konfirmasi kata sandi tidak cocok/i);
  });

  test.skip('TC-AUTH-05 (Negative): Akses dashboard tanpa login dialihkan ke halaman login', async ({ page }) => {
    await page.goto('/user/dashboard.php');

    // Expected: Akses tanpa login dicegah dan di-redirect ke login.php
    await expect(page).toHaveURL(/login\.php/);
    await expect(page.locator('h1')).toContainText(/Masuk ke Akun Anda/i);
  });

  test('TC-AUTH-06 (Positive): Registrasi akun baru berhasil', async ({ page }) => {
    await page.goto('/register.php');

    const uniqueEmail = `qa_playwright_${Date.now()}@example.com`;
    await page.fill('input#nama', 'QA Tester');
    await page.fill('input#email', uniqueEmail);
    await page.fill('input#password', 'secretpass123');
    await page.fill('input#confirm_password', 'secretpass123');
    await page.click('button[type="submit"]');

    // Expected: Redirect ke login dengan pesan sukses registrasi
    await expect(page).toHaveURL(/login\.php\?registered=success/);
    const successAlert = page.locator('.text-emerald-800, .bg-emerald-50');
    await expect(successAlert).toBeVisible();
    await expect(successAlert).toContainText(/Pendaftaran akun berhasil/i);
  });

  test('TC-AUTH-07 (Positive): Logout berhasil membersihkan session', async ({ page }) => {
    // Login terlebih dahulu
    await page.goto('/login.php');
    await page.fill('input#email', 'user@bloombooks.com');
    await page.fill('input#password', 'user123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/user\/dashboard\.php/);

    // Kunjungi endpoint logout
    await page.goto('/logout.php');

    // Expected: Redirect ke login.php?logout=success dengan pesan logout
    await expect(page).toHaveURL(/login\.php\?logout=success/);
    const successAlert = page.locator('.text-emerald-800, .bg-emerald-50');
    await expect(successAlert).toBeVisible();
    await expect(successAlert).toContainText(/berhasil keluar dari akun/i);
  });

});
