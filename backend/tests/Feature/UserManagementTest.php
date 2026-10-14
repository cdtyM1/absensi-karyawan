<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM01'])->fresh();
    }

    private function staff(): User
    {
        return User::factory()->create(['employee_code' => 'K001'])->fresh();
    }

    private function data(User $u): array
    {
        return ['name' => $u->name, 'email' => $u->email, 'active' => 1];
    }

    public function test_employee_cannot_manage_any_accounts(): void
    {
        $u = $this->staff();
        $this->actingAs($u)->get('/admin/users/'.$u->id.'/edit')->assertForbidden();
        $this->patch('/admin/users/'.$u->id, $this->data($u))->assertForbidden();
        $this->delete('/admin/users/'.$u->id, ['confirm_code' => 'K001'])->assertForbidden();
    }

    public function test_admin_can_change_email_and_blank_password_preserves_hash(): void
    {
        $u = $this->staff();
        $hash = $u->password;
        $this->actingAs($this->admin())->get('/admin/users/'.$u->id.'/edit')->assertOk()->assertDontSee($hash);
        $this->patch('/admin/users/'.$u->id, [...$this->data($u), 'email' => ' NEW@example.test ', 'password' => '', 'password_confirmation' => '', 'role' => 'admin'])->assertSessionHasNoErrors();
        $u->refresh();
        $this->assertEquals('new@example.test', $u->email);
        $this->assertEquals($hash, $u->password);
        $this->assertEquals('employee', $u->role);
        $this->assertEquals(2, $u->auth_version);
    }

    public function test_reset_password_invalidates_old_session(): void
    {
        $u = $this->staff();
        $this->actingAs($this->admin())->patch('/admin/users/'.$u->id, [...$this->data($u), 'password' => 'FreshPassword12345', 'password_confirmation' => 'FreshPassword12345'])->assertSessionHasNoErrors();
        $u->refresh();
        $this->assertTrue(Hash::check('FreshPassword12345', $u->password));
        $this->assertEquals(2, $u->auth_version);
        $this->actingAs($u)->withSession(['auth_version' => 1])->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $u->email, 'password' => 'FreshPassword12345'])->assertRedirect('/dashboard');
    }

    public function test_duplicate_email_and_mismatched_password_are_rejected(): void
    {
        $u = $this->staff();
        $admin = $this->admin();
        $this->actingAs($admin)->patch('/admin/users/'.$u->id, [...$this->data($u), 'email' => $admin->email])->assertSessionHasErrors('email');
        $this->patch('/admin/users/'.$u->id, [...$this->data($u), 'password' => 'short', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
    }

    public function test_short_password_is_accepted_for_reset(): void
    {
        $u = $this->staff();
        $this->actingAs($this->admin())->patch('/admin/users/'.$u->id, [...$this->data($u), 'password' => 'a', 'password_confirmation' => 'a'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('a', $u->fresh()->password));
    }

    public function test_disable_preserves_attendance_and_rejects_login(): void
    {
        $u = $this->staff();
        Attendance::create(['user_id' => $u->id, 'work_date' => '2026-10-14', 'mode' => 'Kantor', 'checked_in_at' => '2026-10-14 08:30:00', 'arrival_status' => 'Tepat waktu']);
        $this->actingAs($this->admin())->patch('/admin/users/'.$u->id, [...$this->data($u), 'active' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $u->id, 'active' => false]);
        $this->assertDatabaseCount('attendances', 1);
        $this->actingAs($u->fresh())->get('/dashboard')->assertRedirect('/login');
    }

    public function test_delete_requires_confirmation_and_only_unused_employee_can_be_deleted(): void
    {
        $u = $this->staff();
        $this->actingAs($this->admin())->delete('/admin/users/'.$u->id, ['confirm_code' => 'wrong'])->assertSessionHasErrors('confirm_code');
        $this->assertDatabaseHas('users', ['id' => $u->id]);
        $this->delete('/admin/users/'.$u->id, ['confirm_code' => 'K001'])->assertRedirect('/admin');
        $this->assertDatabaseMissing('users', ['id' => $u->id]);
    }

    public function test_admin_and_employee_with_history_cannot_be_deleted(): void
    {
        $u = $this->staff();
        Attendance::create(['user_id' => $u->id, 'work_date' => '2026-10-14', 'mode' => 'Kantor', 'checked_in_at' => '2026-10-14 08:30:00', 'arrival_status' => 'Tepat waktu']);
        $admin = $this->admin();
        $this->actingAs($admin)->delete('/admin/users/'.$u->id, ['confirm_code' => 'K001'])->assertSessionHasErrors('delete');
        $this->delete('/admin/users/'.$admin->id, ['confirm_code' => 'ADM01'])->assertSessionHasErrors('delete');
        $this->patch('/admin/users/'.$admin->id, [...$this->data($admin), 'active' => 0])->assertSessionHasErrors('active');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_self_email_edit_preserves_admin_session_version(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->withSession(['auth_version' => 1])->patch('/admin/users/'.$admin->id, [...$this->data($admin), 'email' => 'adminnew@example.test'])->assertSessionHasNoErrors()->assertSessionHas('auth_version', 2);
        $this->actingAs($admin->fresh())->get('/admin')->assertOk()->assertSee('Kelola');
    }
}
