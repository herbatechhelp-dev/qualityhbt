<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_superadmin_cannot_access_users_management()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);
        $qa = User::factory()->create(['role' => 'qa']);

        $response = $this->actingAs($initiator)->get(route('superadmin.users'));
        $response->assertStatus(403);

        $response = $this->actingAs($qa)->get(route('superadmin.users'));
        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_users_management()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.users'));
        $response->assertStatus(200);
    }

    public function test_superadmin_can_create_user()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@qms.com',
            'password' => 'password123',
            'role' => 'qa',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@qms.com',
            'role' => 'qa',
        ]);
    }

    public function test_superadmin_can_update_user()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $targetUser = User::factory()->create(['role' => 'initiator']);

        $response = $this->actingAs($superadmin)->put(route('superadmin.users.update', $targetUser->id), [
            'name' => 'Updated User Name',
            'email' => 'updatedemail@qms.com',
            'role' => 'qa',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated User Name',
            'email' => 'updatedemail@qms.com',
            'role' => 'qa',
        ]);
    }

    public function test_superadmin_can_delete_user()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $targetUser = User::factory()->create(['role' => 'initiator']);

        $response = $this->actingAs($superadmin)->delete(route('superadmin.users.destroy', $targetUser->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_superadmin_cannot_delete_self()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($superadmin)->delete(route('superadmin.users.destroy', $superadmin->id));
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', [
            'id' => $superadmin->id,
        ]);
    }

    public function test_superadmin_can_update_settings()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.settings.update'), [
            'app_name' => 'My Custom QMS Portal',
            'app_logo_type' => 'text',
            'app_logo' => '🚀 Custom Logo',
            'google_spreadsheet_id' => 'new-spreadsheet-id-xyz',
            'google_service_account_json' => '{"type": "service_account"}',
            'print_logo_type' => 'text',
            'print_company_name' => 'HERBATECH',
        ]);

        $response->assertRedirect();
        $this->assertEquals('My Custom QMS Portal', Setting::getValue('app_name'));
        $this->assertEquals('🚀 Custom Logo', Setting::getValue('app_logo'));
        $this->assertEquals('new-spreadsheet-id-xyz', Setting::getValue('google_spreadsheet_id'));
        $this->assertEquals('{"type": "service_account"}', Setting::getValue('google_service_account_json'));
        $this->assertEquals('HERBATECH', Setting::getValue('print_company_name'));
        $this->assertEquals('text', Setting::getValue('print_logo_type'));
    }
}
