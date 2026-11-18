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

## Phase 3 — Next.js migration (done, with caveats below)

### ORM choice: Prisma
Prisma's `db pull` introspects an existing MariaDB schema directly, which is exactly what you asked for — I did not hand-write the 12-table model. Drizzle's introspection is more manual and would have required reconciling types by hand. Prisma Client also generates typed accessors for the odd enum names in this schema (`No Schedule`, `1`–`4` year levels). `prisma generate` runs against `DATABASE_URL` from `.env`.

**Important:** the live `labguard` database in XAMPP is **broken** — every one of its 12 tables returns MySQL error 1932 (`doesn't exist in engine`; orphaned `.frm` files, missing InnoDB tablespace). This matches errors in `debug.log`. I did **not** repair, drop, or alter it. To introspect safely I created a separate database `labguard_schema_introspect`, imported the canonical `labguard.sql` into it, ran `prisma db pull`, then changed the datasource URL back to `env("DATABASE_URL")`. That scratch DB still exists and contains the dump's data — it is the only working copy of the schema/data on this machine right now, and I smoke-tested the app against it.

### What changed
- Next.js 15 App Router + TypeScript scaffold (`app/`, `components/`, `lib/`, `prisma/`).
- `prisma/schema.prisma` generated by introspection; numeric year-level enum values given Prisma-safe names with `@map` so DB values are unchanged.
- Ported endpoints (each committed separately):
  - `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me` — HMAC-signed httpOnly cookie session, bcryptjs verifying PHP `$2y$` hashes, role checks centralized in `lib/auth.ts`. Login now also writes `login_logs` (the old login page never did).
  - `POST /api/tap` — full RFID attendance state machine (student/professor check-in/out, wrong class, no schedule, late marking, auto check-out of students, ended-class expiry). Times use `Asia/Manila`.
  - `GET /api/active-classes`, `GET /api/update-ended-classes`, `POST /api/attendance/summary` (Nodemailer, env-based SMTP).
  - Generic validated CRUD `GET/POST /api/[resource]` and `GET/PATCH/DELETE /api/[resource]/[id]` for admins, faculty, professors, students, sections, subjects, rooms, schedules, attendance, enrollments; logs and roles are read-only. All writes are role-guarded; passwords are bcrypt-hashed on create/update.
  - `GET /api/uploads/[name]` serves existing photos from `backend/uploads/` with path traversal blocked.
- Ported pages: kiosk `/` (RFID input, live clock, active-class polling, recent taps), `/login`, and admin/faculty/professor portals plus their subpages. Legacy CSS is reused; I fixed a stray `/` and a broken font path in `css/styles.css` that Next's CSS parser rejected.
- Verification after each step: `tsc --noEmit`, `next build`, `next lint`. All pass; 4 non-blocking lint warnings remain (custom `<link>` fonts and three `<img>` tags).
- Smoke test with the production server against the scratch DB: `/api/auth/me` → 401, `/api/active-classes` → 200 JSON, `/login` and `/` → 200, unknown RFID tap → JSON error, unauthenticated `/api/students` → 401, missing upload → 404. No 500s.

### Dependencies added (and why)
- `next`, `react`, `react-dom`, `typescript`, `@types/*`, `eslint`, `eslint-config-next` — required for the target stack and the build/lint gate.
- `prisma` + `@prisma/client` — introspection and typed DB access (see choice above).
- `bcryptjs` — verify existing PHP `password_hash()` (`$2y$`) hashes; pure JS, no native build.
- `nodemailer` — replaces PHPMailer for the attendance-summary email.
- `@tailwindcss/postcss` was installed but **removed from the PostCSS pipeline** (Tailwind v4 plugin broke on the legacy plain CSS); `tailwindcss` remains in devDependencies from the original repo but is unused. Safe to drop later.

### How to run locally
1. Copy `.env.example` to `.env` (already present locally, gitignored).
2. Set `DATABASE_URL` for XAMPP MariaDB, e.g. `mysql://root:@127.0.0.1:3306/labguard`. If you set a DB password, URL-encode it: `mysql://root:my%40pass@127.0.0.1:3306/labguard`.
3. Set `MAIL_*` to a real SMTP account/app password and replace `AUTH_SECRET` with `openssl rand -base64 32`.
4. Start Apache and MySQL in XAMPP. Make sure the `labguard` database actually has readable InnoDB tables — see the blocker below.
5. `npm install && npx prisma generate && npm run dev`, then open `http://localhost:3000`.
   - Re-introspect only if the schema changes: `npx prisma db pull` (use the mirror if the binary CDN is blocked: `PRISMA_ENGINES_MIRROR=https://registry.npmmirror.com/-/binary/prisma`).
6. No PHP/Apache routing is needed for the new app; the PHP files remain in the tree as a reference implementation only.

### What I skipped / is not yet equivalent
- **Photo uploads are not ported** — the new app can serve existing files via `/api/uploads/[name]`, but there is no upload endpoint, so creating/editing a user's `photo` field only stores a filename.
- The legacy PHP files are still in the repo (and the untracked SQL dumps/uploads remain on disk). They are not served by Next.js.
- The professor "My Schedule" page currently lists all schedules; it is not filtered to the signed-in professor.
- Attendance-summary email still needs real `MAIL_*` values to actually send; the endpoint was verified to compile, not to deliver mail.
- Some legacy pages (profile editors, room-log viewers, debug pages) are collapsed into the generic resource panels rather than ported one-for-one.

### NEEDS MY DECISION
- **The live `labguard` DB is unreadable (error 1932).** Until you restore/repair it, the new app cannot run against `labguard`. Options: (a) restore the tablespace from a backup, or (b) rename the broken database and use a fresh database imported from `labguard.sql`. I did neither because both change the DB. The isolated `labguard_schema_introspect` database is working and can be renamed/used if you approve.
- Confirm whether the generic CRUD panels are enough, or you want each legacy admin/faculty/professor page ported individually with its original layout.
- Decide whether to delete the PHP app and `_to_review/` once you are happy with the port.
- Rotate the credentials listed in Phase 1 — still outstanding.

### Commit trail (this branch)
`761aab1` README → `50d7351` Phase 1 security → `ad51421` Phase 2 cleanup → `58a2487` Next.js scaffold + Prisma → `9f22dc4` auth → `97664dd` tap API → `b2c4d0f` active/ended/summary APIs → `2eca25e` CRUD → `e71eed8` kiosk page → `aabb2d1` portals → `150c521` portal subpages.

Final PROGRESS write-up and any follow-up decisions can be added here.

---

## Follow-up — decisions resolved + scanner fallback (2026-11-19)

### Decisions you gave
1. **Broken DB → fresh restore from dump.** Done (details below).
2. **Photo uploads → port them.** Done.
3. **Portal scope → generic CRUD panels are enough.** No change; the reusable panels stay.
4. **Legacy PHP app → move to a gitignored `legacy/` folder, keep on disk.** Done.

### What I did
- **Restored `labguard`:** the broken database's 25 raw `.frm`/`.ibd` files were copied
  to `C:\xampp\mysql\broken_db_backups\labguard_broken_20261119\` first (MariaDB can't
  rename databases, and the orphaned `.ibd` files blocked a plain DROP). Then dropped
  the broken DB, created a fresh `labguard` (`utf8mb4_general_ci`) and imported
  `legacy/labguard.sql`. Verified: **12 tables, all readable**
  (7 students, 18 attendance rows, 3 login logs). Nothing was repaired in place —
  the original tablespace files are preserved in case you want to recover them later.
  The scratch `labguard_schema_introspect` DB still exists and can now be dropped at
  your discretion; I left it alone.
- **Photo uploads:** added `POST /api/uploads` (admin/faculty only) — accepts PNG/JPEG/
  WebP/GIF up to 5 MB, writes a UUID filename into the gitignored top-level `uploads/`,
  and updates the record's `photo` column. Wired a file input into the resource panels
  for admins/faculty/professors/students. Verified unauthenticated uploads → 401 and
  photo serving → 200 `image/jpeg`.
- **Legacy move:** all PHP source, `vendor/`, `_to_review/`, SQL dumps, logs and assets
  now live under `legacy/` (gitignored, on disk, **never served by Next.js**). The
  canonical dump is `legacy/labguard.sql`. Branding assets were copied to `public/assets/`;
  the five CSS files + DigitalNumbers font were copied to `app/styles/` and `app/fonts/`
  so the Next.js build does not depend on `legacy/`. Uploaded photos moved to `uploads/`,
  also gitignored. `README.md` now documents the Next.js app.
- **Fixed a bug from my earlier work:** the unanchored `logs/` ignore rule had silently
  prevented `app/admin/logs/page.tsx` from ever being committed. Anchored it to `/logs/`
  and the page is now tracked (verified 200).
- **Temporary click-to-tap fallback** (you said the RFID scanner broke):
  - `DEMO_TAP_ENABLED=true` in `.env` (and `.env.example`). Set to `false`/remove to disable.
  - `GET /api/demo/people` returns the registered students/professors (tag + label)
    only when demo mode is on; otherwise 404.
  - The kiosk shows a person picker and the ID circle becomes a button — choose a
    person, click the circle, and it submits their tag through the real `/api/tap`
    state machine. It is labelled "DEMO MODE" in the UI. Smoke test: demo endpoint
    returned 12 people, a real tag tap produced the expected attendance JSON.
  - **Remove `/api/demo/people`, the picker, and the env flag once the scanner works.**

### Verification after this round
`tsc --noEmit`, `next build`, `next lint` all pass (same 5 non-blocking warnings:
custom font + `<img>` tags). Production smoke test against the restored `labguard`:
kiosk 200, `/assets/IDtap.svg` 200, `/api/demo/people` 200 (12 people),
`/api/tap` with a real tag → expected JSON, `/api/uploads` unauthenticated → 401,
photo serve → 200, `/admin/logs` → 200. No 500s.

### Still outstanding (your action / optional)
- **ROTATE THE CREDENTIALS** listed in Phase 1 — unchanged and urgent.
- New commits this round: `ccb8bfe` (legacy move) and `abdc46e` (uploads) — plus this
  PROGRESS update. Merged into `main` locally at the end of this session as you asked;
  **nothing was pushed**.
- Professor "My Schedule" still lists all schedules (unfiltered) — small fix if wanted.
- SMTP still needs real `MAIL_*` values to actually deliver email.
- The generic portal pages remain collapsed rather than one-for-one PHP layouts, per your call.

### Follow-up 2 — schedule filter + scratch DB (2026-11-19)
- **Professor schedule filtering:** enforced server-side in `listResource` — any
  professor requesting `/api/schedules` is scoped to their own `prof_user_id`,
  regardless of query string. This covers both the Overview and My Schedule pages
  and cannot be bypassed by editing the URL. Verified with signed sessions:
  professor (id 7) got only their 1 schedule even with `?prof_user_id=4`; an admin
  still got all 9. Commit `70781c4`.
- **Scratch DB dropped:** `labguard_schema_introspect` was removed (it was only for
  Prisma introspection). `labguard` remains intact and readable (7 students).
- **Kept as requested:** the broken-DB raw backup under `C:\xampp\mysql\broken_db_backups\`
  and the demo tap fallback (physical scanner still broken).
- Typecheck/build/lint still pass. Merged into `main` locally; nothing pushed.
