# Mendly — craft repair marketplace

[![CI](https://github.com/zijing0907/mendly/actions/workflows/ci.yml/badge.svg)](https://github.com/zijing0907/mendly/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white)
![No frameworks](https://img.shields.io/badge/frameworks-none-a98885)
![License: All rights reserved](https://img.shields.io/badge/license-all%20rights%20reserved-lightgrey)

Mendly connects people who want to **repair rather than replace** their belongings with independent repair
artisans in Malaysia: tailors, cobblers, book binders, plushie "doctors" and leather workers. Customers browse
services, save favourites, submit repair requests with photos, chat with the expert, and review the finished
job. Experts list services and move each request through a tracked workflow that sends automatic chat updates
to the customer.

Built with **vanilla PHP, MySQL, HTML, CSS and JavaScript**. Frameworks and libraries were not allowed in this
course, so routing, auth, validation, the chat widget and the responsive layout are all hand-written.

![Home page](docs/screenshots/home.jpg)

## Features

**Customers**
- Browse and search services with filters for category, location, price and rating, plus sorting and pagination
- Service detail pages with a photo gallery, expert profile and reviews
- Favourites / wishlist, toggled in place with `fetch()`
- Submit repair requests with up to 5 reference photos; cancel while still pending
- Track request status: Pending → Accepted → In Progress → Ready for Collection → Completed
- Leave a review with star rating, tags and photo/video attachments
- Floating chat widget for messaging experts, with image/video attachments

**Repair experts**
- Self-registration with a business profile; first login goes straight to creating a listing
- **My Services** dashboard: create, edit, deactivate and delete listings
- Manage incoming requests; each status change posts an automatic chat message to the customer
- Public reviews page

**Everyone / admin**
- Registration and login for two account types, profile editing with photo upload, password change,
  password reset by token, account deletion
- Contact form, with an admin inbox (`/admin`) for triaging inquiries
- Fully responsive layout using only CSS media queries, with a slide-out drawer on mobile

| Browse & filter | Chat with an expert |
| --- | --- |
| ![Browse services](docs/screenshots/browse-services.jpg) | ![Chat widget](docs/screenshots/chat.jpg) |

| Expert dashboard | Mobile |
| --- | --- |
| ![My Services](docs/screenshots/expert-services.jpg) | <img src="docs/screenshots/mobile-menu.jpg" alt="Mobile menu" width="220"> |

## Tech stack

| Layer | Technology |
| --- | --- |
| Front end | HTML5, CSS3 (custom properties, Grid/Flexbox, media queries), vanilla JavaScript (`fetch`, DOM APIs) |
| Back end | PHP 8 (procedural, `mysqli` prepared statements, sessions) |
| Database | MySQL 8 / MariaDB, 16 tables with foreign keys and cascades |
| Tooling | GitHub Actions CI: PHP lint on 8.1–8.3 plus an end-to-end smoke test against a real MySQL |

## Security notes

- **SQL injection:** every query that takes user input uses `mysqli` prepared statements, including the
  dynamic filter query on Browse Services.
- **Passwords:** stored with `password_hash()` (bcrypt) and checked with `password_verify()`. The session ID is
  regenerated on login.
- **XSS:** server output goes through `htmlspecialchars()`. Chat content rendered by JavaScript is escaped
  before it touches `innerHTML`.
- **File uploads:** the type is detected from the file's bytes, not its name. Files are stored under random
  names with an extension the server picks, and `uploads/.htaccess` refuses to serve scripts.
- **Access control:** every page and endpoint checks the role, and every write is scoped to the owner
  (`WHERE ... AND expert_id = ?`), so changing an ID in a request can't touch someone else's data.
- **Config:** database credentials come from environment variables or a git-ignored `.env`.

## Getting started

### Option A: XAMPP (Windows / macOS)

1. Copy this folder into `htdocs/` (for example `htdocs/mendly`).
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open phpMyAdmin → **Import** → run `database/mendly-schema.sql`, then `database/mendly-data.sql`.
4. Visit <http://localhost/mendly/>.

The default settings match XAMPP (host `localhost`, port `3306`, user `root`, empty password). If yours
differ, copy `.env.example` to `.env` and edit it.

### Option B: PHP built-in server

```bash
mysql -u root -p < database/mendly-schema.sql
mysql -u root -p < database/mendly-data.sql
cp .env.example .env        # then set DB_PASSWORD etc.
php -S localhost:8000
```

Then open <http://localhost:8000>. Requires PHP 8.1+ with the `mysqli` extension.

### Demo accounts

All seed accounts use the password **`ChangeMe!2026`**.

| Role | Login |
| --- | --- |
| Customer | `emily@gmail.com` (has requests, reviews and chats) |
| Expert | `sarah@mendly.com` (3 services) or `alex@mendly.com` |
| Admin | username `admin` at `/admin/login.php` |

### Running the tests

```bash
tests/smoke-test.sh http://localhost:8000
```

The script logs in as each role, loads every page, and fails on any PHP warning or error, unexpected status
code, broken access rule, or broken link (130 checks). Run it against a freshly seeded database. CI runs the same script
on every push.

## Project structure

```
├── index.php        home page (the site's entry point)
├── pages/           every other page a visitor opens: browse, service details, login, profile, ...
├── actions/         form handlers: validate the POST, write to the database, redirect back
├── api/             JSON endpoints called with fetch(): chat, favourites, reviews, request status
├── includes/        shared code, never requested directly (blocked by .htaccess)
│   ├── db.php           connection, .env loading, shared database helpers
│   ├── helpers.php      url()/redirect(), escaping, role checks, safe upload extensions
│   └── header.php, nav.php, footer.php, main.php, chat-widget.php
├── admin/           admin login and inquiry inbox
├── assets/          css/, js/, images/
├── database/        schema and seed data (blocked by .htaccess)
├── tests/           end-to-end smoke test
├── docs/            README screenshots
└── uploads/         user uploads, git-ignored; scripts can't run here
```

Pages print a `<base href>` tag pointing at the project root, and `url()` builds server-side redirects, so
the app works both at `localhost/mendly/` (XAMPP) and at the web root (`php -S`) with no configuration.

## Data model

```mermaid
erDiagram
    customers ||--o{ repair_requests : submits
    customers ||--o{ favorites : saves
    experts ||--o{ services : offers
    categories ||--o{ services : groups
    services ||--o{ service_images : has
    services ||--o{ repair_requests : receives
    repair_requests ||--o{ request_images : has
    repair_requests ||--o| reviews : "reviewed by"
    reviews ||--o{ review_tags : has
    reviews ||--o{ review_media : has
    customers ||--o{ conversations : ""
    experts ||--o{ conversations : ""
    conversations ||--o{ messages : contains
```

Also: `password_resets` (one-time reset tokens), `inquiries` (contact form) and `admins`.

## Post-submission improvements

After the course ended I audited the codebase and fixed the problems that only show up when all the pages run
together. The full list is in the [commit history](https://github.com/zijing0907/mendly/commits/main). Highlights:

- **Fake login:** a leftover debug file logged *every* visitor in as a demo customer. It loaded because of how
  PHP resolves relative `include` paths.
- **Remote code execution:** the repair-request upload kept the user's file extension, so an image with PHP
  appended and named `.php` could be uploaded and run.
- **No admin auth:** the admin inbox, with every user's name and email, was public.
- **Stored XSS:** chat messages were inserted into the page as HTML.
- **Crashes:** four pages queried tables or columns that didn't exist. The chat only worked through the fake
  login, because it read a session key the real login never set.
- **Performance:** the home page went from ~25 MB of images to ~2.7 MB.
- **CRUD:** built the missing expert service-management page and request cancellation.

## Team & credits

Mendly was built as a four-person group assignment for **UECS2194 Web Application Development** at
Universiti Tunku Abdul Rahman (UTAR), 2026. The original team repository is
[wanxi31/WAD_Asgmt](https://github.com/wanxi31/WAD_Asgmt).

## License

Copyright © 2026 Zijing and the Mendly project team. **All rights reserved.**

This code is public for portfolio viewing only. It is not open source: please don't copy it or reuse it,
including as coursework, without permission. See [LICENSE](LICENSE).
