# Database Systems Final Project

## Setup

1. Start MySQL and Apache on the XAMPP control panel.
2. Copy the project folder into `htdocs`.
3. Copy `config.example.php` to `config.php` and set your database credentials (defaults work for local XAMPP with `root` and no password).
4. Import `database/nufindsdb.sql` into MySQL, then `database/notifications.sql` (student alerts), then `database/stored_procedures.sql` and `database/triggers.sql` if you use those features.
5. Open `http://localhost/NUFINDS/pages/login.html` (adjust the folder name if yours differs).

### Production deployment

Before going live:

- Set `NUFINDS_ENV` to `production` in `config.php`.
- Use a dedicated MySQL user with a strong password (never `root` with an empty password).
- Change the default admin password (`admin123`) immediately after import.
- Do not use default student password `student123` on a live server.
- Serve the site over HTTPS (session cookies use the `Secure` flag automatically when HTTPS is detected).

`config.php` is gitignored — never commit live credentials.

### Student login

Use the main login at `pages/login.html` with **email and password**. College department and student number stay on the registered account and appear in the profile menu after login.

| Field | Example |
|-------|---------|
| Email | `juansantos@students.national-u.edu.ph` |
| Password | `student123` |

New students register at `pages/register.html` with college department, NU email, and password. A student ID is assigned automatically (`YYYY-0001`, e.g. `2026-0001` for the first account in 2026).

Run `database/student_passwords.sql` in phpMyAdmin if you already imported the database before student passwords were added.

### Admin login (same page as students)

When you enter the **admin email**, the form switches to **Admin Password**.

| Field | Value |
|-------|--------|
| Admin email | `nufindshelpdesk@gmail.com` |
| Password | `secret` |

**Admin dashboard:** LOST / FOUND (view and edit reports inline), VERIFY (matched pairs only), HISTORY (verified match archive, editable), NOTIFY (send messages to students). LOST, FOUND, VERIFY, and HISTORY include a search bar (ticket, student ID, email, location, category, description — not department on lost/found). Students see a **bell icon** in the top bar; new messages appear within ~12 seconds (polling) plus a toast popup. Uses MySQL **stored procedures** and **triggers** when `stored_procedures.sql` and `triggers.sql` are imported.

Re-run `database/stored_procedures.sql` in phpMyAdmin after pulling updates to add search procedures and auto student ID generation.

**Students:** LOST / FOUND (submit reports) and TRACK only.

When an admin verifies a match, both reports are copied to `history` and removed from `lost` / `found`.

Run `database/admin_accounts.sql` in phpMyAdmin if you already imported the database earlier.

## Main pages (`pages/`)

| File | Purpose |
|------|---------|
| `login.html` | Login (students and admin) |
| `register.html` | Student registration |
| `student/home.html` | Student dashboard |
| `student/lost.html` | Report lost item |
| `student/found.html` | Report found item |
| `student/trackreport.html` | Track submitted reports |
| `admin/home.html` | Admin dashboard |
| `admin/lost.html` | All lost reports by department |
| `admin/found.html` | All found reports by department |
| `admin/history.html` | Archive of verified matches (edit/delete rows) |
| `admin/notifications.html` | Send custom notifications to one or all students |

All `.html` files under `pages/` run as PHP via `pages/.htaccess`. Form posts and verify still use `database/php/`.

## Project layout

| Path | Purpose |
|------|---------|
| `pages/` | Main HTML pages + nested message/verify views |
| `css/` | All stylesheets |
| `js/` | Client scripts |
| `database/php/entries/` | Auth/data logic for main pages |
| `database/php/auth/` | Login API, logout |
| `database/php/reports/` | Report submission API |
| `database/php/verify/` | Match verification |
| `database/php/lib/` | Shared PHP (`bootstrap.php`, `View.php`, services) |
| `database/stored_procedures.sql` | Admin/verify stored procedures |
| `database/triggers.sql` | Validation and auto-field triggers |

Old URLs such as `database/php/home.php` redirect to the matching page in `pages/`.
