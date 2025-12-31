# Minimarket POS System - API Walkthrough

This document provides a comprehensive walkthrough of the implemented features, including authentication, product management, inventory control, and sales processing.

## 1. Authentication & Profile Management

The system uses a secure, multi-step authentication process.

### 1.1. Registration

New users (Owners) register here.

-   **Endpoint**: `POST /api/auth/register`
-   **Body**:
    ```json
    {
        "name": "Admin User",
        "email": "admin@example.com",
        "password": "strongPassword123!",
        "password_confirmation": "strongPassword123!"
    }
    ```

### 1.2. Login (2FA Initiated) 🚀 **(Redis Rate Limiting)**

Authenticating returns a `2fa_required` status, sending an OTP to the user's email.

-   **Endpoint**: `POST /api/auth/login`
-   **Body**: `{ "email": "admin@example.com", "password": "strongPassword123!", "device_name": "MacBook" }`

### 1.3. Verify OTP & Get Token 🚀 **(Redis Rate Limiting)**

Exchange the OTP for a Sanction Bearer token.

-   **Endpoint**: `POST /api/auth/verify-otp`
-   **Body**: `{ "user_id": 1, "otp": "123456", "device_name": "MacBook" }`
-   **Response**: `{ "token": "1|..." }`

### 1.4. Profile Management

Authenticated users can manage their account.

-   **Get Profile**: `GET /api/user`
-   **Update Profile**: `PUT /api/auth/profile`
    -   Body: `{ "name": "New Name", "email": "new@example.com" }`
-   **Change Password**: `PUT /api/auth/password`
    -   Body: `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`

### 1.5. Password Recovery

-   **Forgot Password**: `POST /api/auth/forgot-password` (Sends reset link via email).
-   **Reset Password**: `POST /api/auth/reset-password` (Uses token from email).

---

## 2. Product Management

Products are the core of the system. The listing logic has been enhanced for better UX.

### 2.1. List Products 🚀 **(Redis Caching)**

-   **Endpoint**: `GET /api/products`
-   **Behavior**:
    -   **Caching**: Results are cached (`products:list`) for performance.
    -   **Sorting**: **In-Stock** items appear first. **Out-of-Stock** (0 quantity) items are automatically moved to the **bottom** of the list.
    -   **Order**: Secondary sort by creation date (newest first).

### 2.2. Create Product

-   **Endpoint**: `POST /api/products`
-   **Body**:
    ```json
    {
        "sku": "PROD-001",
        "name": "Premium Coffee",
        "cost": 5000,
        "price": 15000
    }
    ```

### 2.3. Upload Image

Images are stored in MinIO/S3.

-   **Endpoint**: `POST /api/products/{id}/image`
-   **Body**: `Multipart/Form-Data` with key `image`.

---

## 3. Inventory Management

Strict control over stock movement.

### 3.1. Purchasing (Stock In)

Increases product stock.

-   **Endpoint**: `POST /api/purchases`
-   **Body**:
    ```json
    {
        "supplier_id": 1,
        "items": [{ "product_id": 1, "quantity": 100 }]
    }
    ```

### 3.2. Stock Opname (Audit)

Corrects stock levels based on physical count.

-   **Endpoint**: `POST /api/inventory/opname`
-   **Body**:
    ```json
    {
        "items": [
            { "product_id": 1, "actual_stock": 98, "reason": "Damaged goods" }
        ]
    }
    ```

---

## 4. POS Transactions (Sales)

Processes sales and decrements stock atomically.

### 4.1. Create Transaction 🚀 **(Redis Distributed Lock)**

-   **Endpoint**: `POST /api/pos`
-   **Body**:
    ```json
    {
        "items": [{ "product_id": 1, "quantity": 2 }],
        "payment_method": "cash",
        "amount_paid": 50000
    }
    ```
-   **Logic**:
    -   **Lock**: Acquires `pos_transaction_processing` lock to ensure atomic stock updates.
    -   Checks stock availability.
    -   Calculates total price and change.
    -   Atomically decrements stock.
    -   Generates invoice number.

---

## 5. Verification & Testing

All features are verified with automated tests.

### Running Tests

```bash
php artisan test
```

### Recent Verifications

-   **Auth**: Verified Profile, Password Change, and Reset flows (`AuthFeaturesTest`).
-   **Products**: Verified sorting logic (Out-of-stock last) via reproduction script and manual review.
-   **Inventory/POS**: Verified atomic stock operations via `StockManagementTest` and `PosTransactionTest`.
-   **Performance**: Verified Redis caching for product scanning (`RedisFeaturesTest`).
-   **Safety**: Verified distributed locking ensures stock accuracy (`RedisFeaturesTest`).

---

## 6. Performance & Security (Redis)

The system integrates Redis to solve critical performance and safety challenges.

### 6.1. POS Transaction Locking

**Problem**: Rapid concurrent sales of the same item can cause race conditions (underselling stock).
**Solution**: Redis Distributed Locks (`Cache::lock`) serialize transactions at the application level, acting as a first line of defense before DB row locking.

### 6.2. High-Speed Product Scanning

**Problem**: Barcode scanners hit the API hundreds of times per minute. Beating the database for every scan is inefficient.
**Solution**: `GET /api/products/scan/{sku}` uses Redis Cache (`product:scan:{sku}`) to return product metadata instantly.

### 6.3. Rate Limiting

**Problem**: Brute force attacks on Login and OTP endpoints.
**Solution**: Redis-backed Rate Limiter tracks attempts by IP and User ID, enforcing strict backoff policies (e.g., 5 attempts/minute).
