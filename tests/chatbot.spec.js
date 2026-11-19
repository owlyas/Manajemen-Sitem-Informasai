// @ts-check
import { test, expect } from '@playwright/test';

test.describe('BloomBot AI Assistant Widget Tests', () => {

  test('TC-BOT-01 (Positive): Tombol floating BloomBot terlihat dan dapat membuka jendela chat', async ({ page }) => {
    await page.goto('/index.php');

    const toggleBtn = page.locator('#chatbotToggleBtn');
    await expect(toggleBtn).toBeVisible();

    // Buka jendela chatbot
    await toggleBtn.click();

    const chatWindow = page.locator('#chatbotWindow');
    await expect(chatWindow).toBeVisible();
    await expect(chatWindow).toContainText(/BloomBot/i);
  });

  test('TC-BOT-02 (Positive): Mengirim pesan teks ke chatbot menghasilkan balasan otomatis', async ({ page }) => {
    await page.goto('/index.php');

    // Buka jendela chatbot
    await page.locator('#chatbotToggleBtn').click();
    await page.waitForSelector('#chatbotWindow', { state: 'visible', timeout: 5000 });
    await page.waitForTimeout(500);

    const inputMsg = page.locator('#chatbotInput');
    await inputMsg.fill('Rekomendasi novel fiksi');
    await page.locator('#chatbotSendBtn').click();

    // Pesan pengguna muncul di riwayat chat
    await expect(page.locator('#chatbotMessages')).toContainText('Rekomendasi novel fiksi');

    // Tunggu balasan bot muncul
    await page.waitForTimeout(4000);
    const botReplies = page.locator('#chatbotMessages .bg-white\/95, #chatbotMessages .border-purple-100\/80');
    await expect(botReplies.first()).toBeVisible();
  });

  test('TC-BOT-03 (Positive): Menutup jendela chat BloomBot mengembalikannya ke floating button', async ({ page }) => {
    await page.goto('/index.php');

    // Buka
    await page.locator('#chatbotToggleBtn').click();
    await page.waitForSelector('#chatbotWindow', { state: 'visible', timeout: 5000 });
    await expect(page.locator('#chatbotWindow')).toBeVisible();

    // Tutup
    await page.locator('#chatbotCloseBtn').click();
    await expect(page.locator('#chatbotWindow')).toHaveClass(/hidden/);
  });

});
