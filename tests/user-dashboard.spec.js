// @ts-check
import { test, expect } from '@playwright/test';

test.describe('User Dashboard & Account Management Tests', () => {

  test.beforeEach(async ({ page }) => {
    // Login terlebih dahulu sebagai pembeli
    await page.goto('/login.php');
    await page.fill('input#email', 'user@bloombooks.com');
    await page.fill('input#password', 'user123');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/user\/dashboard\.php/);
  });

  test('TC-USER-01 (Positive): Dashboard pengguna menampilkan kartu selamat datang dan statistik', async ({ page }) => {
    await page.goto('/user/dashboard.php');

    // Expected: Nama user tampil di kartu selamat datang
    await expect(page.locator('h1')).toContainText(/Nasya Reader/);
    // Kartu status buku tersimpan atau status pesanan terlihat
    await expect(page.locator('text=Riwayat Pesanan Buku')).toBeVisible();
  });

  test('TC-USER-02 (Positive): Berpindah antar tab (Ringkasan, Profil, Alamat, Keamanan)', async ({ page }) => {
    await page.goto('/user/dashboard.php');

    // Pindah ke tab Profil
    await page.click('button[onclick*="profil"]');
    await expect(page.locator('#tabContent-profil')).toBeVisible();
    await expect(page.locator('#tabContent-ringkasan')).toBeHidden();

    // Pindah ke tab Alamat
    await page.click('button[onclick*="alamat"]');
    await expect(page.locator('#tabContent-alamat')).toBeVisible();

    // Pindah ke tab Keamanan
    await page.click('button[onclick*="keamanan"]');
    await expect(page.locator('#tabContent-keamanan')).toBeVisible();
  });

  test('TC-USER-03 (Positive): Memperbarui data profil (Nama & Nomor Telepon)', async ({ page }) => {
    await page.goto('/user/dashboard.php?tab=profil');

    await page.fill('input#nama', 'Nasya Reader Verified');
    await page.fill('input#telepon', '081298765432');
    await page.click('#tabContent-profil button[type="submit"]');

    // Expected: pesan sukses muncul
    const alert = page.locator('text=Profil Anda berhasil diperbarui');
    await expect(alert).toBeVisible();

    // Kembalikan ke nama awal
    await page.fill('input#nama', 'Nasya Reader');
    await page.click('#tabContent-profil button[type="submit"]');
  });

  test('TC-USER-04 (Negative): Memperbarui profil dengan nama kosong dicegah', async ({ page }) => {
    await page.goto('/user/dashboard.php?tab=profil');

    const namaInput = page.locator('input#nama');
    await namaInput.fill('');
    await page.click('#tabContent-profil button[type="submit"]');

    const isInvalid = await namaInput.evaluate((el) => !el.checkValidity());
    expect(isInvalid).toBe(true);
  });

  test('TC-USER-05 (Positive): Menyimpan alamat pengiriman baru', async ({ page }) => {
    await page.goto('/user/dashboard.php?tab=alamat');

    await page.fill('textarea#alamat', 'Jl. Merdeka No. 10, RT 01 / RW 02, Gambir');
    await page.fill('input#kota', 'Jakarta Pusat');
    await page.fill('input#kode_pos', '10110');
    await page.click('#tabContent-alamat button[type="submit"]');

    // Expected: notifikasi sukses disimpan
    const alert = page.locator('text=Alamat pengiriman berhasil disimpan');
    await expect(alert).toBeVisible();
  });

  test('TC-USER-06 (Negative): Ganti kata sandi dengan kata sandi saat ini yang salah', async ({ page }) => {
    await page.goto('/user/dashboard.php?tab=keamanan');

    await page.fill('input#current_password', 'kataSandiAsalSalah123');
    await page.fill('input#new_password', 'passwordBaruRahasia99');
    await page.fill('input#confirm_new_password', 'passwordBaruRahasia99');
    await page.click('#tabContent-keamanan button[type="submit"]');

    // Expected: Pesan error kata sandi saat ini salah
    const errorAlert = page.locator('text=Kata sandi saat ini salah');
    await expect(errorAlert).toBeVisible();
  });

});
