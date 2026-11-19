// @ts-check
import { test, expect } from '@playwright/test';

// Smoke test: homepage loads and title is correct
test('Home page loads with correct title', async ({ page }) => {
  await page.goto('/index.php');
  await expect(page).toHaveTitle(/Bloom&Books/);
});

// Smoke test: successful login
test('Login works with valid credentials', async ({ page }) => {
  await page.goto('/login.php');
  await page.fill('input#email', 'user@bloombooks.com');
  await page.fill('input#password', 'user123');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/user\\/dashboard\\.php/);
});

// Smoke test: logout clears session
test('Logout clears session', async ({ page }) => {
  // login first
  await page.goto('/login.php');
  await page.fill('input#email', 'user@bloombooks.com');
  await page.fill('input#password', 'user123');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/user\\/dashboard\\.php/);
  // logout
  await page.goto('/logout.php');
  await expect(page).toHaveURL(/login\\.php\\?logout=success/);
});
