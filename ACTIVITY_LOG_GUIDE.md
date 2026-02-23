# 📋 Activity Log System Guide

This Activity Log system provides an automatic audit trail to track all CRUD activities and important actions within the application.

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         APPLICATION                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────────┐   │
│  │    Model     │    │  Controller  │    │     Service      │   │
│  │ (with Trait) │    │              │    │                  │   │
│  └──────┬───────┘    └──────┬───────┘    └────────┬─────────┘   │
│         │                   │                     │              │
│         │ created/updated   │ login/logout        │ manual log   │
│         │ /deleted events   │                     │              │
│         ▼                   ▼                     ▼              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    LogsActivity Trait                     │   │
│  │                  / ActivityLogService                     │   │
│  └──────────────────────────┬───────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                  activity_logs table                      │   │
│  │  • user_id      • action       • description              │   │
│  │  • subject_type • subject_id   • old_values              │   │
│  │  • new_values   • properties   • ip_address              │   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 Components

| File                                                 | Description                       |
| ---------------------------------------------------- | --------------------------------- |
| `app/Models/ActivityLog.php`                         | Model for storing logs            |
| `app/Traits/LogsActivity.php`                        | Trait for automatic CRUD logging  |
| `app/Services/ActivityLogService.php`                | Service for manual log operations |
| `app/Http/Controllers/ActivityLogController.php`     | Controller for the log view       |
| `resources/views/pages/activity-log/index.blade.php` | UI for viewing logs               |

---

## 🚀 How to Use

### 1. Automatic CRUD Logging (Using Trait)

Add the `LogsActivity` trait to the Model you want to track:

```php
<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'price', 'description'];

    // OPTIONAL: Define fields to be logged (default: all)
    protected static array $logAttributes = ['name', 'price'];

    // OPTIONAL: Fields NOT to be logged (default: password, remember_token, timestamps)
    protected static array $logExcept = ['internal_notes'];
}
```

**Result:** Every time a `Product` is created, updated, or deleted, the system will automatically record:

-   Who performed the action
-   When it was performed
-   Data before and after the change
-   IP Address and User Agent

### 2. Manual Logging (Non-CRUD)

For actions not covered by the CRUD trait, use `ActivityLogService`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;

class ReportController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function export()
    {
        // ... export process ...

        // Log export activity
        $this->activityLogService->log(
            action: 'exported',
            description: 'User exported sales report',
            subject: null, // or specific Model
            properties: [
                'format' => 'xlsx',
                'date_range' => '2024-01-01 - 2024-12-31'
            ]
        );

        return $file;
    }
}
```

### 3. Custom Logging from Model Instance

If the Model already uses the trait, you can also log custom activities:

```php
$product = Product::find(1);

$product->logCustomActivity(
    action: 'viewed',
    description: 'User viewed product details',
    properties: ['referrer' => request()->headers->get('referer')]
);
```

---

## 🔍 Accessing Logs

### Via Web UI

Access the page: `/activity-log`

Features:

-   Filter by action, user, date
-   View data change details (before/after)
-   Pagination

### Via API

```javascript
// Get paginated logs
fetch("/activity-log/data?per_page=15&action=created")
    .then((res) => res.json())
    .then((data) => console.log(data));

// Get statistics
fetch("/activity-log/statistics?days=30")
    .then((res) => res.json())
    .then((data) => console.log(data));
```

### Via Code (Query)

```php
use App\Models\ActivityLog;
use App\Services\ActivityLogService;

// Direct query
$recentLogs = ActivityLog::with('user')
    ->action('created')
    ->forModel(Product::class)
    ->betweenDates('2024-01-01', '2024-12-31')
    ->latest()
    ->take(10)
    ->get();

// Via Service
$service = app(ActivityLogService::class);

// Get logs for specific user
$userLogs = $service->getByUser($userId, limit: 10);

// Get logs for specific subject
$productLogs = $service->getBySubject($product, limit: 10);

// Get statistics
$stats = $service->getStatistics(days: 30);
```

---

## 🔧 Configuration

### Changing Default Ignored Fields

Edit `app/Traits/LogsActivity.php`:

```php
protected static function getLogExceptAttributes(): array
{
    $defaults = ['password', 'remember_token', 'updated_at', 'created_at'];
    // Add global fields that should not be logged

    if (property_exists(static::class, 'logExcept')) {
        return array_merge($defaults, static::$logExcept);
    }

    return $defaults;
}
```

### Custom Description

Override the method in the Model:

```php
class Product extends Model
{
    use LogsActivity;

    protected static function getLogDescription(string $action, Model $model): string
    {
        return match ($action) {
            'created' => "New product '{$model->name}' added with price " . number_format($model->price),
            'updated' => "Product '{$model->name}' updated",
            'deleted' => "Product '{$model->name}' removed from the system",
            default => parent::getLogDescription($action, $model),
        };
    }
}
```

---

## 🧹 Maintenance

### Cleanup Old Logs

To clean up old logs (e.g., > 90 days):

```php
// Via Service
$service = app(ActivityLogService::class);
$deletedCount = $service->cleanup(daysToKeep: 90);

// Or create a scheduled command
// app/Console/Kernel.php
$schedule->call(function () {
    app(ActivityLogService::class)->cleanup(90);
})->daily();
```

---

## 📊 Action Types

| Action     | Description      | Trigger                  |
| ---------- | ---------------- | ------------------------ |
| `created`  | New data created | Model::created event     |
| `updated`  | Data updated     | Model::updated event     |
| `deleted`  | Data deleted     | Model::deleted event     |
| `login`    | User logged in   | AuthController::login()  |
| `logout`   | User logged out  | AuthController::logout() |
| `viewed`   | Data viewed      | Manual log               |
| `exported` | Data exported    | Manual log               |
| `imported` | Data imported    | Manual log               |
| (custom)   | Custom action    | Manual log               |

---

## ✅ Best Practices

1. **Don't log sensitive data** - Use `$logExcept` for fields like password, tokens, etc.
2. **Limit log attributes** - Use `$logAttributes` if you only need to track specific fields.
3. **Regular Cleanup** - Set a scheduled task to delete old logs so your database doesn't bloat.
4. **Index columns** - Ensure columns that are often filtered are indexed (already in migration).
