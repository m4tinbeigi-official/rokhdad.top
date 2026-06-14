# Rokhdad MVP Definition

## Goal

Launch `rokhdad.top` as a production-ready Persian event discovery and registration platform with external event aggregation, internal event registration, basic payments, SEO, and operational safety.

## In Scope For MVP

- GitHub-first repository workflow.
- Server-only Docker Compose runtime on Ubuntu.
- HTTPS production domain at `rokhdad.top`.
- Laravel API backend.
- Filament admin panel.
- Vue.js public frontend.
- Tailwind CSS styling.
- MariaDB canonical relational data.
- MongoDB raw payload and snapshot storage.
- Redis queues, cache, locks, and rate limiting.
- Python worker service foundation.
- Event source registry for Evand and Eseminar.
- Raw ingestion for Evand and Eseminar.
- Normalization into canonical event records.
- Basic deduplication.
- Source attribution and external source links.
- Admin-managed categories, cities, organizers, people, and events.
- Public event listing, detail, category, city, organizer, and speaker pages.
- Advanced enough search for keyword, category, city, date, online/in-person, and source.
- User registration and login.
- Phone OTP verification through sms.ir.
- Internal event registration.
- Ticket records with QR-code verification.
- ZarinPal payment gateway for paid internal events.
- Notification logs.
- SEO metadata, sitemap, robots, and schema.org Event/BreadcrumbList.
- Backup, rollback, logs, and baseline security hardening.

## Out Of Scope For MVP

- Android app release.
- Advanced Capacitor native integrations.
- Replay/video commerce.
- Webinar provider integrations.
- Advanced AI matching.
- Campaign manager.
- Full settlement/accounting dashboard.
- Complex custom registration forms.
- Public embed widget.
- Multi-provider payment routing beyond MVP gateway fallback design.

## MVP Acceptance Criteria

- `https://rokhdad.top` loads the Vue frontend over HTTPS.
- `https://rokhdad.top/api/health` returns a healthy Laravel API response.
- Admin can log into Filament and manage sources, categories, cities, organizers, people, and events.
- At least Evand and Eseminar ingestion jobs can store raw payload snapshots in MongoDB.
- Normalized external events appear in public listing and detail pages.
- External events clearly link back to their source.
- Users can register, verify phone, and register for an internal event.
- Paid internal event registration can complete through ZarinPal sandbox or production credentials.
- QR tickets can be generated and verified.
- Event pages expose SEO metadata and schema.org Event JSON-LD.
- Backups and rollback commands are documented and tested at least once on staging/production server.

## MVP Test Checklist

- DNS resolves `rokhdad.top` to the Ubuntu server.
- SSL certificate is valid.
- Docker Compose starts all required services.
- Laravel migrations run successfully.
- Health and readiness endpoints pass.
- Admin login works.
- Worker can process a test queue job.
- Evand fixture ingestion works.
- Eseminar fixture ingestion works.
- Public event search returns canonical records.
- Internal event registration creates registration and ticket records.
- Payment callback updates order status idempotently.
- Sitemap and robots endpoints return valid responses.

