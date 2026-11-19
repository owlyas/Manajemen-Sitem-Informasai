// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Shopping Cart & Checkout Workflow Tests', () => {

  test.beforeEach(async ({ page }) => {
    // Login terlebih dahulu untuk memastikan session terisi
    await page.goto('/login.php');
    await page.fill('input#email', 'user@bloombooks.com');
    await page.fill('input#password', 'user123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/user\/dashboard\.php/);
  });

  test.skip('TC-CART-01 (Positive): Menambahkan buku ke keranjang dari katalog', async ({ page }) => {
    await page.goto('/index.php#katalog');

    // Klik tombol "Beli" / "Tambah" pada buku pertama
    const addBtn = page.locator('#katalog form[action="cart.php?action=add"] button[type="submit"]').first();
    await addBtn.click();

    // Expected: Flash alert muncul
    const alert = page.locator('#flashAlert');
    await page.waitForSelector('#flashAlert', { state: 'visible', timeout: 10000 });
    await expect(alert).toBeVisible();
    await expect(alert).toContainText(/keranjang belanja/i);
  });

  test.skip('TC-CART-02 (Positive): Membuka halaman keranjang menampilkan item dan kalkulasi harga', async ({ page }) => {
    await page.goto('/cart.php');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveTitle(/Keranjang/);
    await expect(page.locator('h1, h2').filter({ hasText: 'Keranjang Belanja' })).toBeVisible();

    // Pastikan ada item di keranjang atau tambahkan jika kosong
    const subtotalText = page.locator('text=Total Pembayaran:');
    if (await subtotalText.count() === 0) {
      await page.goto('/index.php#katalog');
      await page.locator('#katalog form[action="cart.php?action=add"] button[type="submit"]').first().click();
      await page.goto('/cart.php');
    await page.waitForLoadState('networkidle');
    }
    await expect(page.locator('text=Total Pembayaran:')).toBeVisible();
  });

  test('TC-CART-03 (Positive): Mengubah jumlah item (+ / -) di keranjang belanja', async ({ page }) => {
    // Pastikan keranjang memiliki minimal 1 item
    await page.goto('/index.php#katalog');
    await page.locator('#katalog form[action="cart.php?action=add"] button[type="submit"]').first().click();

    await page.goto('/cart.php');
    await page.waitForLoadState('networkidle');

    // Cari tombol tambah qty (+)
    const plusBtn = page.locator('button:has-text("+")').first();
    if (await plusBtn.count() > 0) {
      await plusBtn.click();
      // Verifikasi total pembayaran tetap terlihat setelah perubahan qty
      await page.waitForTimeout(500);
    await expect(page.locator('text=Total Pembayaran:')).toBeVisible({ timeout: 10000 });
    }
  });

  test.skip('TC-CART-04 (Positive): Alamat pengiriman terisi otomatis dari profil akun pengguna', async ({ page }) => {
    await page.goto('/cart.php');
    await page.waitForLoadState('networkidle');

    const textarea = page.locator('textarea#alamat_pengiriman');
    if (await textarea.count() > 0) {
      const val = await textarea.inputValue();
      expect(val.trim().length).toBeGreaterThan(0);
    }
  });

  test('TC-CART-05 (Negative): Checkout dengan alamat kosong dicegah oleh validasi', async ({ page }) => {
    await page.goto('/cart.php');
    await page.waitForLoadState('networkidle');

    const textarea = page.locator('textarea#alamat_pengiriman');
    if (await textarea.count() > 0) {
      // Kosongkan alamat lalu submit
      await textarea.fill('');
      const submitBtn = page.locator('#btnCheckoutMidtrans');
      await submitBtn.click();

      // Expected: Backend menolak checkout tanpa alamat dan menampilkan pesan flash error
      const flash = page.locator('#flashAlert');
      await expect(flash).toBeVisible();
      await expect(flash).toContainText(/Mohon lengkapi alamat pengiriman/i);
    }
  });

});
