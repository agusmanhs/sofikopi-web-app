# 🏢 Sistem Absensi & Management Pegawai (SAMS)

A modern, enterprise-grade Attendance and Employee Management System built with **Laravel 12**, featuring **Geofencing**, **Photo Capture**, and **Granular Role-Based Access Control**.

This system is optimized for company-wide scalability using the **Service Repository Pattern** and includes a robust **Audit Trail** for compliance and monitoring.

---

## 🌟 Key Features

### 📍 Attendance & Geolocation

-   **Smart Attendance** - Clock-in and clock-out with real-time location tracking.
-   **Geofencing Validation** - Restrict attendance only within designated office/project areas.
-   **Photo Capture** - Mandatory selfie capture during attendance to prevent "fake" clock-ins.
-   **Attendance History** - Personal and administrative logs of all attendance activities.

### 📝 Leave & Permit Management (E-Izin)

-   **Digital Submissions** - Submit permits (Sick, Leave, Personal) with file attachments.
-   **Approval Workflow** - Multi-level approval system for HR and Managers.
-   **Status Tracking** - Real-time notifications and status updates for employees.

### 👥 Human Resource Management

-   **Pegawai Management** - Complete employee profiles and records.
-   **Divisi & Kantor** - Manage organizational structure and multiple office locations.
-   **Master Data** - Easy management of leave types and company settings.

### 🛡️ Enterprise Core (Built-in)

-   **Service Repository Pattern** - Clean, modular, and maintainable codebase.
-   **Granular RBAC** - Role-Based Access Control down to specific menu actions (CRUD).
-   **Activity Log (Audit Trail)** - Automatically track "Who, What, When" for every data change.
-   **Global Alert System** - Integrated SweetAlert2 & Toastr for seamless UX.

---

## 📁 Documentation Guide

Detailed guides for developers and system administrators:

| Guide                                                 | Description                                           |
| ----------------------------------------------------- | ----------------------------------------------------- |
| 📘 **[FEATURES_GUIDE.md](FEATURES_GUIDE.md)**         | **FULL OVERVIEW** of all technical modules.           |
| 🛠 **[DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md)**   | **CODING STANDARDS** and how to expand the system.    |
| 🕵️ **[ACTIVITY_LOG_GUIDE.md](ACTIVITY_LOG_GUIDE.md)** | Detailed audit trail & user monitoring documentation. |
| 🔔 **[ALERT_SYSTEM_GUIDE.md](ALERT_SYSTEM_GUIDE.md)** | How to use the global notification system.            |

---

## 🚀 Quick Start

### 1. Clone & Install

```bash
git clone https://github.com/ookapratama/web-absensi.git
cd web-absensi
composer install && npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Setup Database & Assets

```bash
php artisan migrate:fresh --seed
npm run build
```

### 4. Run the Project

```bash
composer dev
# or
php artisan serve
```

---

## 💡 Scalability: Expanding Beyond Attendance

This project is built on a highly modular foundation. You can easily add more features like:

-   **POS (Point of Sale)**
-   **Inventory Management**
-   **Payroll System**
-   **Employee Performance KPI**

To create a new module (e.g., POS), simply use the generator:

```bash
php artisan make:feature POS
```

---

## 📦 Tech Stack

-   **Backend**: Laravel 12.x, PHP 8.2+
-   **Frontend**: Bootstrap 5, Vite, jQuery (Sneat Template)
-   **Database**: MySQL / PostgreSQL
-   **Image Processing**: Intervention Image v3
-   **API Docs**: Swagger (L5-Swagger)

---

## 📄 License

This project is licensed under the [MIT license](LICENSE).

_Developed with ❤️ by [Ooka Pratama](https://github.com/ookapratama)_
