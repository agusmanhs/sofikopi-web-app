# Changelog - 2026-01-19

## Fixes Implemented

### 1. Form Izin - Optional Fields ✅

**Files Modified:**

-   `resources/views/pages/izin/create.blade.php`

**Changes:**

-   Made `alasan` (reason) field optional with clear label indication
-   Made `surat pendukung` (supporting document) field optional with clear label indication
-   Removed JavaScript logic that was making file_surat required based on jenis_izin
-   Updated UI labels to show "(Opsional)" text
-   Backend validation already supports optional fields (nullable)

### 2. Riwayat Absensi UI - Responsiveness & Template Colors ✅

**Files Modified:**

-   `resources/views/pages/absensi/history.blade.php`

**Changes:**

-   **Improved Responsiveness:**
    -   Changed breakpoint from `d-md-none/d-md-block` to `d-lg-none/d-lg-block`
    -   Now mini tablets (768px-991px) will see the card view instead of cramped table
    -   Desktop and large tablets (992px+) see the table view
-   **Template Color Matching:**
    -   Changed stats cards from solid colors (`bg-success`, `bg-warning`, `bg-info`) to template label colors (`bg-label-success`, `bg-label-warning`, `bg-label-info`)
    -   Updated text colors to match (`text-success`, `text-warning`, `text-info`)
    -   Added responsive grid classes (`col-sm-6`, `col-sm-12`) for better mobile layout
-   **Card Layout Improvements:**
    -   Enhanced card styling with `shadow-sm` instead of `shadow-none border`
    -   Removed `bg-light` background from card container
    -   Added icons to card labels (login/logout/location/file icons)
    -   Improved spacing with `g-3` gap and better padding
    -   Added `fw-semibold` to time displays for better readability
    -   Better visual hierarchy with border-bottom separators

### 3. Timezone Configuration ✅

**Files Checked:**

-   `config/app.php`

**Status:**

-   Already configured to `'timezone' => 'Asia/Makassar'` (UTC+8)
-   No changes needed ✓

### 4. Active Menu - Fixed Double Active Issue ✅

**Files Modified:**

-   `resources/views/layouts/sections/menu/verticalMenu.blade.php`
-   `resources/views/layouts/sections/menu/submenu.blade.php`

**Changes:**

-   **Improved Route Matching Logic:**
    -   Implemented priority-based matching (exact route → route prefix → path)
    -   Changed from `request()->is()` to `str_starts_with()` for more precise path matching
    -   Added `request()->path()` for direct path comparison
    -   Fixed wildcard matching to prevent multiple menus being active simultaneously
-   **Key Improvements:**
    -   Exact route name match has highest priority
    -   Route prefix matching for `.index` routes (e.g., `izin.index` matches `izin.create`)
    -   Path matching only as fallback, with proper segment checking
    -   Parent menus only become active if children are active AND parent itself is not active
    -   Consistent logic between parent menu and submenu files

## Testing Recommendations

1. **Form Izin:**

    - Test submitting izin without alasan
    - Test submitting izin without surat pendukung
    - Verify both fields are truly optional in validation

2. **Riwayat UI:**

    - Test on mobile (< 768px) - should see cards
    - Test on mini tablet (768px - 991px) - should see cards
    - Test on desktop (> 992px) - should see table
    - Verify card colors match template theme

3. **Active Menu:**

    - Navigate between different menu items
    - Check that only ONE menu item is active at a time
    - Test with parent/child menu relationships
    - Verify submenu highlighting works correctly

4. **Form Izin:**

    - Test submitting izin without alasan
    - Test submitting izin without surat pendukung
    - Verify both fields are truly optional in validation

5. **Riwayat UI:**

    - Test on mobile (< 768px) - should see cards
    - Test on mini tablet (768px - 991px) - should see cards
    - Test on desktop (> 992px) - should see table
    - Verify card colors match template theme

6. **Active Menu:**

    - Navigate between different menu items
    - Check that only ONE menu item is active at a time
    - Test with parent/child menu relationships
    - Verify submenu highlighting works correctly

7. **Timezone:**
    - Check that timestamps display in Makassar time (UTC+8)
    - Verify date/time displays are correct

## Notes

-   All changes maintain backward compatibility
-   No database migrations required
-   No composer dependencies added
-   Template color classes (`bg-label-*`) are part of the existing template

---

# Update - 2026-01-19 (20:37)

## Dashboard Role-Based Content ✅

**Files Modified:**

-   `app/Http/Controllers/DashboardController.php`
-   `app/Http/Controllers/AuthController.php`

**Changes:**

-   **Dashboard menampilkan konten berbeda berdasarkan role:**
    -   **Superadmin & Admin**: Melihat dashboard admin dengan statistik lengkap
    -   **User biasa (Pegawai)**: Langsung melihat halaman absensi
-   **Implementation:**
    -   `DashboardController` sekarang inject `AbsensiService` dan `PegawaiService`
    -   Method `index()` cek role user dengan `$user->role?->slug`
    -   Jika role `super-admin` atau `admin` → tampilkan `pages.dashboard.dashboard`
    -   Jika role lainnya → tampilkan `pages.absensi.index` dengan data absensi
    -   `AuthController` disederhanakan, semua user redirect ke `/` (dashboard)
-   **Benefits:**
    -   ✅ **Single entry point** - semua user ke dashboard, tapi konten berbeda
    -   ✅ **Konsisten dengan menu** - ketika pegawai klik "Dashboard" di menu, langsung lihat absensi
    -   ✅ **Menghindari kebingungan** - pegawai tidak perlu lihat dashboard admin yang tidak relevan
    -   ✅ **Lebih maintainable** - logika role-based content terpusat di DashboardController
    -   ✅ **UX lebih baik** - pegawai langsung ke fitur utama yang mereka butuhkan

**Testing:**

-   Login dengan akun **superadmin** → dashboard menampilkan statistik admin
-   Login dengan akun **admin** → dashboard menampilkan statistik admin
-   Login dengan akun **pegawai** → dashboard menampilkan halaman absensi
-   Klik menu "Dashboard" sebagai pegawai → tetap tampil halaman absensi
-   Klik menu "Dashboard" sebagai admin → tampil dashboard admin
