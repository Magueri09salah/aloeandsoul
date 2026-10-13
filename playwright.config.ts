import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests run against a separate database (aloeandsoul_e2e) that is
 * rebuilt from scratch with E2eSeeder before every run. Build the assets
 * first: `npm run build`.
 */
const PORT = Number(process.env.E2E_PORT ?? 8001);
const env = {
    APP_ENV: 'local',
    APP_DEBUG: 'false',
    APP_URL: `http://127.0.0.1:${PORT}`,
    DB_DATABASE: process.env.E2E_DB_DATABASE ?? 'aloeandsoul_e2e',
    CACHE_STORE: 'array', // rate limits are covered by the PHP suite
    SESSION_DRIVER: 'database',
    MAIL_MAILER: 'array',
};

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 90_000,
    expect: { timeout: 15_000 },
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL: env.APP_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        actionTimeout: 20_000,
        navigationTimeout: 60_000,
    },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile', use: { ...devices['Pixel 7'] }, grep: /@mobile/ },
    ],
    webServer: {
        command: `php artisan migrate:fresh --seed --seeder=E2eSeeder --force --no-interaction && php artisan serve --host=127.0.0.1 --port=${PORT} --no-reload`,
        url: `${env.APP_URL}/up`,
        timeout: 180_000,
        reuseExistingServer: false,
        env,
    },
});
