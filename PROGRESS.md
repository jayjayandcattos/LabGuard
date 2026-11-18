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

## Phase 2 — Cleanup (pending)

## Phase 3 — Next.js migration (pending)
