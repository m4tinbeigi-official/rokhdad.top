# Google Search Console & Analytics (GA4) Integration

This document describes how the integration with Google Analytics 4 (GA4) and Google Search Console is implemented, how it stores data in the database, how to run and manage it in the admin panel, and how to get Google Cloud credentials.

---

## 1. Setup & Credentials Guide

To fetch statistics from Google, you must register a project in the Google Cloud Console and generate OAuth credentials.

### Step 1: Create a Google Cloud Project & Enable APIs
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project or select an existing one.
3. In the sidebar, navigate to **APIs & Services > Library**.
4. Search for and **Enable** the following APIs:
   - **Google Analytics Data API** (required for GA4 report queries)
   - **Google Search Console API** (required for SEO search performance queries)

### Step 2: Configure the OAuth Consent Screen
1. Go to **APIs & Services > OAuth consent screen**.
2. Select **External** (or Internal if you are within a Google Workspace organization) and click **Create**.
3. Fill in the App Name, User support email, and Developer contact information.
4. In the **Scopes** page, add the following scopes:
   - `https://www.googleapis.com/auth/analytics.readonly` (Google Analytics read access)
   - `https://www.googleapis.com/auth/webmasters.readonly` (Search Console read access)
5. Under **Test users**, add the email address of the Google Account that owns the Google Analytics property and Search Console site.

### Step 3: Create OAuth Credentials
1. Go to **APIs & Services > Credentials**.
2. Click **Create Credentials** and select **OAuth client ID**.
3. Choose **Web application** as the application type.
4. Set the name (e.g., `Rokhdad Admin`).
5. Under **Authorized redirect URIs**, add your admin panel OAuth callback URL:
   - Local testing: `http://localhost:8000/admin/google/callback`
   - Production: `https://rokhdad.top/admin/google/callback`
6. Click **Create** to obtain your **Client ID** and **Client Secret**.

---

## 2. Configuration in the Admin Panel

You do not need to hardcode keys in `.env` unless you wish to specify defaults. The integration is fully manageable via the Admin panel.

### Entering Credentials
1. Go to the Filament Admin panel.
2. Under **تنظیمات سیستم** (System Settings), select **تنظیمات گوگل** (Google Settings).
3. Fill in the fields:
   - **Client ID**: From Google Cloud Console.
   - **Client Secret**: From Google Cloud Console.
   - **Redirect URI**: Read-only, auto-filled based on the current domain.
   - **GA4 Property ID**: Find this in Google Analytics under Admin > Property Settings (a numeric ID like `412345678`).
   - **Search Console Site URL**: The registered site URL exactly as it appears in Search Console (e.g., `https://rokhdad.top` or `sc-domain:rokhdad.top`).
4. Click **ذخیره تنظیمات** (Save Settings).

### Connecting the Google Account
1. Once credentials are saved, click **اتصال به حساب گوگل** (Connect Google Account).
2. You will be redirected to Google's authentication page.
3. Select your Google account and grant permissions for Analytics and Search Console.
4. Upon successful login, you will be redirected back to the Google Settings page showing the connected state: `متصل به حساب گوگل` (Connected to Google Account).

---

## 3. Storage & Schema Reference

Statistics are fetched daily and persisted in the local database.

### `google_settings`
Stores OAuth client configuration and tokens:
- `client_id`, `client_secret`, `redirect_uri`
- `access_token`, `refresh_token` (used to refresh access in the background)
- `token_type`, `expires_in`, `created_at_timestamp`
- `analytics_property_id`, `search_console_site_url`

### `google_analytics_metrics`
Stores daily traffic statistics:
- `date` (date, primary key/unique)
- `pageviews` (integer)
- `sessions` (integer)
- `active_users` (integer)
- `bounce_rate` (float, percentage)
- `avg_session_duration` (float, seconds)

### `google_search_console_metrics`
Stores daily SEO search performance:
- `date` (date, primary key/unique)
- `clicks` (integer)
- `impressions` (integer)
- `ctr` (float, percentage)
- `position` (float, rank number)

---

## 4. Background Sync & Console Commands

Dailly statistics are fetched in the background automatically.

### Manual Command
You can run the Artisan command manually to backfill or sync statistics:
```bash
# Sync statistics for the last 30 days
php artisan google:fetch-metrics --days=30

# Sync yesterday's stats only
php artisan google:fetch-metrics --days=1
```

### Scheduled Execution (Cron)
The command is registered to run automatically every day at `02:00` UTC via the scheduler in [console.php](file:///Users/ricksabchez/Desktop/Rokhdad.ToP/backend/routes/console.php):
```php
Schedule::command('google:fetch-metrics --days=1')->dailyAt('02:00');
```
Ensure the server has a cron job executing `php artisan schedule:run` every minute:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Dashboard Charts & Visuals

The statistics are presented visually on a dedicated page:
1. Navigate to **گزارشات سیستم** (System Reports) > **داشبورد گوگل** (Google Dashboard).
2. This displays:
   - **Sum overview cards** (Total pageviews, clicks, impressions, CTR, average position).
   - **Google Analytics Chart**: Interactive timeline showing pageviews, sessions, and active users.
   - **Google Search Console Chart**: Interactive timeline showing clicks and impressions.
   - **Real-time Sync Button**: A button to instantly trigger a sync without waiting for the nightly cron job.

> [!TIP]
> **Demo/Fallback Data:** If no Google account has been connected yet, the dashboard automatically generates simulated data for the last 30 days to populate charts and cards. This allows for immediate design testing and navigation.
