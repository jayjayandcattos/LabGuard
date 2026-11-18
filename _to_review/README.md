# _to_review — parked files

These files were unreferenced by application code (only mentioned in the old README, or not at all) when the codebase was inventoried during Phase 2 cleanup. Nothing here is loaded by the live app. They were **moved, not deleted**, so you can decide what to do with them.

| File | Why parked | Risk / note |
|---|---|---|
| `main_dashboard.php` | Older duplicate of the `index.php` RFID kiosk; no code links to it | Alters `attendance_tbl` on load — do not run against the live DB |
| `check_attendance.php` | Debug attendance viewer; README-only reference | Read-only |
| `db_check.php` | Diagnostic page; README-only reference | Creates `login_logs` and seeds rows on load |
| `db_update.php` | Schema update script; README-only reference | `ALTER TABLE` — do not run |
| `update_db.php` | Schema update script; no references | `ALTER TABLE` — do not run |
| `debug_log.php` | Log viewer; no references | Read-only |
| `debug_login_logs.php` | Debug login-log page; no references | Creates `login_logs` and seeds rows on load |
| `fix_status.php` | One-off data fixer; no references | Mass `UPDATE` of attendance rows — do not run |
| `testing.php` | Scratch test page; no references | Unknown side effects |
| `nav_new.php` | Unused duplicate of `sections/nav.php` | Contains a second copy of the login POST handler |

Note: relative `require` paths inside these parked files assume their original folders; they are not expected to run from here.

If you want any of this behaviour carried into the Next.js app, say so — the migration deliberately does **not** port runtime `ALTER TABLE` / `CREATE TABLE` / seed logic.
