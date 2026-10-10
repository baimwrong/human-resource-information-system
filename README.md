# HRIS Enterprise — Human Resource Information System

[![Laravel](https://img.shields.io/badge/Laravel-13.x-red.svg)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-blue.svg)](https://www.postgresql.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.3-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Offline](https://img.shields.io/badge/Network-100%25%20Offline%20Ready-success.svg)](#offline-network-architecture)

**HRIS Enterprise** is a sophisticated, production-grade Human Resource Information System web application built with **Laravel 13**, **PostgreSQL 18**, **Bootstrap 5.3**, and local icon & charting libraries. 

It is specifically engineered to run in **high-security, isolated air-gapped corporate intranets and internal enterprise networks with zero internet access**, with all vendor stylesheets, icons, fonts, and javascript bundles packaged locally in the project repository.

---

## 🌟 Key Architecture & Features

### 1. 100% Offline / Air-Gapped Network Ready
- **Zero External CDN Dependencies**: All vendor assets (Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Font Awesome 6.5.2, and Chart.js 4.4.2) are physically stored inside `public/vendor/`.
- **Offline Avatars**: Dynamic high-contrast SVG data-URI avatars generated locally without third-party avatar CDNs.
- **Local Print Stylesheets**: Print and PDF-export support for payslips without loading cloud fonts or external scripts.

### 2. High-Performance PostgreSQL 18 Architecture
- Tailored for PostgreSQL 18 with native `jsonb` column schemas for flexible compensation structures, appraisal goal trees, and bulk import error reports.
- Comprehensive relational schema with composite indexes, foreign key cascades, and soft deletes on employee life-cycles.
- Immutable audit log table (`audit_logs`) capturing operational changes with client IP addresses and user agents.

### 3. Comprehensive HR Feature Modules
- **Employee Directory & 360° Profile**:
  - Personal information, emergency contacts, national ID, banking & tax details (NPWP/SSN).
  - Organizational hierarchy (department, designations, direct supervisor, shifts, contract types: permanent, probation, contract, intern).
  - Tabbed 360-degree view containing Personal Bio, Salary Structure, Past 30 Days Attendance, Annual Leave Balances, Issued Payslips, and Performance Reviews.
- **Real-Time Attendance & Time Tracking**:
  - Employee self-service Clock-In and Clock-Out with real-time digital clock widget.
  - Automatic lateness detection based on shift start time and configurable grace periods (e.g. 15 minutes).
  - Automatic work hours calculation and overtime tracking (> 8 hours).
  - HR daily attendance matrix with manual adjustment capabilities and audit reasons.
- **Leave & Time-Off Management**:
  - Configurable leave types (Annual Leave, Sick Leave, Maternity Leave, Paternity Leave, Unpaid Leave, Compassionate Leave).
  - Annual entitlement balance tracking (allocated, used, pending, remaining).
  - Leave application wizard with working-day calculation (excluding Saturdays & Sundays).
  - Multi-tier supervisor/HR approval or rejection workflow with remarks.
  - Automatic attendance synchronization: Approved leaves automatically reflect as `on_leave` on daily attendance sheets.
- **Payroll Processing & Official Payslips**:
  - Monthly batch generator automatically computing basic pay, housing, transport, and meal allowances.
  - Automatic prorated deductions for unpaid leaves and absenteeism.
  - Itemized `jsonb` payload breakdown stored permanently with each payslip.
  - Official, print-ready payslip document format with company letterhead, employee details, earnings vs deductions table, net salary, and signature authorizations.
  - Batch disbursement status (`draft` &rarr; `processed` &rarr; `paid`).
- **Admin Bulk Data Import System**:
  - High-volume CSV ingestion hub for **Employees**, **Departments**, **Attendance**, and **Salaries**.
  - One-click template downloads with valid sample data rows directly from the UI or `storage/app/templates/`.
  - Transaction safety toggle: Choose between **Strict Rollback** (roll back entire batch if any row fails) or **Skip & Report** (commit valid rows, collect per-row errors).
  - Detailed inspection modal and downloadable error CSV report highlighting the exact row number, identifier, and error cause.
- **Performance & Appraisal Reviews**:
  - Quarterly and annual appraisal cycles.
  - Multi-factor evaluation (Technical Mastery, Communication & Influence, Teamwork & Culture) with 1–5 ratings.
  - Overall composite score calculation, key strengths, areas for development, and goal tracking.
- **Internal Notice Board & Announcements**:
  - Company-wide or department-targeted announcements with priority tags (`urgent`, `info`, `normal`) and pinning.
- **Security & Role-Based Access Control (RBAC)**:
  - 4 distinct roles: `admin` (Super Admin), `hr_manager` (HR Operations), `manager` (Supervisors), and `employee` (Self-Service).

---

## 🗄️ Database Architecture (PostgreSQL 18)

| Table | Description | Key PostgreSQL Features |
|---|---|---|
| `users` | User credentials, roles, and session states | Enum role check, hashed passwords, last login tracking |
| `departments` | Organizational units and annual budgets | Unique code, self-contained budget decimal |
| `designations` | Job titles and salary pay bands | Department foreign key, min/max salary bands |
| `shifts` | Working shifts and timing | Start/end times, grace period minutes, night shift boolean |
| `employees` | Core employee demographic & job details | Self-referencing supervisor foreign key, soft deletes, `jsonb` custom attributes |
| `salary_structures`| Base pay, allowances, and recurring deductions | Unique employee constraint, high-precision decimals |
| `attendances` | Daily clock-in/out records | Unique `(employee_id, date)` index, IP address, adjustment flags |
| `leave_types` | Leave categories and policies | Paid/unpaid boolean, attachment requirements |
| `leave_balances` | Annual quota tracking per employee | Unique `(employee_id, leave_type_id, year)` composite index |
| `leave_requests` | Time-off applications and workflows | Status index, approver foreign key, working days decimal |
| `payroll_batches` | Monthly compensation runs | Unique `(month, year)` constraint, aggregated totals |
| `payslips` | Individual employee payslip statements | Unique `(employee_id, month, year)`, itemized `jsonb` breakdown |
| `performance_reviews`| Appraisal evaluations and scoring | Reviewer foreign key, composite scores, `jsonb` goals |
| `announcements` | Internal company communications | Pinned index, target department foreign key |
| `bulk_import_logs`| Audit history of CSV imports | Execution row metrics, `jsonb` per-row error logs |
| `audit_logs` | Immutable audit trail | Action and module indexes, `jsonb` diff changes, client IP |
| `settings` | System-wide configuration | Key-value storage with caching |

---

## 🚀 Quick Start Guide

You can run this application using **Docker & Docker Compose** (recommended, bypasses any local PHP version constraints) or via **traditional local PHP CLI**.

### Option A: Docker (Recommended — Instant PostgreSQL 18 & PHP 8.3/8.4)

Since your local computer may have an older PHP version (e.g. XAMPP PHP 7.4 or 8.0/8.2), Docker runs the application inside an isolated container with PHP 8.3/8.4 and PostgreSQL without altering your machine's global PHP:

1. **Clone or navigate to the project directory**:
   ```bash
   cd c:\xampp\htdocs\hris
   ```

2. **Start the containers**:
   ```bash
   docker compose up -d
   ```

3. **Install dependencies and seed the database** (if not run automatically):
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan migrate:fresh --seed
   ```

4. **Access the application**:
   Open your browser to: **[http://localhost:8000](http://localhost:8000)**

---

### Option B: Local CLI Setup (When Local PHP >= 8.2/8.3 is Available)

1. **Create PostgreSQL Database**:
   Log into PostgreSQL via `psql` or pgAdmin:
   ```sql
   CREATE DATABASE hris_db;
   ```

2. **Configure Environment Variables**:
   Verify `.env` contains your PostgreSQL credentials:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=hris_db
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

3. **Install Dependencies**:
   ```bash
   composer install
   ```

4. **Run Database Migrations & Seed Sample Data**:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Start the Local Development Server**:
   ```bash
   php artisan serve --port=8000
   ```
   Open **[http://localhost:8000](http://localhost:8000)** in your browser.

---

## 🔐 Default Demo User Accounts

The database seeder pre-populates realistic data for testing all system roles:

| Role | Email Address | Default Password | Access Level |
|---|---|---|---|
| **Super Admin** | `admin@enterprise-hris.local` | `Password123!` | Complete access: Bulk Import, Audit Logs, Settings, Payroll, Workforce |
| **HR Manager** | `hr@enterprise-hris.local` | `Password123!` | Employee directory, payroll runs, leave approvals, shifts, announcements |
| **Department Manager** | `manager@enterprise-hris.local` | `Password123!` | Subordinate oversight, leave approvals, attendance tracking, appraisals |
| **Employee** | `employee@enterprise-hris.local` | `Password123!` | Self-service: Clock in/out, view payslips, check leave balances, apply leave |

> **Tip**: The login screen features convenient **One-Click Quick Login Buttons** for instant credential auto-population during review and testing.

---

## 📥 Admin Bulk Data Import Guide

The Admin Bulk Import module can ingest hundreds of records in seconds with row-level validation.

### Accessing the Import Center:
1. Log in as **Super Admin** (`admin@enterprise-hris.local`).
2. Click **Bulk Data Import** in the left navigation sidebar.
3. Download the relevant CSV template or use the guidelines below.

---

### 1. Employees Import (`template_employees.csv`)
- **Action**: Creates portal user account with default password `Password123!`, creates employee profile, assigns department & designation, initializes salary structure, and creates annual leave quota balances.
- **CSV Headers**:
  ```csv
  employee_code,first_name,last_name,email,department_code,designation_code,gender,date_of_birth,phone,join_date,employment_type,basic_salary
  ```
- **Example Data**:
  ```csv
  EMP-2026-001,John,Doe,john.doe@example.com,ENG,SR-DEV,male,1992-05-15,+62812345678,2024-01-15,permanent,12500000
  EMP-2026-002,Jane,Smith,jane.smith@example.com,HRD,HR-EXEC,female,1995-08-20,+62812345679,2024-03-01,permanent,9500000
  ```

---

### 2. Departments Import (`template_departments.csv`)
- **Action**: Creates or updates organizational units and budgets.
- **CSV Headers**:
  ```csv
  code,name,description,budget,status
  ```
- **Example Data**:
  ```csv
  ENG,Engineering & Technology,Software development and infrastructure,500000000,active
  HRD,Human Resources & People Ops,Talent acquisition payroll and welfare,120000000,active
  ```

---

### 3. Attendance Import (`template_attendance.csv`)
- **Action**: Batch uploads historical or biometric punch card logs. Calculates working hours and overtime.
- **CSV Headers**:
  ```csv
  employee_code,date,clock_in,clock_out,status,notes
  ```
- **Example Data**:
  ```csv
  EMP-2026-001,2026-10-01,08:52:00,18:05:00,present,Regular morning clock-in
  EMP-2026-002,2026-10-01,09:25:00,18:15:00,late,Traffic delay on highway
  ```

---

### 4. Salaries Import (`template_salaries.csv`)
- **Action**: Updates monthly compensation structures and allowances for employees.
- **CSV Headers**:
  ```csv
  employee_code,basic_salary,house_allowance,transport_allowance,meal_allowance,tax_deduction,insurance_deduction,effective_date
  ```
- **Example Data**:
  ```csv
  EMP-2026-001,12500000,2000000,1000000,800000,750000,500000,2026-01-01
  EMP-2026-002,9500000,1500000,800000,600000,450000,380000,2026-01-01
  ```

---

## 📁 Project Directory Structure

```text
hris/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AnnouncementController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── AuditLogController.php
│   │   │   ├── AuthController.php
│   │   │   ├── BulkImportController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── DesignationController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── LeaveController.php
│   │   │   ├── LeaveTypeController.php
│   │   │   ├── PayrollController.php
│   │   │   ├── PerformanceReviewController.php
│   │   │   └── SettingController.php
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   ├── Models/
│   │   ├── Announcement.php
│   │   ├── Attendance.php
│   │   ├── AuditLog.php
│   │   ├── BulkImportLog.php
│   │   ├── Department.php
│   │   ├── Designation.php
│   │   ├── Employee.php
│   │   ├── LeaveBalance.php
│   │   ├── LeaveRequest.php
│   │   ├── LeaveType.php
│   │   ├── PayrollBatch.php
│   │   ├── Payslip.php
│   │   ├── PerformanceReview.php
│   │   ├── SalaryStructure.php
│   │   ├── Setting.php
│   │   ├── Shift.php
│   │   └── User.php
│   └── Services/
│       ├── AttendanceService.php
│       ├── AuditLogger.php
│       ├── BulkImportService.php
│       └── PayrollCalculationService.php
├── bootstrap/
│   ├── app.php
│   └── providers.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php        # Optimized for PostgreSQL 18
│   ├── filesystems.php
│   ├── logging.php
│   └── session.php
├── database/
│   ├── migrations/         # 17 PostgreSQL 18 migrations
│   └── seeders/
│       └── DatabaseSeeder.php
├── public/
│   ├── assets/
│   │   ├── css/custom.css  # Enterprise responsive styles & print CSS
│   │   └── js/app.js       # Live clock, sidebar toggle & file preview
│   ├── vendor/             # 100% Offline vendor assets
│   │   ├── bootstrap/      # Bootstrap 5.3.3 CSS & JS bundle
│   │   ├── bootstrap-icons/# Bootstrap Icons fonts & stylesheet
│   │   ├── fontawesome/    # Font Awesome 6.5.2 CSS & webfonts
│   │   └── chartjs/        # Standalone Chart.js 4.4.2 bundle
│   ├── .htaccess
│   └── index.php
├── resources/
│   └── views/              # Blade templates (layouts, auth, dashboard, etc.)
├── routes/
│   ├── web.php             # Protected RBAC web routes
│   ├── api.php
│   └── console.php
├── storage/
│   └── app/templates/      # CSV import templates
├── docker-compose.yml       # PostgreSQL 18 & PHP container definitions
├── Dockerfile
├── composer.json
└── README.md
```

---

## 🔒 Security & Data Integrity

1. **Role-Based Middleware (`RoleMiddleware`)**: Protected routes enforce role checks on server-side requests (`admin`, `hr_manager`, `manager`, `employee`).
2. **PostgreSQL Foreign Keys**: Relational constraints enforce integrity with `cascadeOnDelete` for child balances/logs and `nullOnDelete` for department managers and supervisors.
3. **Password Security**: Passwords are saved with bcrypt hashing.
4. **CSRF Protection**: All POST/PUT/DELETE forms implement Laravel CSRF tokens.
5. **Session Safety**: File and PostgreSQL session storage supported with strict cookie parameters.
6. **Audit Trails**: Critical changes are recorded with diff metadata into `audit_logs`.

---

## 📄 License
This Human Resource Information System is open-sourced software licensed under the **MIT License**.
