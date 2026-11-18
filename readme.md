# LabGuard 🛡️
### Smart Computer Laboratory Access & RFID Attendance Management System

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![PHPMailer](https://img.shields.io/badge/PHPMailer-v6.10-006699?style=for-the-badge&logo=php&logoColor=white)](https://github.com/PHPMailer/PHPMailer)

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
├── assets/                     # Application branding, background media, and images
│   ├── uploads/                # User profile photos and avatars
├── backend/                    # Core PHP backend processing & API endpoints
│   ├── add_faculty.php         # Create faculty account controller
│   ├── add_schedule.php        # Create schedule entry controller
│   ├── add_section.php         # Create section controller
│   ├── add_student.php         # Create student profile controller
│   ├── add_subject.php         # Create course subject controller
│   ├── admin_dashboard.php     # System Administrator main dashboard
│   ├── admin_profile.php       # Admin account settings
│   ├── check_attendance.php    # (moved to _to_review/) Debug attendance viewer
│   ├── db.php                  # PDO database connection configuration (env-based)
│   ├── db_check.php            # (moved to _to_review/) DB diagnostics
│   ├── db_update.php           # (moved to _to_review/) Schema update script
│   ├── edit_*.php              # Modification controllers for all entities
│   ├── faculty_dashboard.php   # Faculty overview interface
│   ├── faculty_schedule.php    # Faculty master schedule viewer
│   ├── get_active_class.php    # API endpoint returning real-time active classes
│   ├── login.php               # Unified multi-role authentication handler
│   ├── login_logs.php          # Login audit history viewer
│   ├── logout.php              # Session termination controller
│   ├── main_dashboard.php      # (moved to _to_review/) Older duplicate kiosk
│   ├── prof_attendance.php     # Professor class attendance manager
│   ├── prof_dashboard.php      # Professor dashboard view
│   ├── prof_students.php       # Professor student roster view
│   ├── room_logs.php           # Laboratory room occupancy history
│   ├── schedule.php            # System-wide schedule manager
│   ├── send_attendance_summary.php # Manual/Auto trigger for email report dispatch
│   └── student_secs.php        # Section management view
├── css/                        # Custom stylesheets & Tailwind outputs
├── js/                         # Client-side JavaScript logic
│   ├── activeClass.js          # Dynamic active class polling script
│   ├── classroomManagement.js # Room state toggle logic
│   ├── loadingtransition.js   # UI state transitions
│   ├── login.js                # Login modal & form validation
│   ├── studentValidation.js    # Student form validation logic
│   ├── tap.js                  # Primary RFID tap kiosk event handler
│   └── time.js                 # Real-time digital clock component
├── sections/                   # Reusable UI navigation templates
│   ├── admin_nav.php           # Admin sidebar navigation
│   ├── fac_nav.php             # Faculty navigation bar
│   ├── prof_nav.php            # Professor navigation bar
│   └── nav_new.php             # (moved to _to_review/) Unused nav duplicate
├── labguard.sql                # Complete database import dump
├── mail_config.php             # PHPMailer SMTP credentials configuration
├── mail_functions.php          # Email dispatch helper functions
├── composer.json               # PHP dependency specification
├── package.json                # Node.js build configuration (Tailwind CSS)
├── index.php                   # Main Kiosk Interface (RFID Tap Access Terminal)
└── readme.md                   # System documentation
```

---

## ⚡ Installation & Setup Guide

### Prerequisites
Before setting up LabGuard, ensure your local environment contains:
* **Web Server**: Apache or Nginx (e.g., [XAMPP](https://www.apachefriends.org/), WAMP, or LAMP stack).
* **PHP**: Version `8.0` or higher with `pdo_mysql` enabled.
* **Database**: MySQL `5.7+` or MariaDB `10.4+`.
* **Dependency Manager**: [Composer](https://getcomposer.org/) (for PHPMailer).
* **Node.js**: `v18+` (optional, required only if rebuilding Tailwind CSS).

---

### Step-by-Step Installation

#### 1. Clone or Download Repository
Place the repository inside your web server root (e.g., `C:\xampp\htdocs\LabGuard-main`):
```bash
git clone https://github.com/jayjayandcattos/LabGuard.git C:/xampp/htdocs/LabGuard-main
```

#### 2. Install PHP Dependencies
Navigate to the root directory and run Composer:
```bash
cd C:/xampp/htdocs/LabGuard-main
composer install
```

#### 3. Import Database
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or your preferred MySQL client.
2. Create a new database named **`labguard`** with collation `utf8mb4_general_ci`.
3. Import the file **`labguard.sql`** located in the root directory.

#### 4. Configure Database Credentials
Edit `backend/db.php` if your MySQL username or password differs from default XAMPP settings:
```php
<?php
$host = "localhost"; 
$dbname = "labguard"; 
$username = "root"; 
$password = ""; // Add password if configured

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

#### 5. Configure Email Service (SMTP)
Edit `mail_config.php` to set up absent student email reporting:
```php
define('MAIL_HOST', 'smtp.gmail.com');          // SMTP Server
define('MAIL_PORT', 587);                       // TLS Port (587) or SSL (465)
define('MAIL_USERNAME', 'your-email@gmail.com');// Sender Email
define('MAIL_PASSWORD', 'your-app-password');   // App Password (Google/Outlook)
define('MAIL_FROM', 'your-email@gmail.com');
define('MAIL_FROM_NAME', 'LabGuard System');
define('MAIL_ENCRYPTION', 'tls');
```

#### 6. Launch Application
Start Apache & MySQL from the XAMPP Control Panel, then access:
* **Kiosk Terminal**: `http://localhost/LabGuard-main/index.php`
* **Admin Login**: `http://localhost/LabGuard-main/backend/login.php`

---

## 💳 RFID Hardware Setup

LabGuard is compatible with standard USB RFID Card Readers operating in **Keyboard Wedge Mode** (125kHz EM4100 / 13.56MHz Mifare).

1. Connect the USB RFID scanner to the kiosk terminal PC.
2. Open `index.php` in full-screen mode on the terminal display.
3. Ensure cursor focus remains on the RFID input box (handled automatically by `js/tap.js`).
4. When a user taps their card, the RFID scanner submits the numerical tag ID directly into `index.php` for instant attendance processing.

---

## ✉️ Email Notification Configuration

LabGuard includes an automated absent reporting mechanism (`mail_functions.php`).

* **Triggering Reports**: When a professor finishes a class session in `backend/prof_dashboard.php`, the system queries all enrolled section students who did not check in during that scheduled slot.
* **Email Template**: Formatted HTML table highlighting student ID, student name, and class schedule time.
* **Debug Mode**: Toggle `MAIL_DEBUG` in `mail_config.php` (`0` for silent production, `2` for detailed verbose logs during setup).

---

## 🔍 Troubleshooting & Maintenance

| Symptom / Error | Cause | Solution |
|:---|:---|:---|
| **"Scanned tag not registered"** | Scanned RFID card ID is not in `student_tbl`, `prof_tbl`, or `faculty_tbl`. | Register the RFID Tag under the respective user profile in Admin Dashboard. |
| **"No Schedule Found" on tap** | No active schedule exists in `schedule_tbl` for the current day and time. | Verify room number, current day/time, and schedule settings in Admin Portal. |
| **Database Connection Failure** | MySQL service stopped or incorrect credentials in `backend/db.php`. | Ensure MySQL is running in XAMPP and credentials match in `backend/db.php`. |
| **Emails Not Sending** | Incorrect SMTP credentials or missing Google App Password. | Check `mail_config.php`. If using Gmail, generate an App Password via Google Security Settings. |
| **Logs & Error Debugging** | Runtime errors are captured in local log files. | Inspect `debug.log` in the root directory or `backend/debug.log`. |

---

## 📜 License

This project is developed for institutional and academic computer laboratory management. All rights reserved.

---
<p center align="center">Made with ❤️ for smart, secure, and automated laboratory management.</p>
