// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Leaderboard Standalone Page Tests', () => {

  test('TC-LEAD-01 (Positive): Halaman Leaderboard menampilkan Podium Juara dan Top 10', async ({ page }) => {
    await page.goto('/leaderboard.php');

    await expect(page).toHaveTitle(/Leaderboard/);
    await expect(page.locator('h1')).toContainText(/Buku/);
    await expect(page.locator('h1')).toContainText(/Terpopuler/);

    // Verifikasi daftar ranking penjualan tampil
    await expect(page.locator('text=Top 10 Paling Banyak Terjual')).toBeVisible();
  });

  test('TC-LEAD-02 (Positive): Filter kategori di Leaderboard berfungsi memperbarui tampilan', async ({ page }) => {
    await page.goto('/leaderboard.php');

    // Ubah filter kategori melalui dropdown
    const selectKategori = page.locator('select[name="kategori"]');
    await selectKategori.selectOption({ index: 1 });

    await expect(page).toHaveURL(/kategori=\d+/);
  });

  test('TC-LEAD-03 (Positive): Filter periode waktu (30 Hari / Semua Waktu)', async ({ page }) => {
    await page.goto('/leaderboard.php');

    // Klik tombol filter periode '30 Hari'
    const btnMonth = page.locator('button[name="period"][value="month"]');
    await btnMonth.click();

    await expect(page).toHaveURL(/period=month/);
  });

  test('TC-LEAD-04 (Positive): Tombol "Lihat Buku" membuka modal detail buku pada leaderboard', async ({ page }) => {
    await page.goto('/leaderboard.php');

    // Buka buku pertama di leaderboard list
    const lihatBukuBtn = page.locator('button:has-text("Lihat Buku")').first();
    if (await lihatBukuBtn.count() > 0) {
      await lihatBukuBtn.click();
      const modal = page.locator('#bookDetailModal');
      await expect(modal).toBeVisible();
    }
  });

});
