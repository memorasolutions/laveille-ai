import { execSync } from 'child_process';

/**
 * Playwright global setup: seeds test users with known passwords.
 * Runs seed-e2e-users.php which creates/updates 4 test users.
 */
export default async function globalSetup() {
  try {
    const output = execSync('php tests/e2e/seed-e2e-users.php', {
      cwd: process.cwd(),
      stdio: 'pipe',
      timeout: 30000,
    });
    console.log(output.toString());
  } catch (e: unknown) {
    const message = e instanceof Error ? e.message : String(e);
    console.warn('Global setup failed:', message);
    console.warn('Ensure the database is migrated: php artisan migrate');
  }
}
