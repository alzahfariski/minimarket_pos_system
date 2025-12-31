# Minimarket POS System API

A robust, modular monolith API for Minimarket Point of Sale (POS) management, built with **Laravel 12**.

## 🚀 Features

-   **Modular Architecture**: Domain-driven design (`app/Domains`) for maintainability and scalability.
-   **Secure Authentication**:
    -   Email/Password with **OTP (Email)** verification.
    -   **Google Sign-In** integration.
    -   **Role-Based Access Control** (Admin/Owner for management, Cashier for sales).
    -   **User Profile Management**: Update details and secure password changes.
    -   **Password Recovery**: Forgot/Reset password flow.
    -   Strict Rate Limiting and Security Policies.
-   **Inventory Management**:
    -   **Strict Stock Logic**: Atomic transactions ensure stock integrity.
    -   **Purchasing (Stock In)**: Supplier management and purchase orders.
    -   **POS Transactions (Stock Out)**: Efficient sales processing with stock checks.
    -   **Stock Adjustments & Opname**: Audit tools for inventory corrections.
    -   **Smart Product Listing**: Out-of-stock items are automatically prioritized towards the bottom.
-   **Performance**:
    -   **Caching**: Optimized product listing with `products:list` cache.
    -   **Object Storage**: MinIO / S3 integration for scalable product images.
-   **Reliability**:
    -   Comprehensive Feature Test suite covering all critical paths.

---

## 🛠️ Prerequisites

-   **PHP**: 8.2 or higher.
-   **Composer**: Dependency manager.
-   **Database**: MySQL, PostgreSQL, or SQLite.
-   **Redis**: Required for Distributed Locking, Caching, and Rate Limiting.
-   **Object Storage**: MinIO (local) or AWS S3.
-   **Mail Server**: SMTP server (e.g., Mailpit for local dev) for OTPs and Reset Links.

---

## ⚙️ Installation & Setup

### 1. Clone & Install Dependencies

```bash
git clone <repository-url>
cd minimarket_pos_system
composer install
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` file. Below are the critical sections:

#### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minimarket_pos
DB_USERNAME=root
DB_PASSWORD=
```

DB_PASSWORD=

````

#### Redis (Performance & Locks)
Required for Caching and Stock Locks.

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
````

#### Authentication (Google)

Required for Google Login.

```env
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
```

#### Email (OTP & Reset Password)

Required for receiving Login OTPs and Password Reset links.

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@minimarket.com"
```

#### MinIO / S3 (Product Images)

Required for Image Uploads.

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=minimarket-bucket
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### 3. Database Setup

Run migrations to create the table structure.

```bash
php artisan migrate
```

---

## 🏃 Usage Guide

### 1. Registration (First Run)

There are no default seeders. You must register the first Owner account manually.

**Endpoint**: `POST /api/auth/register`
**Payload**:

```json
{
    "name": "Store Owner",
    "email": "owner@shop.com",
    "password": "password",
    "password_confirmation": "password"
}
```

_Note: This creates a user with `Admin` role but does **NOT** log you in._

### 2. Login Flow

1.  **Login**: `POST /api/auth/login` (Returns `2fa_required`).
2.  **Check Email**: Get the 6-digit OTP code.
3.  **Verify**: `POST /api/auth/verify-otp` with `{ "user_id": 1, "otp": "...", "device_name": "MyPC" }`.
4.  **Token**: Use the returned Bearer token for all protected requests.

### 3. Account Management

-   **Get Profile**: `GET /api/user`
-   **Update Profile**: `PUT /api/auth/profile`
    -   Payload: `{ "name": "New Name", "email": "new@email.com" }`
-   **Change Password**: `PUT /api/auth/password`
    -   Payload: `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`
-   **Forgot Password**: `POST /api/auth/forgot-password`
    -   Payload: `{ "email": "..." }`

### 4. Uploading Images (MinIO)

Ensure MinIO is running and the bucket exists (`AWS_BUCKET`).
**Endpoint**: `POST /api/products/{id}/image`
**Key**: `image` (File)

---

## 🧪 Testing

The project includes a full suite of Feature Tests ensuring business and security logic integrity.

Run all tests:

```bash
php artisan test
```

### Test Suites

-   `RegisterTest`: Registration & Rate Limiting.
-   `LoginTest` / `VerifyOtpTest`: Auth flow & OTPs.
-   `AuthFeaturesTest`: Profile, Password, and Reset handling.
-   `InventoryLifecycleTest`: Stock Adjustment, Opname, Image Upload (Mocked S3).
-   `PosTransactionTest`: Sales & Atomic Rollbacks.
-   `StockManagementTest`: Purchase & Stock logic.

---

## 📚 API Documentation

See `apidoc.md` for a detailed End-to-End API documentation.
