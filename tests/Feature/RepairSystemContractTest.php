<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairSystemContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_redirects_user_to_tickets_index(): void
    {
        $response = $this->post('/login', [
            'email' => 'user@repair.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/tickets');
        $this->assertAuthenticated();
    }

    public function test_login_redirects_technician_to_technician_tasks(): void
    {
        $response = $this->post('/login', [
            'email' => 'tech@repair.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/technician/tasks');
        $this->assertAuthenticated();
    }

    public function test_login_redirects_admin_to_admin_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@repair.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_can_create_ticket_with_image(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'user@repair.test')->first();
        $category = Category::first();

        $image = UploadedFile::fake()->image('broken_light.jpg');

        $response = $this->actingAs($user)->post('/tickets', [
            'category_id' => $category->id,
            'title' => 'หลอดไฟห้องทำงานเสีย',
            'location' => 'อาคาร 2 ห้อง 201',
            'urgency' => 'high',
            'description' => 'หลอดไฟดับ มีเสียงช็อต',
            'image_before' => $image,
        ]);

        $ticket = RepairRequest::where('title', 'หลอดไฟห้องทำงานเสีย')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('pending', $ticket->status);
        $this->assertNotNull($ticket->image_before);

        Storage::disk('public')->assertExists($ticket->image_before);

        $response->assertRedirect('/tickets/' . $ticket->id);
    }

    public function test_user_can_cancel_pending_ticket(): void
    {
        $user = User::where('email', 'user@repair.test')->first();
        $ticket = RepairRequest::where('user_id', $user->id)->where('status', 'pending')->first();

        $response = $this->actingAs($user)->post('/tickets/' . $ticket->id . '/cancel');

        $response->assertSessionHas('success');
        $this->assertEquals('cancelled', $ticket->fresh()->status);
    }

    public function test_admin_can_assign_technician_to_ticket(): void
    {
        $admin = User::where('email', 'admin@repair.test')->first();
        $tech = User::where('email', 'tech@repair.test')->first();
        $ticket = RepairRequest::where('status', 'pending')->first();

        $response = $this->actingAs($admin)->post('/admin/tickets/' . $ticket->id . '/assign', [
            'assigned_to' => $tech->id,
            'comment' => 'ให้เข้าซ่อมด่วนบ่ายนี้',
        ]);

        $response->assertSessionHas('success');
        $freshTicket = $ticket->fresh();
        $this->assertEquals($tech->id, $freshTicket->assigned_to);
        $this->assertEquals('assigned', $freshTicket->status);
    }

    public function test_technician_can_update_status_and_complete_job(): void
    {
        Storage::fake('public');

        $tech = User::where('email', 'tech@repair.test')->first();
        $ticket = RepairRequest::where('assigned_to', $tech->id)->first();

        // 1. In Progress
        $res1 = $this->actingAs($tech)->post('/technician/tasks/' . $ticket->id . '/status', [
            'status' => 'in_progress',
            'technician_notes' => 'เริ่มตรวจเช็คหน้างาน',
        ]);
        $res1->assertStatus(302);
        $res1->assertSessionHasNoErrors();
        $this->assertEquals('in_progress', $ticket->fresh()->status);

        // 2. Completed with after image & cost
        $afterImage = UploadedFile::fake()->image('fixed.jpg');
        $this->actingAs($tech)->post('/technician/tasks/' . $ticket->id . '/status', [
            'status' => 'completed',
            'technician_notes' => 'เปลี่ยนชิ้นส่วนและทดสอบผ่านปกติ',
            'repair_cost' => 250.00,
            'image_after' => $afterImage,
        ]);

        $completedTicket = $ticket->fresh();
        $this->assertEquals('completed', $completedTicket->status);
        $this->assertEquals(250.00, $completedTicket->repair_cost);
        $this->assertNotNull($completedTicket->image_after);
        $this->assertNotNull($completedTicket->completed_at);
    }

    public function test_user_can_review_completed_ticket_and_close_it(): void
    {
        $user = User::where('email', 'user@repair.test')->first();
        $ticket = RepairRequest::where('user_id', $user->id)->where('status', 'completed')->first();

        $response = $this->actingAs($user)->post('/tickets/' . $ticket->id . '/review', [
            'rating' => 5,
            'comment' => 'ยอดเยี่ยม ช่างทำงานเรียบร้อยมาก',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->review);
        $this->assertEquals(5, $ticket->fresh()->review->rating);
    }

    public function test_role_authorization_middleware_protects_routes(): void
    {
        $user = User::where('email', 'user@repair.test')->first();
        $tech = User::where('email', 'tech@repair.test')->first();

        // Normal user cannot access admin dashboard -> 403
        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);

        // Normal user cannot access tech tasks -> 403
        $response = $this->actingAs($user)->get('/technician/tasks');
        $response->assertStatus(403);

        // Technician cannot access admin dashboard -> 403
        $response = $this->actingAs($tech)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_user_cannot_view_other_user_ticket(): void
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        $ticket = RepairRequest::where('user_id', '!=', $otherUser->id)->first();

        $response = $this->actingAs($otherUser)->get('/tickets/' . $ticket->id);
        $response->assertStatus(403);
    }

    public function test_user_cannot_cancel_already_assigned_ticket(): void
    {
        $user = User::where('email', 'user@repair.test')->first();
        $ticket = RepairRequest::where('user_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'completed'])
            ->first();

        if ($ticket) {
            $response = $this->actingAs($user)->post('/tickets/' . $ticket->id . '/cancel');
            $response->assertSessionHas('error');
            $this->assertNotEquals('cancelled', $ticket->fresh()->status);
        }
    }

    public function test_admin_can_view_tickets_and_filter(): void
    {
        $admin = User::where('email', 'admin@repair.test')->first();

        $response = $this->actingAs($admin)->get('/admin/tickets?status=pending');
        $response->assertOk();
        $response->assertViewHas('tickets');
    }
}
