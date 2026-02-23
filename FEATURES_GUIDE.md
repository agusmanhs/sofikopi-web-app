# 🚀 Base Laravel Feature Guide

This documentation explains the main features available in this template and how to use them to speed up your application development.

---

## 📚 Feature List

1. [Service Repository Pattern](#1-service-repository-pattern)
2. [Activity Log System (Audit Trail)](#2-activity-log-system)
3. [File Upload Manager](#3-file-upload-manager)
4. [Role & Permission Management](#4-role--permission-management)
5. [Standardized API Response](#5-standardized-api-response)
6. [Dynamic Menu System](#6-dynamic-menu-system)
7. [Custom Artisan Generator](#7-custom-artisan-generator)
8. [API Documentation (Swagger)](#8-api-documentation-swagger)

---

## 1. Service Repository Pattern

Separation of business logic, data access, and presentation layer for cleaner and more testable code.

-   **Repository**: Contains only DB queries (Eloquent).
-   **Service**: Contains business logic and application rules.
-   **Controller**: Handles only request/response (thin controller).

**How to Use:**
Use the artisan command to create the boilerplate all at once:

```bash
php artisan make:feature ModuleName
```

---

## 2. Activity Log System

Automatic audit trail to monitor who changed what and when.

-   **Auto-Tracking**: Add the `LogsActivity` trait to your Model.
-   **Manual Logging**: Use `ActivityLogService` in your Controller.
-   **Audit UI**: Access at `/activity-log` to view data change history (Before vs After).

**Example in Model:**

```php
use App\Traits\LogsActivity;

class Product extends Model {
    use LogsActivity;
}
```

---

## 3. File Upload Manager

Centralized file management with support for image optimization.

-   **Integrated Storage**: Easily switch from Local to S3/Cloudinary without changing business logic code.
-   **Image Processing**: Auto-resize, crop, and compress using Intervention Image.
-   **DB Tracking**: Every uploaded file is recorded in the `media` table.

**Example Usage:**

```php
use App\Services\FileUploadService;

public function store(Request $request, FileUploadService $fileService) {
    $media = $fileService->upload($request->file('avatar'), 'avatars', [
        'width' => 300,
        'height' => 300,
        'crop' => true
    ]);

    $user->update(['avatar_id' => $media->id]);
    // Access URL: $media->url
}
```

---

## 4. Role & Permission Management

A very granular Role-Based Access Control (RBAC) system.

-   **Granular Permission**: Set permissions per menu for actions: `Create`, `Read`, `Update`, `Delete`.
-   **Middleware**: Use `check.permission:menu-slug` in routes.
-   **Blade Directive**: Use `@can('access', ['menu-slug', 'create'])`.

**Example in Routes:**

```php
Route::resource('product', ProductController::class)
      ->middleware('check.permission:product.index');
```

---

## 5. Standardized API Response

Standardized JSON response format to facilitate integration with Frontend (Vue/React/Mobile).

**Example in Controller:**

```php
use App\Helpers\ResponseHelper;

return ResponseHelper::success($data, 'Data retrieved successfully');
return ResponseHelper::error('Failed to process data', 400);
```

---

## 6. Dynamic Menu System

Sidebar navigation menu automatically appears based on the logged-in user's access rights.

-   Configuration via database or JSON file: `resources/menu/verticalMenu.json`.
-   Automatically hides the menu if the user does not have `Read` permission.

---

## 7. Custom Artisan Generator

Speed up the creation of new features without manual file copying.

```bash
php artisan make:feature Product
```

**This command will create:**

-   `app/Interfaces/Repositories/ProductRepositoryInterface.php`
-   `app/Repositories/ProductRepository.php`
-   `app/Services/ProductService.php`
-   `app/Http/Controllers/ProductController.php`
-   `app/Http/Requests/ProductRequest.php`

---

## 8. API Documentation (Swagger)

Automatically generate interactive API documentation to facilitate collaboration with Frontend or Mobile teams.

-   **Endpoint**: Access at `/api/documentation`.
-   **Generator**: Use artisan command to update documentation after adding @OA annotations.
-   **Sanctum Integration**: Supports Bearer Token authentication (Sanctum).

**How to Update Documentation:**

```bash
php artisan l5-swagger:generate
```

**Example Annotation in Controller:**

```php
/**
 * @OA\Get(
 *     path="/api/users",
 *     tags={"Users"},
 *     summary="Get users list",
 *     @OA\Response(response=200, description="OK")
 * )
 */
```

---

## 🛠️ Main Tech Stack

-   **Laravel 12.x**
-   **L5-Swagger** (OpenAPI Documentation)
-   **Laravel Sanctum** (API Auth)
-   **Bootstrap 5 (Sneat Template)**
-   **Intervention Image v3** (Image Processing)
-   **SweetAlert2 & Toastr** (Alerts)
-   **DataTables** (Table list)
-   **Vite** (Assets Bundling)

---

_Base Laravel - Created by Ooka Pratama_
