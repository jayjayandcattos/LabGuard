# LabGuard 🛡️
### Smart Computer Laboratory Access & RFID Attendance Management System

[![Next.js](https://img.shields.io/badge/Next.js-15_App_Router-000000?style=for-the-badge&logo=next.js&logoColor=white)](https://nextjs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Prisma](https://img.shields.io/badge/Prisma-ORM-5C3EE8?style=for-the-badge&logo=prisma&logoColor=white)](https://www.prisma.io/)
[![MariaDB](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Node.js](https://img.shields.io/badge/Node.js-18%2B-339933?style=for-the-badge&logo=node.js&logoColor=white)](https://nodejs.org/)

> **Current application:** the Next.js app in `app/`, `components/`, `lib/`, and `prisma/`.
> The original PHP implementation is preserved for reference in the gitignored `legacy/` folder.
> See `PROGRESS.md` for migration status and outstanding decisions.

---

## 📌 Table of Contents
- [Overview](#-overview)
- [Key Features](#-key-features)
- [System Architecture](#-system-architecture)
- [Role-Based Access Matrix](#-role-based-access-matrix)
- [Technology Stack](#-technology-stack)
- [Database Schema & Data Model](#-database-schema--data-model)
- [Directory Structure](#-directory-structure)
- [Installation & Setup Guide](#-installation--setup-guide)
- [RFID Hardware Setup](#-rfid-hardware-setup)
- [Email Notification Configuration](#-email-notification-configuration)
- [Troubleshooting & Maintenance](#-troubleshooting--maintenance)
- [License](#-license)

---

## 📸 Overview

**LabGuard** is an enterprise-grade, real-time Computer Laboratory Access & Attendance Tracking System powered by RFID hardware integration. Designed specifically for academic institutions and computer laboratory administrators, LabGuard automates student and professor attendance check-ins, monitors room occupancy, validates class schedules dynamically, and generates automated absent student reports via email.

Instead of manual sign-in sheets or delayed roll calls, LabGuard allows students and faculty members to scan their RFID cards at a terminal kiosk (`index.php`). The system automatically validates schedules, detects unauthorized class entries ("Wrong Class"), logs attendance statuses (Present, Late, Absent, Ended, No Schedule), updates laboratory room occupancy, and notifies instructors of absenteeism upon class completion.

---

## ✨ Key Features

### 🎛️ 1. Interactive RFID Attendance Kiosk (`index.php`)
* **Instant RFID Card Processing**: Real-time card scanning support via keyboard wedge or HTTP POST requests.
* **Smart Tap Identification**: Auto-detects whether the scanned RFID card belongs to a **Student** or a **Professor**.
* **Dynamic Schedule & Room Matching**: Cross-references the current day and time against scheduled lab sessions to ensure users are in the correct room.
* **Automatic Attendance Status Classification**:
  * `Present`: On-time attendance during an active class schedule.
  * `Late`: Check-in after grace period limits.
  * `Wrong Class`: Tap-in by a student not enrolled in the current section or subject schedule.
  * `No Schedule`: RFID tap when no valid class is scheduled in the room.
  * `Ended`: Class check-out timestamp triggered when the professor ends the session.
* **Audio-Visual Feedback & Live Clock**: Interactive UI notifications with custom sound alerts (`js/tap.js`).

### 👑 2. Multi-Role Administration & Portals

#### 🛡️ Administrator Portal (`backend/admin_dashboard.php`)
* **User Management**: Complete CRUD operations for Admins, Faculty members, Professors, and Students.
* **Schedule Management**: Create, edit, and assign subjects, professors, sections, rooms, and weekly time slots.
* **Classroom & Facility Maintenance**: Monitor room statuses (`Vacant`, `Occupied`, `Maintenance`) and adjust room capacity.
* **System & Audit Logs**: Track user login activity (`login_logs.php`), IP addresses, and room entry/exit histories (`room_logs.php`).

#### 🏫 Faculty Portal (`backend/faculty_overview.php`)
* **Laboratory Overview**: Real-time monitoring of all laboratory rooms, ongoing classes, and room availability.
* **Schedule Directory**: Master timetable of all professor schedules and assigned sections.
* **Student & Professor Directories**: View rosters, section assignments, and user records.

#### 👨‍🏫 Professor Portal (`backend/prof_dashboard.php`)
* **Active Class Control**: View currently active classes, start/end lab sessions, and track real-time student check-ins.
* **Class Attendance Roster**: View section student lists with instant attendance indicators (Present, Absent, Late, Wrong Class).
* **Schedule Viewer**: Personalized weekly class schedules detailing room assignments and time slots.
* **Automated Summary Dispatch**: Trigger or receive automated absent student digests via email upon class completion.

#### 🎓 Student Management
* **Roster Integration**: Linked by Student ID, Section (`section_tbl`), Subject Enrollment (`student_enrollment_tbl`), Year Level, and Status (`Regular` / `Irregular`).
* **RFID Card Association**: Unique RFID tag mapping for every enrolled student.

### 📧 3. Automated Email Reporting System (`mail_functions.php`)
* **PHPMailer Integration**: Sends HTML-formatted attendance summary reports directly to professors upon class completion.
* **Absent Student Digest**: Automatically identifies enrolled students who failed to tap into the laboratory session and compiles a detailed table delivered via SMTP.

---

## 🏗️ System Architecture

```
                                 ┌─────────────────────────┐
                                 │   RFID Card Reader      │
                                 └────────────┬────────────┘
                                              │ (Card ID)
                                              v
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │                                   LabGuard Kiosk                                      │
 │                                    (index.php)                                         │
 └─────────────┬──────────────────────────────┬─────────────────────────────┬─────────────┘
               │                              │                             │
               v                              v                             v
 ┌──────────────────────────┐   ┌──────────────────────────┐   ┌──────────────────────────┐
 │     Admin Portal         │   │      Faculty Portal      │   │     Professor Portal     │
 │ (admin_dashboard.php)    │   │  (faculty_overview.php)  │   │   (prof_dashboard.php)   │
 └─────────────┬────────────┘   └─────────────┬────────────┘   └─────────────┬────────────┘
               │                              │                             │
               └──────────────────────────────┼─────────────────────────────┘
                                              │
                                              v
                                ┌──────────────────────────┐
                                │     MySQL Database       │
                                │       (labguard)         │
                                └─────────────┬────────────┘
                                              │
                                              v
                                ┌──────────────────────────┐
                                │   PHPMailer Mail Engine  │
                                │ (sendAbsentStudentsEmail)│
                                └──────────────────────────┘
```

---

## 👥 Role-Based Access Matrix

| Feature / Module              | Admin | Faculty | Professor | Student |
|:------------------------------|:-----:|:-------:|:---------:|:-------:|
| RFID Attendance Kiosk Tap     |  ✅   |   ✅    |    ✅     |   ✅    |
| Manage Users (CRUD)           |  ✅   |   ❌    |    ❌     |   ❌    |
| Manage Rooms & Facilities     |  ✅   |   👁️    |    👁️     |   ❌    |
| Create & Edit Schedules       |  ✅   |   ❌    |    ❌     |   ❌    |
| View Real-time Active Classes |  ✅   |   ✅    |    ✅     |   ❌    |
| Manage Class Attendance       |  ✅   |   👁️    |    ✅     |   ❌    |
| View Audit & Login Logs       |  ✅   |   👁️    |    ❌     |   ❌    |
| Receive Absent Email Reports  |  ❌   |   ❌    |    ✅     |   ❌    |

*Legend: ✅ Full Access | 👁️ Read-Only Access | ❌ Restricted*

---

## 🛠️ Technology Stack

* **Frontend**: PHP 8.x, HTML5, JavaScript (ES6+ AJAX, Fetch API), CSS3 / Tailwind CSS v4.0.
* **Backend**: PHP 8.x (PDO Object-Oriented Database Layer, Password Hashing API, Native Session Management).
* **Database**: MySQL / MariaDB (InnoDB engine, foreign keys, transaction handling, optimized ENUMs and indexes).
* **Email Service**: PHPMailer v6.10 via Composer SMTP.
* **Assets & UI Components**: Tailwind CSS, Google Fonts, Custom JavaScript event listeners (`js/tap.js`, `js/activeClass.js`).

---

## 🗄️ Database Schema & Data Model

The database (`labguard.sql`) consists of 10 primary relational tables:

```
                          ┌──────────────┐
                          │  roles_tbl   │
                          └──────┬───────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         │                       │                       │
         v                       v                       v
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    admin_tbl    │     │   faculty_tbl   │     │    prof_tbl     │
└─────────────────┘     └─────────────────┘     └────────┬────────┘
                                                         │
                                                         v
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   section_tbl   │◄────┤  schedule_tbl   ├────►│   subject_tbl   │
└────────┬────────┘     └────────┬────────┘     └─────────────────┘
         │                       │
         v                       v
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   student_tbl   │     │    room_tbl     │     │ attendance_tbl  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Table Breakdown

1. **`admin_tbl`**: System administrator accounts and credentials.
2. **`faculty_tbl`**: Facility coordinators and laboratory administrators.
3. **`prof_tbl`**: Teaching faculty members with RFID mapping (`rfid_tag`).
4. **`student_tbl`**: Student profiles linked to section (`section_id`), year level, regularity status, and RFID tag.
5. **`roles_tbl`**: System role definitions (`Admin`, `Faculty`, `Professor`, `Student`).
6. **`room_tbl`**: Laboratory classrooms (`room_number`, `room_name`, `status`: `Vacant`, `Occupied`, `Maintenance`).
7. **`section_tbl`**: Academic section designations (e.g., `SBIT2I`, `SBIT2G`).
8. **`subject_tbl`**: Course offerings linked to assigned professors.
9. **`schedule_tbl`**: Weekly class timetable linking Professor, Subject, Section, Room, Schedule Day, Start Time, and End Time.
10. **`attendance_tbl`**: Core log table recording timestamps (`time_in`, `time_out`), status (`check_in`, `check_out`, `ended`, `no_schedule`, `wrong_class`), and attendance state (`Present`, `Absent`, `Late`, `Ended`, `No Schedule`, `Wrong Class`).
11. **`login_logs`**: Security audit table recording user logins, roles, timestamps, and IP addresses.

---

## 📂 Directory Structure

```
LabGuard/
├── app/                        # Next.js App Router — pages and route handlers
│   ├── api/                    # Ported API: auth, tap, active-classes, CRUD, uploads, demo
│   ├── admin/                  # Administrator portal + subpages
│   ├── faculty/                # Faculty portal + subpages
│   ├── professor/              # Professor portal + subpages
│   ├── login/                  # Staff sign-in page
│   ├── styles/                 # Self-contained copy of the legacy CSS
│   ├── globals.css             # Global styles + kiosk/portal additions
│   ├── layout.tsx              # Root layout
│   └── page.tsx                # RFID kiosk (with temporary click-to-tap demo)
├── components/                 # Shared portal shell and resource panel
├── lib/                        # Prisma client, auth/session, attendance, CRUD, mail helpers
├── prisma/schema.prisma        # Introspected MariaDB schema (do not redefine tables)
├── public/assets/              # Branding images served by Next.js
├── app/fonts/                  # DigitalNumbers font used by the kiosk clock
├── uploads/                    # User photos (gitignored, personal data)
├── .env.example                # Environment variable template (placeholders only)
├── PROGRESS.md                 # Migration status, run instructions, rotation list
└── readme.md                   # This document
```

The original PHP application, its assets, SQL dumps, logs and Composer vendor
directory are preserved on disk under `legacy/` (gitignored, not versioned).
They are a reference implementation only and are not served by Next.js.

---

## ⚡ Installation & Setup Guide

### Prerequisites
* **Node.js**: `v18+` and npm.
* **Database**: MariaDB via XAMPP (or MySQL `5.7+`). Start MySQL in XAMPP before running the app.
* No PHP, Apache routing, or Composer needed — the PHP app is reference-only under `legacy/`.

---

### Step-by-Step Installation

#### 1. Install dependencies
```bash
cd path/to/LabGuard-main
npm install
```

#### 2. Prepare the database
Create a `labguard` database in XAMPP (`utf8mb4_general_ci`) and import the
canonical dump. It lives at `legacy/labguard.sql` (gitignored, kept on disk).
If you need a clean schema, that dump is the source of truth — do not redefine
tables by hand; `prisma/schema.prisma` was introspected from it.

#### 3. Configure the environment
Copy `.env.example` to `.env` and fill in:
* `DATABASE_URL` — e.g. `mysql://root:@127.0.0.1:3306/labguard` (URL-encode a password if you set one).
* `AUTH_SECRET` — generate with `openssl rand -base64 32`.
* `MAIL_*` — a real SMTP account/app password for attendance-summary email.
* `DEMO_TAP_ENABLED=true` — temporary click-to-tap kiosk fallback (see below).

#### 4. Generate the Prisma client and run
```bash
npx prisma generate
npm run dev
```
Open `http://localhost:3000` for the kiosk, `/login` for staff sign-in.

#### Temporary click-to-tap demo
The physical RFID scanner is currently unavailable. With `DEMO_TAP_ENABLED=true`,
the kiosk shows a picker of registered students/professors and the ID circle
becomes clickable — choose a person and click the circle to simulate a tap
through the real `/api/tap` state machine. Disable/remove this once the scanner
works. See `PROGRESS.md`.

---

## 💳 RFID Hardware Setup

LabGuard is compatible with standard USB RFID Card Readers operating in **Keyboard Wedge Mode** (125kHz EM4100 / 13.56MHz Mifare).

1. Connect the USB RFID scanner to the kiosk terminal PC.
2. Open the kiosk at `http://localhost:3000` in full-screen mode.
3. Ensure cursor focus remains on the RFID input box (handled automatically).
4. When a user taps their card, the scanner submits the tag ID to `/api/tap`.
   If the scanner is broken, use the temporary click-to-tap demo above.

---

## ✉️ Email Notification Configuration

LabGuard sends attendance summaries via SMTP through `/api/attendance/summary`.

* **Triggering Reports**: A professor ends a class session; the endpoint queries enrolled students who did not check in and emails the summary.
* **Email Template**: Formatted HTML table of student ID, name, and class schedule time.
* **Configuration**: Set the `MAIL_*` variables in `.env` (see `.env.example`). `MAIL_DEBUG` controls verbosity.

---

## 🔍 Troubleshooting & Maintenance

| Symptom / Error | Cause | Solution |
|:---|:---|:---|
| **"Scanned tag not registered"** | Scanned RFID card ID is not in `student_tbl`, `prof_tbl`, or `faculty_tbl`. | Register the RFID Tag under the respective user profile in Admin Dashboard. |
| **"No Schedule Found" on tap** | No active schedule exists in `schedule_tbl` for the current day and time. | Verify room number, current day/time, and schedule settings in Admin Portal. |
| **Database Connection Failure** | MySQL service stopped or incorrect `DATABASE_URL` in `.env`. | Start MySQL in XAMPP and check `DATABASE_URL`. |
| **Emails Not Sending** | Incorrect SMTP values in `.env`. | Set a real app password in `MAIL_PASSWORD`; see `.env.example`. |
| **Kiosk ID circle does nothing / no demo picker** | `DEMO_TAP_ENABLED` is not `true` in `.env`. | Set `DEMO_TAP_ENABLED=true`, restart `npm run dev`. |
| **Photos not showing** | Photos live in `uploads/` (gitignored). | Copy any saved photos into the top-level `uploads/` folder. |

---

## 📜 License

This project is developed for institutional and academic computer laboratory management. All rights reserved.

---
<p center align="center">Made with ❤️ for smart, secure, and automated laboratory management.</p>
