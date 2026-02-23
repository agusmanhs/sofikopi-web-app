# Global Notification & Alert System Guide

This system provides a standardized way to display notifications using **SweetAlert2** and **Toastr**. It automatically handles responses from both Server-Side (Redirect/Flash Message) and Client-Side (AJAX/Axios) interactions.

---

## 1. Controller Usage (PHP)

### A. For Standard Requests (Form Submit & Redirect)

Use standard Laravel `with()`. The system will detect the `success` or `error` session and display the corresponding alert automatically.

```php
// Success (Displays SweetAlert Success)
return redirect()->route('users.index')->with('success', 'User data saved successfully.');

// Failure (Displays SweetAlert Error)
return redirect()->back()->with('error', 'An error occurred while saving data.');

// Validation
// Laravel automatically sends errors to the session. The system captures them and displays error details.
$request->validate([...]);
```

### B. For AJAX / API Requests

Use `App\Helpers\ResponseHelper` to return a standardized JSON format. The frontend will automatically parse this response.

```php
use App\Helpers\ResponseHelper;

// Success
return ResponseHelper::success($data, 'Data deleted successfully');

// Failure (Custom Error)
return ResponseHelper::error('Failed to process data', 500);

// Failure (Manual Validation - rarely used if using FormRequest)
return ResponseHelper::validationError($errors);
```

---

## 2. Frontend Usage (Javascript)

The `AlertHandler` utility class is registered globally as `window.AlertHandler`.

### A. Handling Axios Responses (AJAX)

Simply pass the Axios response to `handle()`. This helper will automatically determine whether to display success or error (including validation lists).

```javascript
axios
    .post("/some-url", payload)
    .then((response) => {
        // Automatically displays Success SweetAlert
        window.AlertHandler.handle(response);

        // Optional: Reload page if successful
        if (response.data.success) {
            location.reload();
        }
    })
    .catch((error) => {
        // Automatically displays Error SweetAlert (including validation list if any)
        window.AlertHandler.handle(error.response);
    });
```

### B. Delete Confirmation (or Destructive Actions)

Use the `confirm()` method to display a standard confirmation dialog.

```javascript
window.AlertHandler.confirm(
    'Are you sure?',                // Title
    'Deleted data cannot be recovered!', // Message
    'Yes, Delete!',                 // Confirm Button Text
    () => {                         // Callback if confirmed
        // Perform delete action here (e.g., call AJAX)
        axios.delete(url)...
    }
);
```

### C. Manual Alerts

You can trigger alerts at any time without a server request.

```javascript
// Show Success SweetAlert
window.AlertHandler.showSuccess("Operation successful!");

// Show Success Toastr (small notification in the corner)
// Second parameter 'true' enables Toast mode
window.AlertHandler.showSuccess("Data saved", true);

// Show Error
window.AlertHandler.showError("A fatal error has occurred");
```

---

## 3. Standard Structure

### Response Helper (`App\Helpers\ResponseHelper.php`)

Ensure all API JSON responses use this helper for consistent formatting:

```json
{
    "success": true,
    "message": "Success message",
    "data": { ... }
}
```

### Alert Handler (`resources/js/utils/alert-handler.js`)

This file manages the basic configuration of SweetAlert and Toastr. Edit this file to change button colors, animation duration, or toast position.
