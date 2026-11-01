# Vendor Opening Balance & Directory Alignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement full Vendor Opening Balance capability and align the Vendor Directory table, forms, profile, and PDF report to be identical to Customer Directory.

**Architecture:** 
- The `vendors` table already contains the decimal `opening_balance` column and `Vendor` model already supports `opening_balance` in `$fillable` and its financial calculation helper `calculateFinancials()`.
- Add `opening_due` input to Vendor Create and Edit views with validation in `VendorController`.
- Update `VendorController::index` to compute aggregated purchase dues and map financial metrics (`opening_due`, `purchases_due`, `total_due`, `advance_balance`) matching `CustomerController::index`.
- Align `vendor.index` table columns (`#`, `Vendor Name`, `Phone & Email`, `Address`, `Opening Due`, `Purchases Due`, `Account Balance`, `Status`, `Action`) with 3-dot dropdowns, tooltips, and identical styling to `customer.index`.
- Update `vendor.show` financial cards to display an `Opening Due Balance` card and total payable breakdown.
- Update `pdf.vendors` to include Opening Due and status badges matching `pdf.customers`.

**Tech Stack:** Laravel 11.57.0, PHP 8.3, Bootstrap 5 Blade Templates, mPDF 8.3, PHPUnit 10.5.

**Spec:** User request: "customer hs opening balance.. i want for vendor too.. make plan" -> "identical to Customer Directory".

## Global Constraints
- Strictly follow `.agents/AGENTS.md`:
  - No breadcrumbs in view headers.
  - 3-Dot Action Dropdowns in tables: `<a ... data-bs-popper-config='{"strategy":"fixed"}'>` and `overflow: visible !important`.
  - Modals placed at bottom of blade files outside table container.
  - Zero unauthorized migrations or seeders.
  - mPDF vector standard with letterhead pad support.

## Review Focus
1. When `opening_due` is submitted as empty or zero, `opening_balance` defaults to `0.00`.
2. When `opening_due` has a value (e.g., `25000.50`), `Vendor::create` / `Vendor::update` correctly stores it in `opening_balance`.
3. Negative values in `opening_due` are rejected by validation rule (`min:0`).
4. In `VendorController::index`, vendors with opening balance but 0 purchases correctly show in the directory with their opening due and total due.
5. In `VendorController::downloadPdf`, opening balance is rendered cleanly in the PDF table.

---

### Task 1: Add Opening Due Validation and Storage to VendorController

**Files:**
- Modify: `app/Http/Controllers/VendorController.php:33-56, 83-110`
- Test: `tests/Feature/VendorWorkflowTest.php`

**Interfaces:**
- Consumes: `Request` with `opening_due` / `opening_balance`
- Produces: `Vendor::$opening_balance` set and persisted

- [ ] **Step 1: Write the failing tests for vendor opening balance store and update**
Add tests in `tests/Feature/VendorWorkflowTest.php`:
```php
public function test_can_create_vendor_with_opening_due(): void
{
    $response = $this->actingAs($this->admin)->post(route('vendors.store'), [
        'name' => 'National Steel Mills',
        'phone' => '01700112233',
        'email' => 'national@mills.test',
        'address' => 'Chittagong Yard',
        'opening_due' => '35000.50',
        'status' => 'active',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('vendors.index'));

    $this->assertDatabaseHas('vendors', [
        'name' => 'National Steel Mills',
        'phone' => '01700112233',
        'opening_balance' => 35000.50,
    ]);
}

public function test_can_update_vendor_opening_due(): void
{
    $vendor = Vendor::create([
        'name' => 'Dhaka Re-Rolling',
        'phone' => '01800112233',
        'address' => 'Fatullah',
        'opening_balance' => 10000.00,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin)->put(route('vendors.update', $vendor->id), [
        'name' => 'Dhaka Re-Rolling Ltd',
        'phone' => '01800112233',
        'address' => 'Fatullah Industrial Area',
        'opening_due' => '45000.00',
        'status' => 'active',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('vendors.index'));

    $vendor->refresh();
    $this->assertEquals('Dhaka Re-Rolling Ltd', $vendor->name);
    $this->assertEquals(45000.00, (float)$vendor->opening_balance);
}

public function test_vendor_opening_due_rejects_negative_value(): void
{
    $response = $this->actingAs($this->admin)->post(route('vendors.store'), [
        'name' => 'Negative Supplier',
        'phone' => '01900112233',
        'address' => 'Dhaka',
        'opening_due' => '-500',
    ]);

    $response->assertSessionHasErrors('opening_due');
}
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_can_create_vendor_with_opening_due`
Expected: FAIL because `VendorController::store` does not yet accept or store `opening_due`.

- [ ] **Step 3: Implement `opening_due` in `VendorController::store` and `update`**
In `app/Http/Controllers/VendorController.php`:
Update `$rules`:
```php
'opening_balance' => 'nullable|numeric',
'opening_due'     => 'nullable|numeric|min:0',
```
In `store()`:
```php
if ($request->filled('opening_due')) {
    $vendor->opening_balance = abs((float)$request->opening_due);
} elseif ($request->filled('opening_balance')) {
    $vendor->opening_balance = (float)$request->opening_balance;
} else {
    $vendor->opening_balance = 0.00;
}
$vendor->status = $request->status ?? '1';
```
In `update()`:
```php
if ($request->filled('opening_due')) {
    $vendor->opening_balance = abs((float)$request->opening_due);
} elseif ($request->filled('opening_balance')) {
    $vendor->opening_balance = (float)$request->opening_balance;
} else {
    $vendor->opening_balance = 0.00;
}
if ($request->has('status')) {
    $vendor->status = $request->status;
}
```

- [ ] **Step 4: Run test to verify it passes**
Run: `php artisan test --filter=VendorWorkflowTest`
Expected: PASS

- [ ] **Step 5: Commit changes**
`git commit -m "feat: add opening due validation and persistence to VendorController"`

---

### Task 2: Add Opening Due Input Fields to Vendor Create and Edit Views

**Files:**
- Modify: `resources/views/frontend/pages/vendor/create.blade.php:80-97`
- Modify: `resources/views/frontend/pages/vendor/edit.blade.php:85-105`

**Interfaces:**
- Consumes: Form inputs `opening_due`, `$vData->opening_balance`
- Produces: HTML form input with `name="opening_due"`, ৳ addon, and helper text identical to customer create/edit views.

- [ ] **Step 1: Update `resources/views/frontend/pages/vendor/create.blade.php`**
Add the Opening Due Balance form field matching `customer/create.blade.php`:
```html
<!-- Opening Due Balance -->
<div class="col-md-6 col-lg-6">
    <label class="form-label fw-semibold text-secondary small mb-1">Opening Due Balance (৳)</label>
    <div class="input-group">
        <span class="input-group-text bg-light border-light-subtle text-muted">৳</span>
        <input type="number" step="0.01" min="0" class="form-control @error('opening_due') is-invalid @enderror" name="opening_due" id="opening_due" value="{{ old('opening_due', '0.00') }}" placeholder="0.00">
    </div>
    <small class="text-muted d-block mt-1" style="font-size: 11px;">Existing payable due owed to supplier before this system</small>
    @error('opening_due')
        <div class="text-danger fs-7 mt-1">{{ $message }}</div>
    @enderror
</div>
```

- [ ] **Step 2: Update `resources/views/frontend/pages/vendor/edit.blade.php`**
Add the Opening Due Balance form field pre-filled with formatted `$vData->opening_balance` matching `customer/edit.blade.php`:
```html
<!-- Opening Due Balance -->
<div class="col-md-6 col-lg-6">
    <label class="form-label fw-semibold text-secondary small mb-1">Opening Due Balance (৳)</label>
    <div class="input-group">
        <span class="input-group-text bg-light border-light-subtle text-muted">৳</span>
        @php
            $dueVal = ($vData->opening_balance > 0) ? number_format((float)$vData->opening_balance, 2, '.', '') : '0.00';
        @endphp
        <input type="number" step="0.01" min="0" class="form-control @error('opening_due') is-invalid @enderror" name="opening_due" id="opening_due" value="{{ old('opening_due', $dueVal) }}" placeholder="0.00">
    </div>
    <small class="text-muted d-block mt-1" style="font-size: 11px;">Existing payable due owed to supplier before this system</small>
    @error('opening_due')
        <div class="text-danger fs-7 mt-1">{{ $message }}</div>
    @enderror
</div>
```

- [ ] **Step 3: Verify rendered forms in automated test or web route test**
Add assertions in `VendorWorkflowTest`:
```php
public function test_vendor_create_and_edit_pages_render_opening_due_field(): void
{
    $response = $this->actingAs($this->admin)->get(route('vendors.create'));
    $response->assertStatus(200);
    $response->assertSee('name="opening_due"', false);

    $vendor = Vendor::create([
        'name' => 'View Test Vendor',
        'phone' => '01500112233',
        'address' => 'Dhaka',
        'opening_balance' => 12500.00,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin)->get(route('vendors.edit', $vendor->id));
    $response->assertStatus(200);
    $response->assertSee('name="opening_due"', false);
    $response->assertSee('12500.00');
}
```
Run: `php artisan test --filter=test_vendor_create_and_edit_pages_render_opening_due_field`
Expected: PASS

- [ ] **Step 4: Commit changes**
`git commit -m "feat: add opening due input fields to vendor create and edit views"`

---

### Task 3: Align Vendor Directory Controller & View Identical to Customer Directory

**Files:**
- Modify: `app/Http/Controllers/VendorController.php:16-21, 119-138`
- Modify: `resources/views/frontend/pages/vendor/index.blade.php`
- Modify: `resources/views/pdf/vendors.blade.php`

**Interfaces:**
- Consumes: `$vendors` with aggregated purchases and payments sums
- Produces:
  - Computed financial attributes: `opening_due`, `purchase_due`, `total_due`, `advance_balance`
  - Table headers matching Customer Directory:
    `#`, `Vendor Name`, `Phone & Email`, `Address`, `Opening Due`, `Purchases Due`, `Account Balance`, `Status`, `Action`
  - Fixed 3-dot dropdowns with `data-bs-popper-config='{"strategy":"fixed"}'`

- [ ] **Step 1: Write test for Vendor Directory financial calculations and columns**
In `tests/Feature/VendorWorkflowTest.php`:
```php
public function test_vendor_index_renders_identical_columns_and_opening_due(): void
{
    $warehouse = Warehouse::create(['name' => 'Depot 1', 'type' => 'warehouse']);
    $vendor = Vendor::create([
        'name' => 'Standard Steel Corp',
        'phone' => '01600112233',
        'email' => 'standard@steel.test',
        'address' => 'Sitakunda, Chittagong',
        'opening_balance' => 40000.00,
        'status' => 'active',
    ]);

    $lot = Lot::create([
        'vendor_id' => $vendor->id,
        'lot_number' => 'LOT-STD-01',
        'date' => now()->toDateString(),
        'status' => 'received',
    ]);

    Purchase::create([
        'vendor_id' => $vendor->id,
        'lot_id' => $lot->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 1,
        'unit_weight' => 1.0,
        'total_weight' => 1.0,
        'unit_price' => 60000.00,
        'total_price' => 60000.00,
        'payment' => 20000.00,
        'due' => 40000.00,
        'status' => 'partial',
    ]);

    $response = $this->actingAs($this->admin)->get(route('vendors.index'));
    $response->assertStatus(200);

    // Assert headers
    $response->assertSee('Opening Due');
    $response->assertSee('Purchases Due');
    $response->assertSee('Account Balance');

    // Assert figures
    $response->assertSee('40,000.00'); // opening due and purchase due
    $response->assertSee('80,000.00'); // total payable account balance
}
```

- [ ] **Step 2: Update `VendorController::index` and `downloadPdf`**
In `VendorController::index`:
```php
$vendors = Vendor::withSum(['purchases' => function($q) {
    $q->whereNull('deleted_at');
}], 'due')
->withSum(['purchases' => function($q) {
    $q->whereNull('deleted_at');
}], 'total_price')
->withSum('payments', 'amount')
->latest()
->get()
->map(function ($v) {
    $fin = $v->calculateFinancials(
        (float)($v->purchases_sum_total_price ?? 0),
        (float)($v->payments_sum_amount ?? 0)
    );
    $v->opening_due = max(0, (float)($v->opening_balance ?? 0));
    $v->purchase_due = (float)($v->purchases_sum_due ?? 0);
    $v->advance_balance = $fin['advance_balance'];
    $v->net_due = $fin['payable_due'];
    return $v;
});

$customers = $vendors; // Alias for compatibility
$bankAccounts = \App\Models\BankDetail::where('is_active', true)->orderBy('bank_name')->get();

return view('frontend.pages.vendor.index', compact('vendors', 'customers', 'bankAccounts'));
```
In `VendorController::downloadPdf`:
Map `$vendors` with `$opening_due`, `$purchase_due`, and `$net_due` and pass to `pdf.vendors`.

- [ ] **Step 3: Update `resources/views/frontend/pages/vendor/index.blade.php`**
Align table HTML exactly to `customer/index.blade.php`:
- Headers:
  - `<th class="ps-4">#</th>`
  - `<th>Vendor Name</th>`
  - `<th>Phone & Email</th>`
  - `<th>Address</th>`
  - `<th>Opening Due</th>`
  - `<th>Purchases Due</th>`
  - `<th>Account Balance</th>`
  - `<th>Status</th>`
  - `<th>Action</th>`
- Body Rows:
  - `Opening Due`: `৳{{ number_format($openingDue, 2) }}` (or badge/dash if 0.00)
  - `Purchases Due`: `৳{{ number_format($purchaseDue, 2) }}` (warning soft badge)
  - `Account Balance`:
    - If `$advanceBalance > 0`: `<span class="badge badge-soft-success">Advance: ৳{{ number_format($advanceBalance, 2) }}</span>`
    - Else if `$netDue > 0`: `<span class="badge badge-soft-danger">Due: ৳{{ number_format($netDue, 2) }}</span>`
    - Else: `<span class="badge bg-light text-secondary">Balanced</span>`
  - Action Dropdown:
    - 3-dot vertical dropdown with `data-bs-popper-config='{"strategy":"fixed"}'`, View, Edit, Account Ledger, Statement PDF, Pay Due modal trigger, and Delete.
- Modals:
  - Ensure vendor due payment modal is included outside table card if needed, or linked to standard vendor payment modal.

- [ ] **Step 4: Update `resources/views/pdf/vendors.blade.php`**
Add Opening Due column and status badges to match `pdf/customers.blade.php`.

- [ ] **Step 5: Run tests and verify**
Run: `php artisan test --filter=VendorWorkflowTest`
Expected: PASS

- [ ] **Step 6: Commit changes**
`git commit -m "feat: align vendor directory table and pdf report identical to customer directory"`

---

### Task 4: Update Vendor Show Profile to Display Opening Due Card & Pay Due Modal Integration

**Files:**
- Modify: `resources/views/frontend/pages/vendor/show.blade.php:160-220`
- Test: `tests/Feature/VendorWorkflowTest.php`

**Interfaces:**
- Consumes: `$vData->opening_balance`, `$totalDue`, `$advanceBalance`
- Produces: 4 financial stat cards on vendor profile matching Customer Profile:
  1. Total Purchase Orders
  2. Total Procurement Value (৳)
  3. Opening Due Balance (৳)
  4. Account Balance / Vendor Advance / Net Outstanding Due (৳)

- [ ] **Step 1: Write test for Vendor Show opening due stat card**
In `tests/Feature/VendorWorkflowTest.php`:
```php
public function test_vendor_show_displays_opening_due_metric_card(): void
{
    $vendor = Vendor::create([
        'name' => 'Profile Test Vendor',
        'phone' => '01400112233',
        'address' => 'Bhatiary, Chittagong',
        'opening_balance' => 50000.00,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin)->get(route('vendors.show', $vendor->id));
    $response->assertStatus(200);
    $response->assertSee('Opening Due Balance');
    $response->assertSee('50,000.00');
}
```

- [ ] **Step 2: Update `resources/views/frontend/pages/vendor/show.blade.php`**
In the metric cards section:
- Card 1: `Total Purchase Orders` (`{{ number_format($totalPurchases) }} Orders`)
- Card 2: `Total Purchase Value` (`৳{{ number_format($totalSpent, 2) }}`)
- Card 3: `Opening Due Balance` (`৳{{ number_format($openingDue, 2) }}` with `fe-clock`)
- Card 4: `Vendor Advance Balance` (if advance) OR `Net Outstanding Due` (`৳{{ number_format($effectiveDue, 2) }}`)
Also ensure the "Pay Vendor Due" header button reflects the full effective due (`$openingDue + $totalPurchasesDue`).

- [ ] **Step 3: Run test to verify it passes**
Run: `php artisan test --filter=test_vendor_show_displays_opening_due_metric_card`
Expected: PASS

- [ ] **Step 4: Commit changes**
`git commit -m "feat: add opening due balance stat card and full due calculations to vendor show profile"`

---

### Task 5: Full Regression Testing & Verification

**Files:**
- Test: All automated feature test suites

- [ ] **Step 1: Run the full test suite**
Run: `php artisan test`
Expected: 100% PASS (all 80+ tests passing, 0 failures)

- [ ] **Step 2: Verify git status and UI consistency**
Run: `git status`
Confirm clean working tree and no unexpected modifications.
```
