# LabGuard Migration Progress

Active branch: `cleanup/nextjs-migration`. Work is committed in small steps; no pushes, force-pushes, or history rewrites.

---

## Phase 1 — Security (done)

### What I did
- Scanned tracked files and full git history for secrets, credentials, keys, logs, dumps, uploads, and IDE/OS junk.
- Added `.gitignore` covering: `.env` files, `node_modules/`, `vendor/`, `.next/`, `out/`, `build/`, `dist/`, `*.log`, `*.sql`, `backend/uploads/`, XAMPP/htdocs artifacts, and IDE/OS junk.
- Added `.env.example` with placeholder-only values for MariaDB, SMTP, `DATABASE_URL`, and `AUTH_SECRET`.
- Added `env_loader.php` — a tiny dependency-free parser that loads repo-root `.env` into `getenv()` for the legacy PHP app.
- Replaced hardcoded credentials with environment variables in:
  - `backend/db.php` (DB host/port/name/user/password; now also hides connection errors from the browser)
  - `backend/db_check.php` (its own separate PDO connections)
  - `mail_config.php` (all `MAIL_*` constants)
  - `backend/send_attendance_summary.php` (second hardcoded Gmail app password → `MAIL_*` constants)
- Untracked sensitive/bulk files with `git rm --cached` (all still present on disk): `debug.log`, `backend/debug.log`, all four `*.sql` dumps, all `backend/uploads/*` photos, `node_modules/`, `vendor/`.

### What I skipped
- Did **not** rewrite git history or delete any files from disk, per instructions.
- Did **not** change the database in any way.
- Did **not** rotate any credentials myself (see below — you must do this).

### ROTATE THESE (committed in git history — treat as compromised)

These values were committed in the past. `.gitignore` cannot remove them from history. Since history is intentionally untouched, **change every one of them at the provider**:

1. **Gmail account `jhonrey.loreno77@gmail.com`** — disable/rotate all app passwords for this account.
   - App password `vfuqkzcjjvwvrpzl` (also committed spaced as `vfuq kzcj jvwv rpzl`) in `mail_config.php`. Found in commits `758f978`, `bfe196b`, `28283f8`.
   - App password `rxlk ojvq mxxa ybgf` in `backend/send_attendance_summary.php`. Found in commits `bfe196b`, `28283f8`.
2. **MariaDB `root` account with empty password** — the DB credentials committed were `root` / `""` in `backend/db.php` (and `db_check.php`) across commits `a9c0de9`, `c6e7f8f`, `1eb28d8`, `28283f8`. If this database is reachable from anything other than your own machine, set a password for `root` or create a dedicated app user.
3. **Password hashes + personal data in SQL dumps** — `labguard.sql`, `labguard (2).sql`, `labguard (4).sql`, and `backend/school_attendance.sql` contain bcrypt password hashes, student/professor emails, names, RFID tags, and photo filenames. They are now untracked but remain in history. If these accounts reuse passwords elsewhere, force resets. If the repo was ever public, consider the user data exposed.

### NEEDS MY DECISION
- The SQL dumps and `backend/uploads/` are untracked but still on disk. Do you want them kept out of git permanently, or replaced with sanitised/sample equivalents? (I left them untouched.)
- The SMTP sender identity and whether to keep using the same Gmail account is yours to decide; `.env.example` uses placeholders.

### How the legacy app reads config now
Copy `.env.example` → `.env`, fill in values. PHP reads it through `env_loader.php`; no PHP dotenv package was added.

---

## Phase 2 — Cleanup (done)

### Codebase map (inventory before changes)

**Stack:** server-rendered PHP 8.x + PDO against MariaDB (`labguard`); Tailwind CSS v4 built via Node; PHPMailer v6 (Composer) for SMTP; Apache/XAMPP locally. Frontend is vanilla JS using `fetch()` against PHP endpoints that return JSON.

**Entry points**
- `index.php` — the RFID kiosk. Dual purpose: PHP block handles `POST rfid_tag` → JSON (lines 22–518), HTML page below (520–627). Includes `sections/nav.php` (header + a second copy of the login handler + modal). No auth guard (it is a public terminal).
- `backend/login.php` — role-based login (POST processor + modal form). Session keys: `user_id`, `name`, `role`. Role strings: `admin`, `faculty`, `professor` (students do not log in). Uses `password_verify` against `admin_tbl` / `faculty_tbl` / `prof_tbl`.
- Admin portal: `admin_dashboard.php`, `admin.php`, `admin_profile.php`, `professors.php`, `faculty.php`, `students.php`, `student_subs.php`, `student_secs.php`, `schedule.php`, `login_logs.php`.
- Faculty portal: `faculty_overview.php`, `faculty_dashboard.php`, `faculty_schedule.php`, `faculty_profile.php`, `view_students.php`, `view_professors.php`, `room_logs.php` (multi-role).
- Professor portal: `prof_dashboard.php`, `prof_schedule.php`, `prof_students.php`, `prof_attendance.php`, `prof_profile.php`.
- Controllers (redirect/JSON only): `add_faculty.php`, `add_schedule.php`, `add_section.php`, `add_student.php`, `add_subject.php`, all `delete_*.php`, `edit_students.php`, `logout.php`, `get_active_class.php`, `update_ended_classes.php`, `send_attendance_summary.php`. Several `edit_*.php` render a form and post to themselves.

**Shared includes:** `sections/nav.php` (kiosk header + login), `nav2.php` (professor), `nav3.php` (faculty), `nav4.php` (admin); `prof_nav.php` / `fac_nav.php` / `admin_nav.php` are the respective sidebars. Every portal page includes one header + one sidebar.

**DB access:** `backend/db.php:8` is the single PDO connection (now env-driven); almost every backend file requires it. `index.php` also requires it. No ORM.

**JSON API endpoints to port**
1. `POST /index.php` — form field `rfid_tag` → attendance JSON (called by `js/tap.js`).
2. `GET /backend/get_active_class.php` — no params, polled every 30s by `js/activeClass.js`.
3. `GET /backend/update_ended_classes.php` — no params, called after a tap.
4. `POST /backend/send_attendance_summary.php` — JSON body `{email, section, date}`, professor-only, sends PHPMailer summary.
5. `POST /backend/main_dashboard.php` — older duplicate kiosk endpoint (parked in `_to_review/`; not linked by the app).

**Auth/authorization:** 14 files guard on `user_id` + `role` per-file; there is no central middleware. Many admin pages and **all** `add_/edit_/delete_` controllers have no guard at all. The migration should centralize this rather than reproduce the gap.

**Schema:** canonical dump is `labguard.sql` (12 tables incl. `login_logs`, 2025-05-09). `labguard (2).sql` / `(4).sql` are older 11-table copies; `backend/school_attendance.sql` is an older 11-table dump from a different database name. All are untracked now but remain on disk for reference.

### What I did
- Deleted `js/tap_old.js` — explicitly an old copy, zero references anywhere.
- Moved 10 unreferenced files to `_to_review/` (with a `README.md` explaining each): `main_dashboard.php`, `check_attendance.php`, `db_check.php`, `db_update.php`, `update_db.php`, `debug_log.php`, `debug_login_logs.php`, `fix_status.php`, `testing.php`, `nav_new.php`. All were verified to have **no code references** (only README mentions for four of them). Several of them mutate the DB on load, so parking them also removes an accidental-data-change hazard.
- Verified every other JS/CSS/section file has at least one real code reference; no exact-content duplicate files were found by hash.
- Added `.gitattributes` to normalise line endings across PHP/JS/CSS/TS/JSON/MD/SQL/SVG.
- Updated the README directory map to mark the parked files.

### What I skipped
- No broad reformatting of the remaining PHP files: they are about to be replaced by the Next.js port, and churning them now would add noise without benefit.
- Did not delete any parked file — you get the final call.
- No database changes of any kind.

### NEEDS MY DECISION
- **Runtime schema mutation in live pages:** `index.php:13–19` runs `ALTER TABLE attendance_tbl` on every kiosk load; `backend/schedule.php:16–17` and `backend/faculty_schedule.php:31` run `ALTER TABLE schedule_tbl`; `backend/login_logs.php:19` runs `CREATE TABLE IF NOT EXISTS login_logs`. I will **not** port these side effects to Next.js. If you rely on them to keep the schema in sync, tell me — otherwise the new app will assume the existing schema is already correct.
- Missing authorization on admin pages/controllers in the legacy app: I plan to enforce role checks centrally in the Next.js port. Say if any endpoint should remain public.
- Keep or delete `_to_review/` and the untracked SQL dumps/uploads once migration is verified.

---

## Phase 3 — Next.js migration (pending)
