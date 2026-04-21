---
name: SoficoCoreConsistency
description: Enforces the Repository-Service-Controller pattern and UI standards for the Sofikopi project.
---

# Sofikopi Core Consistency Skill

This skill provides mandatory instructions for maintaining the architectural integrity of the Sofikopi Web Application. Use this skill whenever creating new modules or refactoring existing ones.

## 1. Directory Structure Standards
All new modules must follow this structure:
- **Model**: `app/Models/`
- **Migration**: `database/migrations/`
- **Request**: `app/Http/Requests/`
- **Interface**: `app/Interfaces/Repositories/` (e.g., `ExampleRepositoryInterface.php`)
- **Repository**: `app/Repositories/` (e.g., `ExampleRepository.php`)
- **Service**: `app/Services/` (e.g., `ExampleService.php`)
- **Controller**: `app/Http/Controllers/`
- **Views**: `resources/views/pages/[module]/`
- **Import/Export**: `app/Imports/` and `app/Exports/`

## 2. Coding Patterns

### Backend
1. **Repository Pattern**:
   - Interfaces must extend `BaseRepositoryInterface`.
   - Repositories must extend `BaseRepository` and implement their specific interface.
2. **Service Layer**:
   - All database writing logic (store/update/delete) and complex calculations must live in the Service.
   - Use `DB::transaction` inside Service methods for data integrity.
3. **AppServiceProvider Registration**:
   - Every new Repository Interface must be bound to its implementation in the `register` method of `app/Providers/AppServiceProvider.php`.

### Frontend (Blade & JS)
1. **Table Responsiveness**:
   - Always implement the "down arrow" (toggle) for mobile.
   - Configure DataTables with `responsive: { details: { ... } }`.
   - Add a `control` column at the beginning of the table.
2. **AJAX Handling**:
   - Use the `ResponseHelper` class for all controller returns.
   - On the frontend, use `window.AlertHandler` to display success/error messages from the API.
3. **Form Components**:
   - Use `Select2` for entity search dropdowns.
   - Wrap Select2 in `<div class="position-relative"></div>` to ensure proper dropdown positioning within modals.

## 3. Workflow Example (New Module)
1. Create Migration & Model.
2. Create FormRequest for validation.
3. Define Repository Interface extending `BaseRepositoryInterface`.
4. Implement Repository extending `BaseRepository`.
5. Create Service extending `BaseService`, injecting the Repository Interface.
6. Register binding in `AppServiceProvider`.
7. Create Controller injecting the Service.
8. Create View following the established `layoutMaster` and DataTables patterns.
9. Link the route in `web.php`.
