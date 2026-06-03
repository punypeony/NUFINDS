# Database Systems Final Project

## Setup

1. Start MySQL and Apache on the XAMPP control panel.
2. Copy the project folder into `htdocs`.
3. Import `database/nufindsdb.sql` into MySQL.
4. Open `http://localhost/NUFINDS/pages/login.html` (adjust the folder name if yours differs).

### Student login

Use the main login at `pages/login.html` with **email and password**. College department and student number stay on the registered account and appear in the profile menu after login.

| Field | Example |
|-------|---------|
| Email | `juansantos@students.national-u.edu.ph` |
| Password | `student123` |

New students can register at `pages/register.html` with college department, student number, NU email, and password.

Run `database/student_passwords.sql` in phpMyAdmin if you already imported the database before student passwords were added.

### Admin login (same page as students)

When you enter the **admin email**, the form switches to **Admin Password**.

| Field | Value |
|-------|--------|
| Admin email | `nufindshelpdesk@gmail.com` |
| Password | `admin123` |

**Admin dashboard:** LOST / FOUND (view and edit reports inline), VERIFY (matched pairs only), HISTORY (verified match archive, editable). Uses MySQL **stored procedures** and **triggers** when `stored_procedures.sql` and `triggers.sql` are imported.

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
