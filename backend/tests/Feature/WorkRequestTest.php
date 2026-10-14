<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkRequestTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['kind' => 'Izin', 'scope' => 'Sehari penuh', 'start_date' => '2026-10-15', 'end_date' => '2026-10-15', 'reason' => 'Urusan keluarga'], $extra);
    }

    private function submit(User $u, array $extra = []): WorkRequest
    {
        $this->actingAs($u)->post('/requests', $this->data($extra))->assertSessionHasNoErrors();

        return WorkRequest::latest('id')->firstOrFail();
    }

    public function test_pending_migration_does_not_break_admin_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Schema::drop('work_requests');
        $this->get('/admin')->assertOk()->assertSee('Modul pengajuan belum siap');
        $this->get('/requests')->assertStatus(503)->assertSee('Modul pengajuan belum siap');
    }

    public function test_guests_and_employees_cannot_review_and_history_is_private(): void
    {
        $this->get('/requests')->assertRedirect('/login');
        $owner = User::factory()->create();
        $item = $this->submit($owner, ['reason' => 'PRIVATE_REQUEST_REASON']);
        $this->get('/requests')->assertOk()->assertSee('PRIVATE_REQUEST_REASON');
        $other = User::factory()->create();
        $this->actingAs($other)->get('/requests')->assertOk()->assertDontSee('PRIVATE_REQUEST_REASON');
        $this->get('/admin/requests')->assertForbidden();
        $this->patch('/admin/requests/'.$item->id, ['status' => 'Disetujui', 'review_note' => 'OK'])->assertForbidden();
        $this->assertEquals('Menunggu', $item->fresh()->status);
    }

    public function test_all_four_kinds_are_pending_and_ignore_client_identity_and_status(): void
    {
        foreach (['Izin', 'Sakit', 'Cuti', 'Lembur'] as $kind) {
            $u = User::factory()->create();
            $item = $this->submit($u, ['kind' => $kind, 'start_time' => '18:00', 'end_time' => '20:30', 'user_id' => 999, 'status' => 'Disetujui', 'reviewer_id' => $u->id]);
            $this->assertEquals($u->id, $item->user_id);
            $this->assertEquals('Menunggu', $item->status);
            $this->assertNull($item->reviewer_id);
            $this->assertEquals($kind === 'Lembur' ? 150 : null, $item->duration_minutes);
        }
    }

    public function test_admin_decision_is_recorded_and_cannot_be_overwritten(): void
    {
        $item = $this->submit(User::factory()->create());
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/requests')->assertOk()->assertSee('Urusan keluarga');
        $this->patch('/admin/requests/'.$item->id, ['status' => 'Disetujui', 'review_note' => 'Disetujui HR'])->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertEquals('Disetujui', $item->status);
        $this->assertEquals($admin->id, $item->reviewer_id);
        $this->assertNotNull($item->reviewed_at);
        $this->patch('/admin/requests/'.$item->id, ['status' => 'Ditolak', 'review_note' => 'Ganti'])->assertSessionHasErrors('status');
        $this->assertEquals('Disetujui', $item->fresh()->status);
        $this->get('/admin?date=2026-10-15')->assertOk()->assertSee('Disetujui HR');
        $this->get('/admin?date=2026-10-16')->assertOk()->assertDontSee('Disetujui HR');
    }

    public function test_rejection_needs_note_and_does_not_appear_in_approved_recap(): void
    {
        $item = $this->submit(User::factory()->create(), ['reason' => 'REJECTED_REASON']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patch('/admin/requests/'.$item->id, ['status' => 'Ditolak'])->assertSessionHasErrors('review_note');
        $this->patch('/admin/requests/'.$item->id, ['status' => 'Ditolak', 'review_note' => 'Tidak sesuai'])->assertSessionHasNoErrors();
        $this->get('/admin')->assertOk()->assertDontSee('REJECTED_REASON');
    }

    public function test_admin_cannot_review_own_request(): void
    {
        $item = $this->submit(User::factory()->create(['role' => 'admin']));
        $this->patch('/admin/requests/'.$item->id, ['status' => 'Disetujui', 'review_note' => 'OK'])->assertForbidden();
    }

    public function test_invalid_ranges_missing_times_and_overlaps_are_rejected(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $this->post('/requests', $this->data(['end_date' => '2026-10-14']))->assertSessionHasErrors('end_date');
        $this->post('/requests', $this->data(['kind' => 'Lembur']))->assertSessionHasErrors(['start_time', 'end_time']);
        $this->post('/requests', $this->data(['scope' => 'Sebagian hari', 'start_time' => '10:00', 'end_time' => '09:00']))->assertSessionHasErrors('end_date');
        $this->post('/requests', $this->data(['kind' => 'Cuti', 'scope' => 'Sebagian hari']))->assertSessionHasErrors('scope');
        $this->submit($u);
        $this->post('/requests', $this->data(['kind' => 'Sakit']))->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('work_requests', 1);
    }

    public function test_overnight_overtime_and_partial_permission_are_supported(): void
    {
        $u = User::factory()->create();
        $item = $this->submit($u, ['kind' => 'Lembur', 'start_time' => '22:00', 'end_time' => '01:00', 'end_date' => '2026-10-16']);
        $this->assertEquals(180, $item->duration_minutes);
        $item = $this->submit($u, ['scope' => 'Sebagian hari', 'start_time' => '10:00', 'end_time' => '12:00']);
        $this->assertEquals('Sebagian hari', $item->scope);
    }

    public function test_request_history_prevents_account_deletion(): void
    {
        $u = User::factory()->create(['employee_code' => 'K001']);
        $this->submit($u);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete('/admin/users/'.$u->id, ['confirm_code' => 'K001'])->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users',['id' => $u->id]);
        $this->assertDatabaseCount('work_requests',1);
    }
}
