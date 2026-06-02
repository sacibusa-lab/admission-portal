# St. Augustine's College Admission Management Portal

A modern, secure, and responsive web-based Admission Management Portal for St. Augustine's College, Ibusa. Built using PHP Laravel, Bootstrap 5, MySQL, Termii SMS, and OpenRouter AI OCR.

---

## ⚡ Quick Start / Installation Guide

Follow these steps to set up the portal locally.

### 📋 Prerequisites

Ensure your system meets the following requirements:
* **PHP**: version `8.2` or higher (we used PHP `8.4.16`)
* **Composer**: version `2.0` or higher
* **MySQL**: version `8.0` or higher
* **Node.js & NPM** (for assets, if compiling)

---

### 🔧 Installation Steps

#### 1. Clone or copy the codebase
Ensure the code is placed in your local server directory (e.g., `H:\admission-portal`).

#### 2. Install dependencies
Run Composer in the project root to install the framework and PDF/OCR dependencies:
```bash
composer install
```

#### 3. Setup Environment Configuration
1. Copy the `.env.example` file to `.env`:
   ```bash
   copy .env.example .env
   ```
2. Open `.env` and configure your database parameters:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=admission_portal
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Generate the application secure key:
   ```bash
   php artisan key:generate
   ```

#### 4. Configure File Storage link
Run the storage link command to allow document/photo previews in the dashboard:
```bash
php artisan storage:link
```

#### 5. Build Database Schema & Seed Data
Ensure MySQL is running, then create the database `admission_portal` and run the migrations/seeders:
```bash
php artisan migrate:fresh --seed
```

#### 6. Start the Local Server
Run the Laravel local development server:
```bash
php artisan serve
```
By default, the application will run at [http://127.0.0.1:8000](http://127.0.0.1:8000).

---

## 🔐 Seeded Accounts

The database seeder registers three default accounts with the password `password123`:

1. **Super Admin**
   * **Email**: `admin@staugustine.edu.ng`
   * **Role**: Full access (Users, Settings, APIs, Admissions, Reports)
2. **Admission Officer**
   * **Email**: `officer@staugustine.edu.ng`
   * **Role**: Register applicants, OCR parsing, upload docs, transition applicant state.
3. **Principal**
   * **Email**: `principal@staugustine.edu.ng`
   * **Role**: Approve/Reject admissions, print letters, view reports, export metrics.

---

## 📡 API Integrations Setup

To enable real-time SMS alerts and AI Document scanning, log in as **Super Admin** and navigate to **Portal Settings** to paste your credentials:

### 1. Termii SMS
* Enter your **Termii API Key** and **Sender ID** (e.g., `SAC`).
* *Fallback*: If no key is set, the portal runs in **Mock SMS Mode**, logging transmissions to the database for evaluation without throwing errors.

### 2. OpenRouter OCR
* Enter your **OpenRouter API Key** and preferred Vision/LLM model (default is `google/gemini-2.5-flash`).
* *Fallback*: If no key is set, the portal runs in **Mock OCR Mode**, automatically populating the form with sample data when a document is uploaded.
