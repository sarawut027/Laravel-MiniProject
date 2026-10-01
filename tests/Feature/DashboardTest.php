<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_redirected_to_role_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('tickets.index'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $tech = User::factory()->create(['role' => 'technician']);
        $this->actingAs($tech)->get('/dashboard')->assertRedirect(route('tech.tasks'));
    }
}
