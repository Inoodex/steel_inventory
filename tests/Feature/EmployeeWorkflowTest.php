<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@steel.test',
            'password' => Hash::make('password'),
            'status'   => '1',
        ]);
        $this->admin->assignRole($role);
    }

    public function test_employee_create_page_renders_with_optional_labels(): void
    {
        $response = $this->actingAs($this->admin)->get(route('employees.create'));
        $response->assertStatus(200);
        $response->assertSee('Email Address <span class="text-muted fw-normal">(optional)</span>', false);
        $response->assertSee('Phone Number <span class="text-muted fw-normal">(optional)</span>', false);
        $response->assertSee('Designation <span class="text-muted fw-normal">(optional)</span>', false);
    }

    public function test_can_create_employee_without_email_phone_and_designation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('employees.store'), [
            'employee_id' => 'EMP-OPT-001',
            'name'        => 'Rahim Uddin',
            'email'       => null,
            'phone'       => null,
            'designation' => null,
            'salary'      => 18000.00,
            'status'      => 'active',
            'join_date'   => '2026-11-01',
        ]);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'employee_id' => 'EMP-OPT-001',
            'name'        => 'Rahim Uddin',
            'email'       => null,
            'phone'       => null,
            'designation' => null,
            'salary'      => 18000.00,
            'status'      => 'active',
        ]);
    }

    public function test_can_update_employee_without_email_phone_and_designation(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-OPT-002',
            'name'        => 'Karim Mia',
            'email'       => 'karim@test.com',
            'phone'       => '01711000000',
            'designation' => 'Foreman',
            'salary'      => 22000.00,
            'status'      => 'active',
            'join_date'   => '2026-10-01',
        ]);

        $response = $this->actingAs($this->admin)->get(route('employees.edit', $employee->id));
        $response->assertStatus(200);
        $response->assertSee('Email Address <span class="text-muted fw-normal">(optional)</span>', false);
        $response->assertSee('Phone Number <span class="text-muted fw-normal">(optional)</span>', false);
        $response->assertSee('Designation <span class="text-muted fw-normal">(optional)</span>', false);

        $updateResponse = $this->actingAs($this->admin)->put(route('employees.update', $employee->id), [
            'employee_id' => 'EMP-OPT-002',
            'name'        => 'Karim Mia Updated',
            'email'       => '',
            'phone'       => '',
            'designation' => '',
            'salary'      => 25000.00,
            'status'      => 'active',
            'join_date'   => '2026-10-01',
        ]);

        $updateResponse->assertRedirect(route('employees.index'));
        $updateResponse->assertSessionHas('success');

        $employee->refresh();
        $this->assertEquals('Karim Mia Updated', $employee->name);
        $this->assertNull($employee->email);
        $this->assertNull($employee->phone);
        $this->assertNull($employee->designation);
        $this->assertEquals(25000.00, (float) $employee->salary);
    }
}
