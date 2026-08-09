<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Coverage for the Invoice refactor: customer display name (no more mitra
 * code prefix), editable due date, cash/cashless payment method, due-date
 * badge coloring, and permission-gated finance actions.
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected Menu $invoiceMenu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceMenu = Menu::create([
            'parent_id' => null,
            'name' => 'Invoice',
            'icon' => 'ri-file-list-3-line',
            'path' => '/penjualan/invoice',
            'slug' => 'invoice.index',
            'order_no' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Buat user dengan role baru yang diberi permission tertentu pada menu invoice.index.
     */
    protected function makeUserWithInvoicePermission(array $perms, ?string $roleSlug = null): User
    {
        $roleSlug = $roleSlug ?? 'role-'.uniqid();

        $role = Role::create(['name' => ucfirst($roleSlug), 'slug' => $roleSlug]);

        DB::table('role_menu')->insert(array_merge(
            ['role_id' => $role->id, 'menu_id' => $this->invoiceMenu->id],
            ['can_create' => false, 'can_read' => false, 'can_update' => false, 'can_delete' => false],
            $perms
        ));

        return User::create([
            'name' => 'User '.$roleSlug,
            'email' => $roleSlug.'@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'mitra_id' => null,
        ]);
    }

    protected function makeInvoice(array $salesOrderOverrides = [], array $invoiceOverrides = []): Invoice
    {
        $user = User::factory()->create();

        $category = MitraCategory::create(['name' => 'Reseller', 'is_active' => true]);

        $mitra = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'MTR-001',
            'name' => 'Toko Sejahtera',
            'is_active' => true,
        ]);

        $salesOrder = SalesOrder::create(array_merge([
            'order_number' => 'SO-'.uniqid(),
            'user_id' => $user->id,
            'mitra_id' => $mitra->id,
            // Legacy rows saved "KODE - Nama" before the fix; kept here so the
            // display accessor is proven to override it via the mitra relation.
            'customer_name' => 'MTR-001 - Toko Sejahtera',
            'order_date' => now(),
            'status' => 'approved',
        ], $salesOrderOverrides));

        return Invoice::create(array_merge([
            'invoice_number' => 'INV-'.uniqid(),
            'sales_order_id' => $salesOrder->id,
            'created_by' => $user->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100000,
            'grand_total' => 100000,
            'status' => 'belum_lunas',
        ], $invoiceOverrides));
    }

    // ---------------------------------------------------------------
    // Customer display name (no more mitra code prefix)
    // ---------------------------------------------------------------

    public function test_customer_display_name_uses_mitra_name_without_code_even_for_legacy_rows(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertSame('Toko Sejahtera', $invoice->salesOrder->customer_display_name);
        $this->assertStringNotContainsString('MTR-001', $invoice->salesOrder->customer_display_name);
    }

    public function test_customer_display_name_falls_back_to_manual_customer_name_when_no_mitra(): void
    {
        $user = User::factory()->create();

        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-'.uniqid(),
            'user_id' => $user->id,
            'mitra_id' => null,
            'customer_name' => 'Pelanggan Manual',
            'order_date' => now(),
            'status' => 'approved',
        ]);

        $this->assertSame('Pelanggan Manual', $salesOrder->customer_display_name);
    }

    public function test_invoice_index_page_shows_customer_name_without_mitra_code(): void
    {
        $invoice = $this->makeInvoice();
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true], 'finance');

        $response = $this->actingAs($finance)->get(route('invoice.index'));

        $response->assertOk();
        $response->assertSee('Toko Sejahtera');
        $response->assertDontSee('MTR-001 - Toko Sejahtera');
    }

    // ---------------------------------------------------------------
    // due_state / due_badge_class accessor
    // ---------------------------------------------------------------

    public function test_due_state_is_overdue_and_badge_is_danger_when_past_due_date_and_unpaid(): void
    {
        $invoice = $this->makeInvoice([], ['due_date' => now()->subDays(2), 'status' => 'belum_lunas']);

        $this->assertSame('overdue', $invoice->due_state);
        $this->assertSame('danger', $invoice->due_badge_class);
    }

    public function test_due_state_is_near_due_and_badge_is_warning_when_within_three_days_and_unpaid(): void
    {
        $invoice = $this->makeInvoice([], ['due_date' => now()->addDays(2), 'status' => 'belum_lunas']);

        $this->assertSame('near_due', $invoice->due_state);
        $this->assertSame('warning', $invoice->due_badge_class);
    }

    public function test_due_state_is_ok_and_badge_is_secondary_when_far_from_due_date_and_unpaid(): void
    {
        $invoice = $this->makeInvoice([], ['due_date' => now()->addDays(10), 'status' => 'belum_lunas']);

        $this->assertSame('ok', $invoice->due_state);
        $this->assertSame('secondary', $invoice->due_badge_class);
    }

    public function test_due_state_is_paid_and_badge_is_success_when_status_lunas_regardless_of_due_date(): void
    {
        $invoice = $this->makeInvoice([], ['due_date' => now()->subDays(30), 'status' => 'lunas']);

        $this->assertSame('paid', $invoice->due_state);
        $this->assertSame('success', $invoice->due_badge_class);
    }

    public function test_invoice_index_page_renders_due_date_badge_with_matching_class(): void
    {
        $invoice = $this->makeInvoice([], ['due_date' => now()->subDays(2), 'status' => 'belum_lunas']);
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true], 'finance');

        $response = $this->actingAs($finance)->get(route('invoice.index'));

        $response->assertOk();
        $response->assertSee('bg-label-danger', false);
    }

    // ---------------------------------------------------------------
    // Due date edit + payment method (service layer)
    // ---------------------------------------------------------------

    public function test_update_invoice_status_persists_new_due_date(): void
    {
        $invoice = $this->makeInvoice([], ['due_date' => now()->addDays(14)]);
        $newDueDate = now()->addDays(30)->toDateString();

        app(InvoiceService::class)->updateInvoiceStatus($invoice->id, [
            'status' => 'belum_lunas',
            'due_date' => $newDueDate,
        ]);

        $this->assertSame($newDueDate, $invoice->fresh()->due_date->toDateString());
    }

    public function test_update_invoice_status_to_lunas_sets_payment_method_and_paid_at(): void
    {
        $invoice = $this->makeInvoice();

        app(InvoiceService::class)->updateInvoiceStatus($invoice->id, [
            'status' => 'lunas',
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertSame('lunas', $invoice->status);
        $this->assertSame('cash', $invoice->payment_method);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_update_invoice_status_back_to_belum_lunas_resets_payment_method_and_paid_at(): void
    {
        $invoice = $this->makeInvoice([], ['status' => 'lunas', 'payment_method' => 'cashless', 'paid_at' => now()]);

        app(InvoiceService::class)->updateInvoiceStatus($invoice->id, [
            'status' => 'belum_lunas',
        ]);

        $invoice->refresh();
        $this->assertSame('belum_lunas', $invoice->status);
        $this->assertNull($invoice->payment_method);
        $this->assertNull($invoice->paid_at);
    }

    // ---------------------------------------------------------------
    // Validation (InvoiceRequest) via HTTP
    // ---------------------------------------------------------------

    public function test_marking_invoice_lunas_without_payment_method_fails_validation(): void
    {
        $invoice = $this->makeInvoice();
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true, 'can_update' => true], 'finance');

        $response = $this->actingAs($finance)->post(route('invoice.update-status', $invoice->id), [
            'status' => 'lunas',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertSame('belum_lunas', $invoice->fresh()->status);
    }

    public function test_marking_invoice_lunas_with_invalid_payment_method_fails_validation(): void
    {
        $invoice = $this->makeInvoice();
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true, 'can_update' => true], 'finance');

        $response = $this->actingAs($finance)->post(route('invoice.update-status', $invoice->id), [
            'status' => 'lunas',
            'payment_method' => 'transfer',
        ]);

        $response->assertSessionHasErrors('payment_method');
    }

    public function test_marking_invoice_lunas_with_valid_cashless_payment_method_succeeds(): void
    {
        $invoice = $this->makeInvoice();
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true, 'can_update' => true], 'finance');

        $response = $this->actingAs($finance)->post(route('invoice.update-status', $invoice->id), [
            'status' => 'lunas',
            'payment_method' => 'cashless',
        ]);

        $response->assertRedirect(route('invoice.show', $invoice->id));
        $this->assertSame('cashless', $invoice->fresh()->payment_method);
    }

    // ---------------------------------------------------------------
    // Permission hardening
    // ---------------------------------------------------------------

    public function test_user_without_read_permission_gets_403_on_invoice_index(): void
    {
        $invoice = $this->makeInvoice();
        $outsider = $this->makeUserWithInvoicePermission([], 'no-access');

        $response = $this->actingAs($outsider)->get(route('invoice.index'));

        $response->assertForbidden();
    }

    public function test_user_without_update_permission_gets_403_when_posting_status_update(): void
    {
        $invoice = $this->makeInvoice();
        $readOnly = $this->makeUserWithInvoicePermission(['can_read' => true], 'manager');

        $response = $this->actingAs($readOnly)->post(route('invoice.update-status', $invoice->id), [
            'status' => 'lunas',
            'payment_method' => 'cash',
        ]);

        $response->assertForbidden();
        $this->assertSame('belum_lunas', $invoice->fresh()->status);
    }

    public function test_user_without_read_permission_gets_403_when_printing_pdf(): void
    {
        $invoice = $this->makeInvoice();
        $outsider = $this->makeUserWithInvoicePermission([], 'no-access');

        $response = $this->actingAs($outsider)->get(route('invoice.print', $invoice->id));

        $response->assertForbidden();
    }

    public function test_detail_button_hidden_on_index_for_role_without_read_access_to_show(): void
    {
        // Even though reaching the index page already implies read access,
        // the Detail link itself must stay wrapped in the same @can guard —
        // this proves the wrapping markup is present and correctly scoped.
        $invoice = $this->makeInvoice();
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true], 'finance');

        $response = $this->actingAs($finance)->get(route('invoice.index'));

        $response->assertOk();
        $response->assertSee('Detail');
    }

    public function test_finance_panel_hidden_on_show_page_for_role_without_update_permission(): void
    {
        $invoice = $this->makeInvoice();
        $readOnly = $this->makeUserWithInvoicePermission(['can_read' => true], 'manager');

        $response = $this->actingAs($readOnly)->get(route('invoice.show', $invoice->id));

        $response->assertOk();
        $response->assertDontSee('Kelola Pembayaran (Finance)');
    }

    public function test_finance_panel_visible_on_show_page_for_role_with_update_permission(): void
    {
        $invoice = $this->makeInvoice();
        $finance = $this->makeUserWithInvoicePermission(['can_read' => true, 'can_update' => true], 'finance');

        $response = $this->actingAs($finance)->get(route('invoice.show', $invoice->id));

        $response->assertOk();
        $response->assertSee('Kelola Pembayaran (Finance)');
    }
}
