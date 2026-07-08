# ASOG TBI Deployment Checklist

Use this checklist before the first production deployment and for later releases that include database changes. The goal is to deploy without overwriting existing production data.

## 1. Pre-deployment Repo Cleanup

- [ ] Run PHP lint or an equivalent syntax check for changed PHP files.
- [ ] Run `git diff --check`.
- [ ] Merge feature branch into `develop`.
- [ ] Test the `develop` branch locally or on the team testing environment.
- [ ] Merge `develop` into `main` only after review/testing passes.
- [ ] Confirm Hostinger is configured to deploy from the GitHub `main` branch.
- [ ] Confirm `.env` is not committed.
- [ ] Confirm `composer.lock` is committed so production installs exact dependency versions.
- [ ] Remove dev-only public files unless intentionally shipped:
  - [ ] `public/test-3d-mountain.html`
  - [ ] backup files such as `public/assets/js/features/layout/hero.js.backup`
- [ ] Confirm the latest `public/style.css` is built if Tailwind changes were made.
- [ ] Confirm uploads and generated files are not accidentally committed.

## 2. Production `.env` Values

- [ ] Set the production environment:
  ```ini
  CI_ENVIRONMENT = production
  app.baseURL = 'https://asogtbi.com/'
  app.forceGlobalSecureRequests = true
  ```
- [ ] Set the production database credentials from Hostinger.
- [ ] Keep Gmail API enabled and setup disabled:
  ```ini
  gmailApi.enabled = true
  gmailApi.setupEnabled = false
  gmailApi.redirectUri = 'https://asogtbi.com/asog-admin/gmail-api/callback'
  ```
- [ ] Set the production Google OAuth callback URLs:
  ```ini
  googleOAuthRedirectUri = https://asogtbi.com/asog-admin/google/callback
  googleOAuthAccountRedirectUri = https://asogtbi.com/admin/google-account/callback
  googleOAuthGameRedirectUri = https://asogtbi.com/games/guess-the-startup/google/callback
  ```
- [ ] Enable production reCAPTCHA after the domain and keys are ready:
  ```ini
  recaptcha.enabled = true
  recaptcha.allowedHostnames = 'asogtbi.com'
  ```
- [ ] Include `www.asogtbi.com` in `recaptcha.allowedHostnames` only if the site will also be served on `www`.
- [ ] Do not leave `.test`, `localhost`, or `127.0.0.1` as active production URL values.

## 3. Google Cloud Console URL Replacement

- [ ] Use an ASOG-owned Google Cloud project that current maintainers can access.
- [ ] Do not depend on an old OAuth client owned only by a previous developer/intern.
- [ ] In `Google Auth platform > Branding`, confirm:
  - [ ] app name is `ASOG TBI Website`
  - [ ] support email is ASOG-owned
  - [ ] developer contact email is ASOG-owned
  - [ ] authorized domain includes `asogtbi.com`
  - [ ] homepage URL is `https://asogtbi.com/`
  - [ ] privacy/terms URLs are filled only if those public pages exist
- [ ] In `Google Auth platform > Data Access`, confirm only needed scopes are enabled:
  - [ ] login/account linking client: `openid`, `email`, `profile`
  - [ ] Gmail API mailer client: `https://www.googleapis.com/auth/gmail.send`
- [ ] In `Google Auth platform > Audience`, confirm production readiness:
  - [ ] during local testing, add the sender/admin Google accounts as test users
  - [ ] before production launch, publish the app to Production so admin Google login is not limited to test users
  - [ ] before generating the final Gmail API refresh token, publish to Production to avoid External Testing refresh-token expiry
  - [ ] if Google requires verification because of branding/logo or Gmail scope, complete the requested verification steps or document the temporary launch limitation
- [ ] In `Google Auth platform > Clients`, confirm the ASOG-owned login OAuth client has these authorized redirect URIs:
  - [ ] `http://localhost:8080/asog-admin/google/callback`
  - [ ] `http://localhost:8080/admin/google-account/callback`
  - [ ] `http://localhost:8080/games/guess-the-startup/google/callback`
  - [ ] `https://asogtbi.com/asog-admin/google/callback`
  - [ ] `https://asogtbi.com/admin/google-account/callback`
  - [ ] `https://asogtbi.com/games/guess-the-startup/google/callback`
- [ ] Update production `.env` with the ASOG-owned login OAuth values:
  ```ini
  googleOAuthClientId = production-client-id
  googleOAuthClientSecret = production-client-secret
  googleOAuthRedirectUri = https://asogtbi.com/asog-admin/google/callback
  googleOAuthAccountRedirectUri = https://asogtbi.com/admin/google-account/callback
  googleOAuthGameRedirectUri = https://asogtbi.com/games/guess-the-startup/google/callback
  ```
- [ ] In `Google Auth platform > Clients`, confirm the Gmail API OAuth client has this authorized redirect URI:
  - [ ] `https://asogtbi.com/asog-admin/gmail-api/callback`
- [ ] Update production `.env` with the Gmail API OAuth values from the ASOG-owned mailer client:
  ```ini
  gmailApi.clientId = production-gmail-client-id
  gmailApi.clientSecret = production-gmail-client-secret
  gmailApi.redirectUri = https://asogtbi.com/asog-admin/gmail-api/callback
  gmailApi.setupEnabled = false
  ```
- [ ] In Google Cloud reCAPTCHA, confirm the production key includes:
  - [ ] `asogtbi.com`
  - [ ] `www.asogtbi.com` only if the `www` domain is used
- [ ] Update production `.env` with production reCAPTCHA values:
  ```ini
  recaptcha.enabled = true
  recaptcha.allowedHostnames = 'asogtbi.com'
  ```
- [ ] Send one test email and verify all links point to `https://asogtbi.com/...`, not `.test` or localhost.

## 4. GitHub To Hostinger Deployment

- [ ] In Hostinger, connect the GitHub repository for this project.
- [ ] Set the deployment branch to `main`.
- [ ] Set the site document root to the repository `public` directory.
- [ ] Confirm Hostinger deploys only after `develop` has been merged into `main`.
- [ ] Confirm production `.env` is created/edited on Hostinger, not committed to GitHub.
- [ ] If Hostinger supports build/deploy commands, configure:
  ```bash
  composer install --no-dev --optimize-autoloader
  npm install
  npm run build:css
  ```
- [ ] If Hostinger does not run Composer, upload `vendor/` separately from a clean local production install.
- [ ] If Hostinger does not run npm, build `public/style.css` locally before merging to `main`.
- [ ] Manual upload is fallback only for production `.env`, `vendor/`, writable folder structure, or emergency rollback.
- [ ] Do not manually upload app code as the primary deployment method once GitHub deployment is connected.
- [ ] Do not upload local `writable/logs`, `writable/session`, or local test uploads unless intentionally migrating files.

## 5. Database Backup And Safe Migration

- [ ] Export the full production database before running anything.
- [ ] If possible, import that backup into a staging/copy database and test migrations there first.
- [ ] Do not import `asogtbi_schema.sql` over production. It contains destructive `DROP TABLE` statements.
- [ ] Do not re-import old seed or full-schema files over production data.
- [ ] Use CodeIgniter migrations so existing data is preserved.
- [ ] Confirm migration coverage for recent features:
  - [ ] application revalidation columns and `for_revalidation` status
  - [ ] application status remarks
  - [ ] duplicate applicant email constraint removal
  - [ ] FAQs
  - [ ] organization members
  - [ ] new interns seed
  - [ ] landing settings
  - [ ] admin reset token fields
  - [ ] Google identity fields
  - [ ] contact/application archive fields

## 6. Hostinger No-SSH Migration Runner

Use this only if Hostinger does not provide SSH or terminal access.

- [ ] After Hostinger deploys the `main` branch, confirm the temporary route exists:
  - `/deployment/run-migrations`
- [ ] Add these temporary production `.env` values only when ready to migrate:
  ```ini
  deploymentMigrations.enabled = true
  deploymentMigrations.token = 'replace-with-a-long-random-secret'
  ```
- [ ] Visit the migration URL once:
  ```text
  https://asogtbi.com/deployment/run-migrations?token=replace-with-a-long-random-secret
  ```
- [ ] Confirm the page says `Migrations completed successfully.`
- [ ] If it fails, check `writable/logs` and restore from the database backup if needed.
- [ ] Immediately disable the route after migration:
  ```ini
  deploymentMigrations.enabled = false
  deploymentMigrations.token = ''
  ```
- [ ] After launch, remove the temporary migration route and controller in the next commit.
- [ ] Never leave the migration runner enabled after deployment.

## 7. Writable And Upload Folder Checks

- [ ] Confirm the server can write to:
  - [ ] `writable/cache`
  - [ ] `writable/logs`
  - [ ] `writable/session`
  - [ ] `writable/uploads`
  - [ ] `public/uploads/posts`
  - [ ] `public/uploads/incubatees`
  - [ ] `public/uploads/organization`
- [ ] Submit a test upload from the admin panel and confirm the file renders publicly.
- [ ] Submit a test application with CV and Lean Canvas files and confirm download links work.

## 8. Hostinger Performance And Cache Setup

- [ ] Confirm CI4 is using the file cache driver in `app/Config/Cache.php`:
  ```php
  public string $handler = 'file';
  ```
- [ ] Confirm `writable/cache` is writable in production.
- [ ] Keep file-cache usage narrow to avoid Hostinger inode pressure:
  - [ ] cache shared blocks such as settings, FAQ lists, cohorts, and public display lists
  - [ ] do not add one cache file per user, row, token, or request
  - [ ] keep TTLs short for changing data, usually 5 to 10 minutes
- [ ] Add a daily Hostinger cron job to clear old file cache entries.
  - Use the real production path from hPanel.
  - Example shape:
    ```bash
    cd /home/u123456789/domains/asogtbi.com/public_html && /usr/local/bin/php spark cache:clear
    ```
- [ ] In hPanel, enable PHP OPcache:
  - [ ] open `Advanced > PHP Configuration`
  - [ ] open PHP extensions/settings
  - [ ] enable `opcache`
  - [ ] save and retest the site
- [ ] If using Hostinger Cache Manager / LiteSpeed cache, enable it only with safe exclusions.
- [ ] Treat Hostinger/LiteSpeed page cache as public-page acceleration only, not cache-everything mode.
- [ ] In hPanel, open `Advanced > Cache Manager` and find `Exclude URLs`, `Cache exclusions`, or purge/exclusion rules.
- [ ] Exclude these paths from server-level page cache:
  - [ ] `/asog-admin*`
  - [ ] `/admin*`
  - [ ] `/apply/form*`
  - [ ] `/apply/revalidate*`
  - [ ] `/contact*` if POST responses are cached by the host
  - [ ] `/games/guess-the-startup*` if logged-in/session game state is active
  - [ ] `/deployment/run-migrations*`
- [ ] If Hostinger supports query/path pattern exclusions, also exclude:
  - [ ] `*token=*`
- [ ] Reason for exclusions:
  - [ ] admin routes must never cache authenticated pages
  - [ ] apply/contact routes contain CSRF, validation, reCAPTCHA, and form state
  - [ ] revalidation links are private token URLs
  - [ ] game routes can contain player/session state
  - [ ] migration route is temporary and must never be cached
- [ ] Confirm app-level `no-store` headers are active on dynamic/admin routes:
  - [ ] `/asog-admin`
  - [ ] `/admin`
  - [ ] `/apply/form`
  - [ ] `/apply/revalidate/{token}`
  - [ ] `/contact`
  - [ ] `/games/guess-the-startup`
  - [ ] `/deployment/run-migrations`
- [ ] After enabling Hostinger cache, verify public settings changes still appear after clearing app/host cache.
- [ ] Do not enable any cache option that caches authenticated admin/editor HTML.

## 9. Admin Settings Checks

- [ ] Log in at `/asog-admin`.
- [ ] Confirm Google admin login works.
- [ ] Confirm any signed-in admin role can open `/admin/settings`.
- [ ] Confirm a non-superadmin can link, change, and unlink their own Google account from Settings.
- [ ] Open Settings and confirm:
  - [ ] public application window status is correct
  - [ ] start/end dates are correct
  - [ ] duplicate email setting is correct
  - [ ] landing loader setting is correct
  - [ ] interns visibility is correct
  - [ ] Guess the Startup visibility and availability are correct
  - [ ] Gmail API status is ready
  - [ ] reCAPTCHA status is enabled

## 10. Public-site Smoke Tests

- [ ] Home page loads and loader behavior is acceptable.
- [ ] Hero slider starts correctly after the loader.
- [ ] About page loads.
- [ ] Programs and services pages load.
- [ ] Facilities page loads.
- [ ] Incubatees page and cohort pages load.
- [ ] Apply overview shows the correct open/not-yet-open/closed notice.
- [ ] `/apply/form` opens only when applications are open.
- [ ] Organization page loads and interns/mentors render correctly.
- [ ] News listing and article pages load.
- [ ] Contact page loads.
- [ ] Sitemap loads at `/sitemap.xml`.

## 11. Form And Email Smoke Tests

- [ ] Contact form saves the message and sends the admin notification.
- [ ] New application submission saves, uploads files, and sends applicant confirmation.
- [ ] Application status update email sends with the correct production links.
- [ ] For Revalidation email includes the private update link on `https://asogtbi.com/...`.
- [ ] Revalidation link opens and updates the existing application.
- [ ] Password reset email sends and reset link points to `https://asogtbi.com/asog-admin/reset-password/...`.
- [ ] Email logo images render from the public production domain.
- [ ] reCAPTCHA passes on production for contact, application, and revalidation forms.

## 12. Rollback Notes

- [ ] Keep the pre-deployment database export until the release is verified.
- [ ] Keep the previous working Git commit hash from `main`.
- [ ] If the site breaks before migrations complete, revert `main` to the previous working commit and trigger Hostinger redeploy.
- [ ] If migrations complete and data is wrong, restore the database backup before retrying.
- [ ] If emails fail, disable only the affected email-dependent feature if necessary; do not roll back data changes unless the core workflow is broken.

## 13. Final Lockdown

- [ ] `deploymentMigrations.enabled = false`
- [ ] `deploymentMigrations.token = ''`
- [ ] `gmailApi.setupEnabled = false`
- [ ] `CI_ENVIRONMENT = production`
- [ ] No active `.test`, `localhost`, or `127.0.0.1` links in production `.env`
- [ ] Temporary migration route/controller scheduled for removal after deployment
