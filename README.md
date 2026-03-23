# bookmarks.philipnewborough.co.uk

A personal bookmark manager built with [CodeIgniter 4](https://codeigniter.com/). Visitors can browse and search public bookmarks; authenticated admins can create, edit, and delete bookmarks and manage tags. The application is designed as one node in a personal homelab ecosystem and integrates with several external microservices.

---

## Features

- **Bookmark management** — create, edit, and soft-delete bookmarks with titles, URLs, notes (Markdown), tags, and screenshots/thumbnails.
- **Infinite scroll** — the homepage loads bookmarks progressively via an IntersectionObserver-driven AJAX endpoint.
- **Full-text search** — filter bookmarks by keyword directly from the homepage.
- **Public / private bookmarks** — private bookmarks are hidden from unauthenticated visitors.
- **Tag system** — tags are stored both denormalized (on the bookmark) and normalized (in a dedicated `tags` table) for fast display and querying.
- **Screenshot capture** — integrates with the [ScreenshotOne](https://screenshotone.com/) API to automatically capture and store page screenshots. URLs are HMAC-signed server-side so the API secret key is never exposed to the browser.
- **YouTube thumbnails** — bookmark URLs that point to YouTube videos automatically use the HD video thumbnail as the bookmark image.
- **Favicon fetching** — favicons are fetched and stored automatically when a bookmark is created.
- **Markdown notes** — notes are written in Markdown, rendered to HTML via an external Markdown microservice, and both forms are persisted in the database.
- **Admin dashboard** — server-side DataTables view of all bookmarks with bulk soft-delete support.
- **REST API** — JSON API for creating/updating bookmarks, fetching tags, rendering Markdown, and capturing screenshots.
- **CLI import** — import bookmarks from a JSON export file via `php spark bookmarks:import`.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | CodeIgniter 4 (`^4.7`) |
| PHP | `^8.2` |
| CSS framework | Bootstrap 5 |
| Icons | Bootstrap Icons |
| Admin tables | DataTables (server-side via `hermawan/codeigniter4-datatables`) |
| JavaScript style | Airbnb Style Guide, linted with ESLint |
| PHP style | PSR-12 |
| Testing | PHPUnit 10 |

---

## Application Structure

### Public routes

| Route | Description |
|---|---|
| `GET /` | Paginated bookmark list with search |
| `GET /bookmarks/load` | AJAX endpoint for infinite scroll |
| `GET /bookmark/{uuid}` | Single bookmark detail page |

### Admin routes

All admin routes require an active session with `is_admin` set (enforced by `AdminFilter`).

| Route | Description |
|---|---|
| `GET /admin` | Dashboard with stats |
| `GET /admin/datatable` | Server-side DataTables JSON |
| `POST /admin/delete` | Soft-delete selected bookmarks |
| `GET /admin/bookmark/create` | Create bookmark form |
| `GET /admin/bookmark/{uuid}/edit` | Edit bookmark form |

### API routes

API routes require an `apikey` header (enforced by `ApiFilter`).

| Route | Description |
|---|---|
| `GET /api/test/ping` | Health check |
| `GET /api/tags` | All tags (alphabetical) |
| `POST /api/bookmarks` | Create a bookmark |
| `PUT /api/bookmarks/{uuid}` | Update a bookmark |
| `POST /api/markdown/preview` | Render Markdown to HTML |
| `GET /api/screenshot/preview` | Get a signed screenshot preview URL |
| `POST /api/screenshot/capture` | Capture and save a screenshot |

---

## Database

### `bookmarks`

| Column | Type | Notes |
|---|---|---|
| `id` | INT | Primary key |
| `uuid` | VARCHAR(255) | UUID v4 |
| `title` | VARCHAR(255) | Plain text title |
| `title_html` | VARCHAR(255) | HTML-escaped title |
| `url` | VARCHAR(255) | Unique |
| `favicon` | TEXT | Favicon data/URL |
| `notes` | TEXT | Raw Markdown |
| `notes_html` | TEXT | Rendered HTML |
| `tags` | VARCHAR(255) | Denormalized comma-separated tags |
| `image` | VARCHAR(255) | Screenshot/thumbnail filename |
| `private` | TINYINT(1) | `1` = hidden from guests |
| `dashboard` | TINYINT(1) | Flag for startpage display |
| `hitcounter` | INT | View count |
| `created_at` / `updated_at` / `deleted_at` | DATETIME | Soft deletes enabled |

### `tags`

| Column | Type | Notes |
|---|---|---|
| `id` | INT | Primary key |
| `bookmark_id` | INT | Foreign key to `bookmarks` |
| `tag` | VARCHAR(100) | Display name |
| `slug` | VARCHAR(100) | Unique alpha-dash slug |
| `created_at` / `updated_at` | DATETIME | |

---

## Authentication

Authentication is **externally delegated** — there is no local user table. The `AuthFilter` reads `user_uuid` and `token` cookies, makes a cURL call to a configured remote auth server, and hydrates the PHP session with the user profile (including the `is_admin` flag). `OptionalAuthFilter` silently hydrates the session when cookies are present without redirecting unauthenticated visitors.

---

## Configuration

All sensitive and environment-specific values are set via the `.env` file (not committed to version control). A template is provided in the `env` file at the project root.

Key configuration files under `app/Config/`:

| File | Purpose |
|---|---|
| `Urls.php` | External service URLs (auth server, startpage, app menu, notifications, markdown, metrics, etc.) |
| `ApiKeys.php` | `masterKey` — primary API authentication token for inter-service calls |
| `ScreenshotOne.php` | ScreenshotOne `apikey` and `secretkey` |
| `User.php` | Server-side username and home directory path |
| `App.php` | Standard CI4 config; adds a `siteName` property used in `<title>` tags |

---

## Homelab Integrations

The application integrates with several external services, all configured via `Urls.php`:

- **Auth server** — SSO via cookies; all protected routes delegate authentication here.
- **Markdown microservice** — renders Markdown notes to HTML.
- **ScreenshotOne** — captures page screenshots for bookmarks.
- **Notifications API** — powers the notification bell in the navbar.
- **App menu API** — populates the app menu in the navbar.
- **Startpage** — linked from the admin navbar.

---

## Getting Started

### Requirements

- PHP `^8.2`
- Composer
- A database supported by CodeIgniter 4 (MySQL / MariaDB / SQLite)

### Installation

```bash
git clone <repo-url>
cd codeigniter
composer install
npm install
cp env .env
# Edit .env with your database credentials, base URL, and service URLs
php spark migrate
```

### Importing bookmarks

```bash
# Import from the first .json file found in imports/
php spark bookmarks:import

# Import from a specific file, wiping existing data first
php spark bookmarks:import imports/my-export.json --truncate
```

### Running tests

```bash
composer test
```

---

## License

See [LICENSE](LICENSE) for details.
