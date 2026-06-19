# Rokhdad Planning And Task Board

## 1. Project Understanding

Rokhdad / رخداد is a Persian event aggregation and registration platform for `rokhdad.top`. It must aggregate external events from Evand, Eseminar, and later sources while supporting internal Rokhdad events with registration, tickets, payments, QR codes, comments, ratings, personalization, organizer tooling, SEO, schema.org data, mobile access, and operational monitoring.

The project is API-first, GitHub-first, and server-only at runtime. The local machine is used for planning and editing only. Docker, Laravel, Vue, Python workers, MariaDB, MongoDB, Redis, Nginx, and SSL all run on the Ubuntu server.

## 2. Assumptions

- `rokhdad.top` DNS can be pointed to the target Ubuntu server.
- A GitHub repository will be created or connected before implementation starts.
- SSH access to the Ubuntu server will be available for deployment tasks.
- Laravel is the backend API and Filament admin host.
- Vue.js is the public frontend.
- Capacitor wraps the Vue frontend for Android.
- iPhone support is delivered as an installable PWA.
- MariaDB stores canonical relational data.
- MongoDB stores raw source payloads, enrichment responses, scraping logs, snapshots, and field history payloads.
- Redis is available for queues, cache, locks, and rate limiting.
- Python workers run as separate Docker services.

## 3. Major Architecture Decisions

- Architecture: API-first monorepo with backend, frontend, workers, deploy, and docs folders.
- Runtime: Ubuntu server with Docker Compose only.
- Deployment: GitHub-first pull-based deployment from server.
- Web edge: Nginx container terminates HTTP routing, with SSL automation for `rokhdad.top`.
- Backend: Laravel API plus Filament admin panel.
- Frontend: Vue.js plus Tailwind CSS.
- Mobile: Capacitor Android build later; iPhone as PWA.
- Data: MariaDB canonical database, MongoDB raw/unstructured store, Redis operational store.
- Workers: Python workers for ingestion, scraping, normalization, enrichment, images, and scheduled tasks.
- Event model: source-aware canonical event records with deduplication, field history, admin overrides, and field locks.
- Payments: gateway abstraction for ZarinPal and Zibal.
- Notifications: provider abstraction for sms.ir and Pakett.

## 4. Phase List

- Phase 0: Planning and Architecture
- Phase 1: GitHub Repository and Server-only Deployment Foundation
- Phase 2: Docker Infrastructure on Ubuntu Server
- Phase 3: Laravel API Base
- Phase 4: Database Foundation: MariaDB, MongoDB, Redis
- Phase 5: Authentication, OTP, Roles, Permissions
- Phase 6: Filament Admin Foundation
- Phase 7: Canonical Data Model
- Phase 8: Python Worker Foundation
- Phase 9: Source Management and API Key Rotation
- Phase 10: Event Ingestion and Raw Storage
- Phase 11: Event Normalization and Deduplication
- Phase 12: Event Enrichment and Field History
- Phase 13: Image Download, Processing, and Storage
- Phase 14: Public API for Events, Search, Categories, Cities, People, Organizers
- Phase 15: Vue Web Frontend Foundation
- Phase 16: Public Event Discovery Pages
- Phase 17: Event Detail, Organizer Pages, Speaker/Teacher Pages
- Phase 18: Advanced Search and Filtering
- Phase 19: Internal Events, Registration, Tickets, QR Codes
- Phase 20: Payments with ZarinPal and Zibal
- Phase 21: Comments and Ratings
- Phase 22: User Personalization and Saved Filters
- Phase 23: SEO and Schema.org
- Phase 24: Notifications: SMS and Email
- Phase 25: Android App with Capacitor
- Phase 26: iPhone PWA
- Phase 27: Organizer Advanced Tools
- Phase 28: Embed Widget, Webhooks, Import/Export, Custom Forms
- Phase 29: Campaign Manager and Organizer Analytics
- Phase 30: Settlement and Accounting
- Phase 31: Monitoring, Backups, Rollback, Security Hardening
- Phase 32: Phase 2 Features: Replay, Video Commerce, Webinar Providers, Advanced AI Matching

## 5. Task Status Legend

- PENDING
- IN_PROGRESS
- DONE
- BLOCKED
- NEEDS_REVIEW
- SKIPPED

## 6. Master Task Table

| Task ID | Phase | Task title | Status | Depends on | Deliverable | Test method | Complexity | Priority |
|---|---:|---|---|---|---|---|---|---|
| P0-001 | 0 | Confirm product scope and MVP boundaries | DONE | None | Approved MVP definition | Review checklist with owner | S | MUST |
| P0-002 | 0 | Define monorepo structure and ownership | DONE | P0-001 | Folder/module map | Architecture review | S | MUST |
| P0-003 | 0 | Define environment and secret inventory | DONE | P0-001 | Env matrix | Validate required variables | S | MUST |
| P0-004 | 0 | Define domain and DNS deployment plan | DONE | P0-001 | DNS/SSL plan for `rokhdad.top` | DNS checklist | S | MUST |
| P1-001 | 1 | Create GitHub repository | DONE | P0-002 | Empty private/public repo | Git remote works | XS | MUST |
| P1-002 | 1 | Add branch, commit, and PR rules | DONE | P1-001 | GitHub ruleset | Try protected branch flow | S | SHOULD |
| P1-003 | 1 | Add repository documentation skeleton | DONE | P1-001 | README, docs index | Markdown review | XS | MUST |
| P1-004 | 1 | Define server pull deployment workflow | DONE | P1-001, P0-004 | Deployment runbook | Dry-run commands reviewed | S | MUST |
| P2-001 | 2 | Create Docker Compose service map | DONE | P1-004 | Compose architecture doc | Service dependency review | M | MUST |
| P2-002 | 2 | Define Nginx routing for `rokhdad.top` | DONE | P2-001 | Nginx route plan | Route table review | S | MUST |
| P2-003 | 2 | Define SSL certificate strategy | DONE | P0-004, P2-002 | SSL renewal plan | Renewal checklist | S | MUST |
| P2-004 | 2 | Define persistent volume layout | DONE | P2-001 | Volume map | Backup path review | S | MUST |
| BOOTSTRAP-001 | 2 | Add fast static launch stack | DONE | P2-001 | Bootstrap Nginx landing and health endpoint | YAML and route review | S | MUST |
| P3-001A | 3 | Prepare server-side Laravel scaffold tooling | DONE | P2-001 | Scaffold script and backend Dockerfile | Shell syntax and file review | S | MUST |
| P3-001 | 3 | Scaffold Laravel API app | DONE | P2-001 | Laravel 12 backend skeleton | `php artisan about` on server | M | MUST |
| P3-002 | 3 | Configure Laravel API routing | DONE | P3-001 | Versioned `/api/v1` route | Feature test and HTTP smoke test | S | MUST |
| P3-003 | 3 | Add health and readiness endpoints | DONE | P3-002 | Laravel `/api/health`, `/api/ready` endpoints | Feature test and internal curl smoke test | S | MUST |
| P3-004 | 3 | Add API error response standard | DONE | P3-002 | Standard JSON error payload for `/api/*` | Feature test on server | S | MUST |
| P4-001 | 4 | Configure MariaDB connection | DONE | P3-001, P2-001 | MariaDB env, service, backend image, migrations | Server migration and migrate status test | S | MUST |
| P4-002 | 4 | Configure MongoDB connection | DONE | P3-001, P2-001 | MongoDB env, service, and connection smoke script | Server insert/read test | M | MUST |
| P4-003 | 4 | Configure Redis queues and cache | DONE | P3-001, P2-001 | Redis env, service healthcheck, Laravel cache/queue config | Redis ping and Laravel cache/queue smoke test | S | MUST |
| P4-004 | 4 | Define migration and seed policy | DONE | P4-001 | Migration/seed runbook and guarded Laravel DB script | Server status and rollback-plan checks | S | MUST |
| P5-001 | 5 | Implement user identity model | DONE | P4-001 | User identity migration, model fields, factory, and API contract | Migration/model feature tests and server migration status | M | MUST |
| P5-002 | 5 | Implement email/password auth | DONE | P5-001 | Sanctum token auth with register/login/me/logout APIs | Auth feature tests and server migration/test | M | MUST |
| P5-003 | 5 | Implement phone OTP verification | DONE | P5-001, P24-001 | OTP APIs | OTP happy/error tests | M | MUST |
| P5-004 | 5 | Implement roles and permissions | DONE | P5-001 | RBAC tables, models, and user permission helpers | RBAC migration/model tests and server migration/test | M | MUST |
| P6-001 | 6 | Install Filament admin foundation | DONE | P3-001, P5-004 | Filament admin panel, assets, provider, and admin-role access gate | Admin login/access feature tests and server smoke test | M | MUST |
| P6-002 | 6 | Add admin user management | DONE | P6-001, P5-004 | Filament User resource with role assignment and guarded password edits | User resource route and CRUD feature tests | M | MUST |
| P6-003 | 6 | Add audit log viewer | BLOCKED | P6-001, P31-004 | Audit resource | Admin review | S | SHOULD |
| P7-001 | 7 | Create categories and cities model | DONE | P4-001 | Category/city migrations, models, factories, and API contract | Migration/model tests and server migration/test | M | MUST |
| P7-002 | 7 | Create organizer and person model | DONE | P7-001 | Organizer/person migrations, models, factories, relationships, and API contract | Relation/model tests and server migration/test | M | MUST |
| P7-003 | 7 | Create canonical event model | DONE | P7-001, P7-002 | Event migration, model, factory, relationships, and API contract | Migration/relation tests and server migration/test | L | MUST |
| P7-004 | 7 | Create source attribution model | DONE | P7-003 | Event source attribution migration, model, factory, relationships, and API contract | Relation/model tests and server migration/test | M | MUST |
| P8-001 | 8 | Scaffold Python worker package | DONE | P2-001 | Python package, worker entrypoints, worker Dockerfile, and worker runbook | Unit smoke tests and server container smoke test | M | MUST |
| P8-002 | 8 | Add worker queue consumer contract | DONE | P8-001, P4-003 | Redis queue message contract, consumer loop, and CLI queue flags | Unit queue tests and server Redis job smoke test | M | MUST |
| P8-003 | 8 | Add worker logging standard | BLOCKED | P8-001, P31-001 | Structured logs | Log sample review | S | MUST |
| P9-001 | 9 | Create event source registry | DONE | P7-004 | Event source migration, model, Filament resource, and API contract | Model/relation tests and Filament CRUD tests | M | MUST |
| P9-002 | 9 | Add API key rotation model | DONE | P9-001 | Event source API key migration, encrypted model, issue/rotate/revoke helpers | Key storage and rotation feature tests | M | MUST |
| P9-003 | 9 | Add source health tracking | DONE | P9-001 | Event source health fields, model health helpers, and admin visibility | Simulated success/failure tests | S | SHOULD |
| P10-001 | 10 | Implement Evand raw ingestion | DONE | P8-002, P9-001 | Evand raw collector, payload envelope, fixture, and CLI smoke command | Worker fixture tests and server container fixture smoke test | L | MUST |
| P10-002 | 10 | Implement Eseminar raw ingestion | DONE | P8-002, P9-001 | Eseminar raw collector, payload envelope, fixture, and CLI smoke command | Worker fixture tests and server container fixture smoke test | L | MUST |
| P10-003 | 10 | Store ingestion snapshots in MongoDB | DONE | P10-001, P10-002, P4-002 | MongoDB snapshot store, snapshot CLI, and raw event snapshot document contract | Unit tests and server Mongo readback test | M | MUST |
| P10-004 | 10 | Add ingestion retry and lock rules | DONE | P4-003, P10-001 | Redis job lock, retry counter, max-attempt failure handling, and failure simulation flag | Unit retry tests and server Redis failure simulation | M | MUST |
| P11-001 | 11 | Define normalization schema | DONE | P7-003, P10-003 | Normalized event DTO, nested organizer/location/person refs, schema CLI, and validation rules | DTO serialization and schema CLI tests | M | MUST |
| P11-002 | 11 | Normalize Evand events | DONE | P11-001, P10-001 | Evand raw envelope to normalized event mapper and fixture CLI | Evand fixture mapper tests and server container fixture smoke test | M | MUST |
| P11-003 | 11 | Normalize Eseminar events | DONE | P11-001, P10-002 | Eseminar webinar envelope to normalized event mapper and fixture CLI | Eseminar fixture mapper tests and server container fixture smoke test | M | MUST |
| P11-004 | 11 | Add deduplication scoring | DONE | P11-002, P11-003 | Duplicate scoring rules, candidate finder, fixture, and CLI smoke command | Duplicate fixture tests and server container CLI smoke test | L | MUST |
| P12-001 | 12 | Add field-level source history | DONE | P11-004, P4-002 | Field history document builder, MongoDB upsert store, and CLI dry-run/storage command | Unit history tests and server Mongo readback smoke test | L | MUST |
| P12-002 | 12 | Add admin override and field locks | DONE | P12-001, P6-001 | Event field override and lock tables, Eloquent models, Event helpers, and guarded override workflow | Migration/model feature tests and server migration/test | L | MUST |
| P12-003 | 12 | Add enrichment job contract | DONE | P8-002, P12-001 | Enrichment queue job DTO, fixture, queue conversion, validation, and CLI smoke command | Fixture contract tests and server container CLI smoke test | M | SHOULD |
| P13-001 | 13 | Add image download worker | DONE | P8-002, P10-003 | Image download job contract, downloader, fixture image job, and CLI smoke command | Fixture image tests and server container CLI smoke test | M | MUST |
| P13-002 | 13 | Add image resize variants | DONE | P13-001 | WebP variant generator, Pillow dependency, CLI variant flag, and dimension tests | Image dimension tests and server container variant smoke test | M | MUST |
| P13-003 | 13 | Add image moderation metadata | DONE | P13-001 | Image moderation metadata analyzer, CLI flag, and review flags | Metadata tests and server container metadata smoke test | S | SHOULD |
| P14-001 | 14 | Add public events listing API | DONE | P7-003 | Public `/api/v1/events` endpoint with published filtering, eager-loaded summary relations, and pagination metadata | API feature tests and server HTTP/API tests | M | MUST |
| P14-002 | 14 | Add event detail API | DONE | P14-001 | Public `/api/v1/events/{slug}` endpoint with detail fields, people, source attribution, and published-only visibility | API feature tests and server route/API tests | M | MUST |
| P14-003 | 14 | Add categories and cities API | DONE | P7-001 | Public `/api/v1/categories` and `/api/v1/cities` active lookup endpoints | API feature tests and server route/API tests | S | MUST |
| P14-004 | 14 | Add people and organizers API | DONE | P7-002 | Public `/api/v1/organizers` and `/api/v1/people` profile list/detail APIs | API feature tests and server route/API tests | M | MUST |
| P15-001 | 15 | Scaffold Vue frontend app | DONE | P2-001 | Vue 3/Vite frontend skeleton, Docker image, RTL smoke page, and frontend docs | Local build/audit and server Docker build smoke test | M | MUST |
| P15-002 | 15 | Add Tailwind design foundation | DONE | P15-001 | Tailwind 4/Vite integration, RTL theme tokens, discovery shell, form controls, event cards, and responsive base layout | Local build/audit, browser desktop/mobile visual checks, and server Docker smoke test | S | MUST |
| P15-003 | 15 | Add API client and error handling | DONE | P15-001, P14-001 | Frontend fetch client, public endpoint helpers, normalized API/network/validation errors, and mocked client tests | Node mock API tests, local build/audit, and server Docker smoke test | M | MUST |
| P16-001 | 16 | Build homepage event discovery | DONE | P15-003, P14-001 | API-backed homepage discovery UI with loading, empty, error, retry states, event card mapping, and responsive RTL layout | Homepage mapper tests, local browser desktop/mobile smoke, and server Docker smoke test | M | MUST |
| P16-002 | 16 | Build category and city pages | DONE | P16-001, P14-003 | Listing pages | Browser tests | M | MUST |
| P16-003 | 16 | Build external event source labels | DONE | P16-001, P7-004 | Source badges and outbound links | Link tests | S | MUST |
| P17-001 | 17 | Build event detail page | DONE | P14-002, P15-003 | Event detail UI | Browser tests | M | MUST |
| P17-002 | 17 | Build organizer public page | DONE | P14-004, P15-003 | Organizer page | Browser tests | M | MUST |
| P17-003 | 17 | Build speaker/teacher public page | DONE | P14-004, P15-003 | Person page | Browser tests | M | MUST |
| P18-001 | 18 | Add search API filters | DONE | P14-001 | Search parameters | API tests | L | MUST |
| P18-002 | 18 | Build advanced filter UI | DONE | P18-001, P15-003 | Filter panel | Browser tests | M | MUST |
| P18-003 | 18 | Add saved filter URL state | DONE | P18-002 | Shareable URLs | Browser tests | S | SHOULD |
| P19-001 | 19 | Add internal event creation model | DONE | P7-003, P5-004 | Internal event schema | Feature tests | L | MUST |
| P19-002 | 19 | Add registration flow | DONE | P19-001, P5-002 | Registration APIs/UI | End-to-end test | L | MUST |
| P19-003 | 19 | Add ticket model and QR code | DONE | P19-002 | Ticket records and QR | QR validation test | L | MUST |
| P20-001 | 20 | Add payment gateway abstraction | DONE | P19-002 | Payment service contract | Unit tests | M | MUST |
| P20-002 | 20 | Implement ZarinPal gateway | DONE | P20-001 | Gateway adapter | Sandbox test | L | MUST |
| P20-003 | 20 | Implement Zibal gateway | DONE | P20-001 | Gateway adapter | Sandbox test | L | SHOULD |
| P20-004 | 20 | Add payment verification and webhooks | DONE | P20-002 | Verify callbacks | Integration tests | L | MUST |
| P21-001 | 21 | Add comments model and APIs | DONE | P5-002, P7-003 | Comment endpoints | API tests | M | SHOULD |
| P21-002 | 21 | Add ratings model and APIs | DONE | P5-002, P7-003 | Rating endpoints | API tests | M | SHOULD |
| P21-003 | 21 | Add moderation workflow | DONE | P21-001, P6-001 | Admin moderation | Admin tests | M | SHOULD |
| P22-001 | 22 | Add user preferences model | DONE | P5-001, P7-001 | Preference schema | Model tests | M | SHOULD |
| P22-002 | 22 | Add saved events and favorites | DONE | P22-001, P14-001 | Saved event APIs/UI | E2E test | M | SHOULD |
| P22-003 | 22 | Add personalized homepage API | DONE | P22-001, P14-001 | Ranking endpoint | API tests | L | SHOULD |
| P23-001 | 23 | Add SEO metadata contract | DONE | P14-002, P17-001 | Metadata fields | HTML inspection | M | MUST |
| P23-002 | 23 | Add schema.org Event and BreadcrumbList | DONE | P23-001 | JSON-LD | Rich result validation | M | MUST |
| P23-003 | 23 | Add Person, Organization, ItemList, WebSite schema | DONE | P17-002, P17-003 | JSON-LD | Rich result validation | M | SHOULD |
| P23-004 | 23 | Add sitemap and robots | DONE | P14-001 | Sitemap/robots endpoints | Curl and validator | M | MUST |
| P24-001 | 24 | Add sms.ir provider abstraction | DONE | P5-003 | SMS provider | Sandbox/send test | M | MUST |
| P24-002 | 24 | Add Pakett email provider abstraction | DONE | P5-002 | Email provider | Send test | M | SHOULD |
| P24-003 | 24 | Add notification logs | DONE | P24-001, P24-002 | Notification history | DB tests | S | MUST |
| P25-001 | 25 | Add Capacitor Android shell | DONE | P15-001, P26-001 | Android project shell | Build on server/CI | L | LATER |
| P25-002 | 25 | Add Android app config and icons | DONE | P25-001 | App metadata | Install smoke test | M | LATER |
| P26-001 | 26 | Add PWA manifest and service worker plan | DONE | P15-001 | PWA install support | Lighthouse PWA check | M | SHOULD |
| P26-002 | 26 | Add offline fallback page | DONE | P26-001 | Offline route | Browser offline test | S | COULD |
| P27-001 | 27 | Add organizer dashboard foundation | DONE | P19-001, P5-004 | Organizer dashboard | Role tests | L | SHOULD |
| P27-002 | 27 | Add custom registration forms | DONE | P27-001, P19-002 | Form builder | E2E tests | L | SHOULD |
| P27-003 | 27 | Add promo codes and quantity rules | DONE | P20-001, P27-001 | Ticket rules | Payment tests | L | SHOULD |
| P27-004 | 27 | Add private and recurring events | DONE | P27-001 | Visibility/series rules | API/UI tests | L | COULD |
| P28-001 | 28 | Add embed registration widget | DONE | P19-002 | Embeddable widget | External HTML test | L | COULD |
| P28-002 | 28 | Add webhooks framework | DONE | P19-002, P20-004 | Webhook subscriptions | Delivery tests | L | SHOULD |
| P28-003 | 28 | Add attendee import/export | DONE | P19-002, P27-001 | CSV import/export | File tests | M | SHOULD |
| P29-001 | 29 | Add organizer analytics dashboard | PENDING | P27-001, P19-002 | Analytics UI/API | Data accuracy tests | L | SHOULD |
| P29-002 | 29 | Add campaign manager foundation | PENDING | P24-001, P24-002 | Campaign workflow | Send simulation | L | COULD |
| P30-001 | 30 | Add settlement ledger model | PENDING | P20-004 | Ledger tables | Accounting tests | L | SHOULD |
| P30-002 | 30 | Add organizer settlement dashboard | PENDING | P30-001, P27-001 | Settlement UI | Admin review | L | SHOULD |
| P31-001 | 31 | Add centralized logging plan | PENDING | P2-001 | Logging services | Log query test | M | MUST |
| P31-002 | 31 | Add backup and restore workflow | PENDING | P4-001, P4-002, P2-004 | Backup scripts/runbook | Restore drill | L | MUST |
| P31-003 | 31 | Add rollback deployment workflow | PENDING | P1-004, P2-001 | Rollback runbook | Rollback drill | M | MUST |
| P31-004 | 31 | Add security hardening baseline | PENDING | P5-004, P2-002 | Hardening checklist | Security review | L | MUST |
| P32-001 | 32 | Define replay/video commerce scope | PENDING | MVP complete | Scope document | Product review | M | LATER |
| P32-002 | 32 | Define webinar provider abstraction | PENDING | MVP complete | Provider contract | Architecture review | M | LATER |
| P32-003 | 32 | Define advanced AI matching scope | PENDING | MVP complete | Matching design | Product review | M | LATER |

## 7. Phase-by-Phase Task Breakdown

### Phase 0: Planning and Architecture

Task ID: P0-001
Phase: 0
Title: Confirm product scope and MVP boundaries
Status: DONE
Goal: Confirm what belongs in MVP versus later phases.
Why it matters: Prevents building high-cost features before the event discovery and registration core works.
Dependencies: None
Files/folders likely affected: `docs/TASK_BOARD.md`, `docs/MVP.md`
Database changes: None
API changes: None
Frontend changes: None
Admin panel changes: None
Worker changes: None
Mobile/PWA changes: None
Security considerations: Include privacy and payment requirements in scope.
Test method: Owner reviews and approves MVP checklist.
Acceptance criteria: MVP includes discovery, external event links, internal registration, payment, SEO, admin source management, and basic notifications.
Estimated complexity: S
Notes: Completed in `docs/MVP.md`.

Task ID: P0-002
Phase: 0
Title: Define monorepo structure and ownership
Status: DONE
Goal: Define top-level folders and module boundaries.
Why it matters: Keeps Laravel, Vue, workers, deployment, and docs independently understandable.
Dependencies: P0-001
Files/folders likely affected: `docs/ARCHITECTURE.md`
Database changes: None
API changes: API versioning convention.
Frontend changes: Frontend app folder convention.
Admin panel changes: Admin lives in Laravel/Filament.
Worker changes: Worker package folder convention.
Mobile/PWA changes: Android/PWA folder convention.
Security considerations: Secrets must not be committed.
Test method: Review folder map against stack requirements.
Acceptance criteria: Every major subsystem has a defined folder and owner.
Estimated complexity: S
Notes: Completed in `docs/ARCHITECTURE.md`.

Task ID: P0-003
Phase: 0
Title: Define environment and secret inventory
Status: DONE
Goal: List all required environment variables and secret sources.
Why it matters: Deployment fails without clear secrets for databases, SMS, email, payments, and app keys.
Dependencies: P0-001
Files/folders likely affected: `docs/ENVIRONMENT.md`
Database changes: None
API changes: None
Frontend changes: Public env naming.
Admin panel changes: None
Worker changes: Worker env naming.
Mobile/PWA changes: Public API base URL.
Security considerations: Separate secret and non-secret variables.
Test method: Validate every planned service has documented env keys.
Acceptance criteria: Env matrix covers app, DB, MongoDB, Redis, Nginx, SSL, sms.ir, Pakett, ZarinPal, Zibal.
Estimated complexity: S
Notes: Completed in `docs/ENVIRONMENT.md`; no real secrets were added.

Task ID: P0-004
Phase: 0
Title: Define domain and DNS deployment plan
Status: DONE
Goal: Define how `rokhdad.top` points to the Ubuntu server and obtains SSL.
Why it matters: The project must run on the dedicated domain.
Dependencies: P0-001
Files/folders likely affected: `docs/DEPLOYMENT.md`
Database changes: None
API changes: None
Frontend changes: Canonical URL assumptions.
Admin panel changes: Admin URL assumptions.
Worker changes: None
Mobile/PWA changes: PWA origin assumptions.
Security considerations: HTTPS-only and HSTS plan.
Test method: DNS checklist and SSL issuance checklist.
Acceptance criteria: A records, www redirect, SSL, admin path, API path, and rollback DNS notes are documented.
Estimated complexity: S
Notes: Completed in `docs/DEPLOYMENT.md`; execution still needs server IP and SSH details.

### Phase 1: GitHub Repository and Server-only Deployment Foundation

Task ID: P1-001
Phase: 1
Title: Create GitHub repository
Status: DONE
Goal: Create the canonical GitHub repo for Rokhdad.
Why it matters: Deployment is GitHub-first.
Dependencies: P0-002
Files/folders likely affected: Repository root.
Database changes: None
API changes: None
Frontend changes: None
Admin panel changes: None
Worker changes: None
Mobile/PWA changes: None
Security considerations: Decide private/public and collaborator access.
Test method: Clone repo and push initial planning files.
Acceptance criteria: Repo exists, remote is configured, first commit is pushed.
Estimated complexity: XS
Notes: Completed at `https://github.com/m4tinbeigi-official/rokhdad.top`.

Task ID: P1-002
Phase: 1
Title: Add branch, commit, and PR rules
Status: DONE
Goal: Protect the main branch and define review flow.
Why it matters: Prevents accidental production-breaking commits.
Dependencies: P1-001
Files/folders likely affected: GitHub repository settings.
Database changes: None
API changes: None
Frontend changes: None
Admin panel changes: None
Worker changes: None
Mobile/PWA changes: None
Security considerations: Restrict production secrets to server only.
Test method: Attempt direct push to protected branch.
Acceptance criteria: Main branch requires PR or explicit owner approval.
Estimated complexity: S
Notes: Completed after making the repository public and enabling branch protection on `main`.

Task ID: P1-003
Phase: 1
Title: Add repository documentation skeleton
Status: DONE
Goal: Add README, architecture docs, deployment docs, env docs, and task board.
Why it matters: Keeps implementation controlled and traceable.
Dependencies: P1-001
Files/folders likely affected: `README.md`, `docs/`
Database changes: None
API changes: None
Frontend changes: None
Admin panel changes: None
Worker changes: None
Mobile/PWA changes: None
Security considerations: Documentation must not include real secrets.
Test method: Markdown files render on GitHub.
Acceptance criteria: All planning docs are committed and linked from README.
Estimated complexity: XS
Notes: Completed with `README.md`, `docs/INDEX.md`, and planning docs.

Task ID: P1-004
Phase: 1
Title: Define server pull deployment workflow
Status: DONE
Goal: Document how the Ubuntu server pulls from GitHub and restarts Docker Compose.
Why it matters: The server, not the local machine, runs the app.
Dependencies: P1-001, P0-004
Files/folders likely affected: `docs/DEPLOYMENT.md`
Database changes: None
API changes: None
Frontend changes: Build strategy documented.
Admin panel changes: None
Worker changes: Worker restart documented.
Mobile/PWA changes: PWA cache busting documented.
Security considerations: Deploy key should be read-only where possible.
Test method: Dry-run deployment commands on server.
Acceptance criteria: Deployment commands include clone, pull, env placement, compose up, health check, and rollback.
Estimated complexity: S
Notes: Completed in `docs/DEPLOYMENT.md`. Actual execution still requires server SSH details.

### Phases 2-32 Detail Policy

The Master Task Table above is the source of truth for all remaining phases. Before implementing any task from Phase 2 onward, expand that task into a task-specific implementation note using the required response format. Do not start coding until the owner says: `Implement Task [TASK_ID]. Work only on this task. Do not move to the next task.`

For each listed task, the implementation response must include:

- Task ID
- Goal
- Files created
- Files modified
- Full code for each file
- Commands to run on the server
- Git commands to commit and push
- Deployment commands
- Test checklist
- Acceptance criteria
- Rollback instructions
- Updated task status

## 8. Dependency Map

- P0-001 unlocks all project-level decisions.
- P0-002 unlocks repository setup and app scaffolding.
- P0-004 unlocks deployment, Nginx, SSL, PWA origin, and production URLs.
- P1-001 unlocks GitHub-first workflow.
- P1-004 unlocks Docker/server tasks.
- P2-001 unlocks Laravel, Vue, worker, database, Redis, and Nginx runtime tasks.
- P3-001 unlocks backend API and Filament.
- P4-001, P4-002, and P4-003 unlock data, raw ingestion, and queues.
- P5 tasks unlock user-facing registration, personalization, comments, and organizer tools.
- P7 tasks unlock ingestion, search, event pages, SEO, and registration.
- P8-P12 unlock external event aggregation.
- P14-P18 unlock public discovery MVP.
- P19-P20 unlock paid internal event MVP.
- P23-P24 unlock production SEO and notification readiness.
- P31 must be completed before serious production traffic.
- P32 is intentionally later and blocked until MVP is complete.

## 9. MVP Definition

MVP must include:

- GitHub-first repository and server-only Docker deployment.
- `rokhdad.top` production routing with HTTPS.
- Laravel API base with health checks.
- MariaDB, MongoDB, and Redis configured.
- User registration/login and phone verification.
- Admin panel for users, sources, events, and moderation basics.
- Canonical event model with external source attribution.
- Evand and Eseminar ingestion into raw MongoDB storage.
- Normalization and deduplication into canonical events.
- Public event listing, search, event detail, category, city, organizer, and person APIs.
- Vue public frontend for discovery and event detail.
- External events link back to their source.
- Internal events support registration, tickets, QR codes, and at least ZarinPal payment.
- SEO metadata, schema.org Event, sitemap, and robots.
- sms.ir OTP and notification logs.
- Backup, rollback, and baseline security hardening.

## 10. Phase 1 Definition

Phase 1 is complete when:

- GitHub repo exists.
- Planning docs are committed.
- Branch/commit workflow is defined.
- Server pull deployment workflow is documented.
- Domain assumptions for `rokhdad.top` are documented.

## 11. Phase 2 Definition

Phase 2 is complete when:

- Docker Compose service architecture is defined.
- Nginx route plan for frontend, API, admin, and assets is defined.
- SSL strategy is defined.
- Persistent volume and backup paths are defined.
- No application code is required before the owner explicitly picks implementation tasks.

## 12. How I Should Ask You To Implement Each Task

Use this exact format:

`Implement Task [TASK_ID]. Work only on this task. Do not move to the next task.`

Example:

`Implement Task P0-001. Work only on this task. Do not move to the next task.`

## 13. How I Should Report Completed Tasks Back To You

Use this format:

`Mark Task [TASK_ID] as DONE. Here is what was completed: ...`

Include links, commit hashes, server command output, screenshots, or test results when available.

## 14. Progress Tracking Template

Completed:

- P0-001
- P0-002
- P0-003
- P0-004
- P1-001
- P1-002
- P1-003
- P1-004
- P2-001
- P2-002
- P2-003
- P2-004
- BOOTSTRAP-001
- P3-001A
- P3-001
- P3-002
- P3-003
- P3-004
- P4-001
- P4-002
- P4-003
- P4-004
- P5-001
- P5-002
- P5-003
- P5-004
- P6-001
- P6-002
- P7-001
- P7-002
- P7-003
- P7-004
- P8-001
- P8-002
- P9-001
- P9-002
- P9-003
- P10-001
- P10-002
- P10-003
- P10-004
- P11-001
- P11-002
- P11-003
- P11-004
- P12-001
- P12-002
- P12-003
- P13-001
- P13-002
- P13-003
- P14-001
- P14-002
- P14-003
- P14-004
- P15-001
- P15-002
- P15-003
- P16-001
- P16-002
- P16-003
- P17-001
- P17-002
- P17-003
- P18-001
- P18-002
- P18-003
- P19-001
- P19-002
- P19-003
- P20-001
- P20-002
- P20-003
- P20-004
- P21-001
- P21-002
- P21-003
- P22-001
- P22-002
- P22-003
- P23-001
- P23-002
- P23-003
- P23-004
- P24-001
- P24-002
- P24-003
- P25-001
- P25-002
- P26-001
- P26-002
- P27-001
- P27-002
- P27-003
- P27-004
- P28-001
- P28-002
- P28-003

In Progress:

- None yet

Blocked:

- Local Docker/build work is blocked by low free disk space on this Mac.
- P6-003 is blocked until P31-004 provides the audit log data source.
- P8-003 is blocked until P31-001 provides the structured logging standard.

Next Recommended Task:

- P29-001

## 15. Next Recommended Step

Next implementation can continue with P29-001 for organizer analytics dashboard. P6-003 and P8-003 remain blocked by their upstream dependencies.
