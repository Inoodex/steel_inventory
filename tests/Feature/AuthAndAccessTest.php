<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@steel.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Staff User',
            'email' => 'staff@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_logout_redirects_safely(): void
    {
        $user = User::create([
            'name' => 'Logout Test User',
            'email' => 'logout@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);

        $response = $this->actingAs($user)->get('/logout');
        $response->assertRedirect('/login');
    }
}
