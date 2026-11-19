// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Homepage, Navigation & Catalog Tests', () => {

  test('TC-HOME-01 (Positive): Halaman utama memuat judul dan elemen kunci dengan benar', async ({ page }) => {
    await page.goto('/index.php');

    // Expected: Title memuat Bloom&Books
    await expect(page).toHaveTitle(/Bloom&Books/);

    // Navbar, wordmark, dan search bar terlihat
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('#headerSearchInput')).toBeVisible();
  });

  test.skip('TC-HOME-02 (Positive): Carousel banner dapat berpindah slide melalui tombol navigasi', async ({ page }) => {
    await page.goto('/index.php');

    // Pastikan container carousel terlihat
    const carouselContainer = page.locator('#heroCarousel');
    await expect(carouselContainer).toBeVisible();

    // Cek slide aktif awal (slide 0)
    const slide1 = page.locator('.carousel-slide[data-slide="0"]');
    await expect(slide1).toHaveClass(/active/);

    // Klik tombol next carousel
    const nextBtn = page.locator('.carousel-control-next, .carousel-next, button[aria-label="Next"]').first();
    await nextBtn.click();

    // Tunggu transisi slide ke slide 1
    await page.waitForTimeout(1200);
    const slide2 = page.locator('.carousel-slide[data-slide="1"]');
    await expect(slide2).toHaveClass(/active/);
  });

  test('TC-HOME-03 (Positive): Bagian Top 5 Leaderboard menampilkan 5 buku terpopuler', async ({ page }) => {
    await page.goto('/index.php');

    const leaderboardHeading = page.locator('text=Top 5 Buku Terpopuler');
    await expect(leaderboardHeading).toBeVisible();

    // Pastikan ada kartu peringkat (#1 sampai #5)
    await expect(page.locator('.top5 .card[data-rank="1"]').first()).toBeVisible();
    await expect(page.locator('.top5 .card[data-rank="2"]').first()).toBeVisible();
    await expect(page.locator('.top5 .card[data-rank="3"]').first()).toBeVisible();
    await expect(page.locator('.top5 .card[data-rank="4"]').first()).toBeVisible();
    await expect(page.locator('.top5 .card[data-rank="5"]').first()).toBeVisible();
  });

  test('TC-HOME-04 (Positive): Mengklik kartu buku di Leaderboard membuka modal detail', async ({ page }) => {
    await page.goto('/index.php');

    // Klik kartu buku terpopuler pertama
    const firstTopCard = page.locator('.top5 .card[data-rank="1"]').first();
    await firstTopCard.click();

    // Expected: Modal detail buku terbuka
    const modal = page.locator('#bookDetailModal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#modalBookJudul')).not.toBeEmpty();

    // Tutup modal
    await page.locator('#bookDetailModal button[aria-label="Tutup"]').click();
    await expect(modal).toBeHidden();
  });

  test('TC-HOME-05 (Positive): Fitur pencarian buku mengembalikan hasil yang sesuai', async ({ page }) => {
    await page.goto('/index.php');

    // Cari buku "Laut" melalui desktop search input
    const searchInput = page.locator('#headerSearchInput');
    await searchInput.fill('Laut');
    await searchInput.press('Enter');

    // Expected: Notice pencarian muncul dan buku "Laut Bercerita" tertampil
    await expect(page.locator('text=Menampilkan hasil pencarian untuk')).toBeVisible();
    await expect(page.locator('#katalog')).toContainText('Laut Bercerita');
  });

  test('TC-HOME-06 (Negative): Pencarian kata kunci acak menampilkan pesan buku tidak ditemukan', async ({ page }) => {
    await page.goto('/index.php?search=xyzqwertynotfound999#katalog');

    // Expected: State buku tidak ditemukan
    await expect(page.locator('text=Buku Tidak Ditemukan')).toBeVisible();
  });

  test('TC-HOME-07 (Positive): Tombol filter kategori menyaring buku secara akurat', async ({ page }) => {
    await page.goto('/index.php');

    // Klik filter "Fiksi & Sastra"
    const fiksiPill = page.locator('#katalog a:has-text("Fiksi & Sastra")').first();
    await fiksiPill.click();

    await expect(page).toHaveURL(/kategori=\d+/);
    // Buku fiksi harus tampil
    await expect(page.locator('#katalog')).toContainText('Laut Bercerita');
  });

  test('TC-HOME-08 (Positive): Modal detail buku menampilkan informasi lengkap dan daftar ulasan', async ({ page }) => {
    await page.goto('/index.php');

    // Buka buku pertama di katalog
    const firstBook = page.locator('#katalog [onclick^="openBookDetail"]').first();
    await firstBook.click();

    const modal = page.locator('#bookDetailModal');
    await expect(modal).toBeVisible();

    // Pastikan detail seperti judul dan harga tampil
    await expect(page.locator('#modalBookJudul')).not.toBeEmpty();
    await expect(page.locator('#modalBookHarga')).toContainText('Rp');

    // Tutup modal
    await page.locator('#bookDetailModal button[aria-label="Tutup"]').click();
    await expect(modal).toBeHidden();
  });

  test('TC-HOME-09 (Positive): Navigasi pagination berpindah ke halaman berikutnya', async ({ page }) => {
    await page.goto('/index.php#katalog');

    // Cek ada pagination link
    const page2Link = page.locator('nav a[href*="page=2"]').first();
    if (await page2Link.count() > 0) {
      await page2Link.click();
      await expect(page).toHaveURL(/page=2/);
    }
  });

});
