import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.APP_URL ?? 'http://127.0.0.1:8011';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    reporter: 'html',
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    webServer: process.env.APP_URL
        ? undefined
        : {
              command:
                  'php artisan serve --host=127.0.0.1 --port=8011 --no-reload',
              url: baseURL,
              reuseExistingServer: !process.env.CI,
          },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'mobile-safari',
            use: { ...devices['iPhone 15'] },
        },
    ],
});
