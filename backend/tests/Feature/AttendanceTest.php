<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Services\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['attendance.require_evidence' => false]);
    }

    private function employee(): User
    {
        return User::factory()->create(['employee_code' => 'J001']);
    }

    public function test_guest_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/attendance/in')->assertRedirect('/login');
    }

    public function test_server_time_and_user_identity_are_authoritative(): void
    {
        $u = $this->employee();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 08:45:00', 'Asia/Jakarta'));
        $this->actingAs($u)->post('/attendance/in', ['mode' => 'Kantor', 'user_id' => 999, 'checked_in_at' => '2026-10-14 08:00:00', 'role' => 'admin'])->assertSessionHasNoErrors();
        $a = Attendance::first();
        $this->assertEquals($u->id, $a->user_id);
        $this->assertEquals('08:45:00', $a->checked_in_at->format('H:i:s'));
        $this->assertEquals('Terlambat', $a->arrival_status);
        $this->assertEquals(15, $a->late_minutes);
    }

    public function test_duplicates_and_check_out_order(): void
    {
        $u = $this->employee();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 08:30:00', 'Asia/Jakarta'));
        $this->actingAs($u)->post('/attendance/out')->assertSessionHasErrors('attendance');
        $this->post('/attendance/in', ['mode' => 'Kantor']);
        $this->post('/attendance/in', ['mode' => 'Kantor'])->assertSessionHasErrors('attendance');
        $this->travelTo(CarbonImmutable::parse('2026-10-14 17:00:00', 'Asia/Jakarta'));
        $this->post('/attendance/out');
        $this->post('/attendance/out')->assertSessionHasErrors('attendance');
        $this->assertDatabaseCount('attendances', 1);
        $this->assertEquals('Selesai', Attendance::first()->departure_status);
    }

    public function test_tolerance_boundary_and_saturday(): void
    {
        foreach (['08:30:00' => 'Tepat waktu', '08:38:00' => 'Dalam toleransi', '08:40:00' => 'Dalam toleransi', '08:40:01' => 'Terlambat'] as $t => $expected) {
            $this->assertEquals($expected, WorkSchedule::arrival(CarbonImmutable::parse('2026-10-14 '.$t, 'Asia/Jakarta'))['arrival_status']);
        }$this->assertEquals('Selesai', WorkSchedule::departure(CarbonImmutable::parse('2026-10-17 12:30:00', 'Asia/Jakarta')));
        $this->assertEquals('Pulang awal', WorkSchedule::departure(CarbonImmutable::parse('2026-10-17 12:29:59', 'Asia/Jakarta')));
    }

    public function test_lapangan_needs_assignment_and_sunday_is_blocked(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-14 08:30:00', 'Asia/Jakarta'));
        $this->actingAs($this->employee())->post('/attendance/in', ['mode' => 'Lapangan'])->assertSessionHasErrors('assignment');
        $this->travelTo(CarbonImmutable::parse('2026-10-18 08:30:00', 'Asia/Jakarta'));
        $this->post('/attendance/in', ['mode' => 'Kantor'])->assertSessionHasErrors('attendance');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_employee_cannot_access_hr_or_escalate_role(): void
    {
        $this->actingAs($this->employee())->get('/admin')->assertForbidden();
        $this->get('/admin/export')->assertForbidden();
        $this->post('/admin/users', ['role' => 'admin'])->assertForbidden();
    }

    public function test_history_is_private(): void
    {
        $u = $this->employee();
        $other = User::factory()->create();
        Attendance::create(['user_id' => $other->id, 'work_date' => '2026-10-14', 'mode' => 'Lapangan', 'assignment' => 'PRIVATE_OTHER_TASK', 'checked_in_at' => '2026-10-14 06:12:34', 'arrival_status' => 'Tepat waktu']);
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertDontSee('PRIVATE_OTHER_TASK')->assertDontSee('06:12:34');
    }

    public function test_hr_creates_employee_and_exports_real_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/users', ['employee_code' => 'J002', 'name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'LongPassword1234', 'password_confirmation' => 'LongPassword1234', 'role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'staff@example.test', 'role' => 'employee']);
        $this->get('/admin')->assertOk()->assertSee('Staff');
        $this->get('/admin/export')->assertOk()->assertDownload('rekap-absensi.csv');
    }

    public function test_inactive_account_cannot_login_or_keep_session(): void
    {
        $u = $this->employee();
        $u->active = false;
        $u->save();
        $this->actingAs($u)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_login_and_logout(): void
    {
        $u = $this->employee();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_attempts_are_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'no@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }$this->post('/login', ['email' => 'no@example.test', 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'Terlalu banyak percobaan. Coba lagi setelah satu menit.']);
    }

    public function test_csv_export_contains_real_records_and_escapes_formulas(): void
    {
        $u = $this->employee();
        $u->name = '=1+1';
        $u->save();
        Attendance::create(['user_id' => $u->id, 'work_date' => '2026-10-14', 'mode' => 'Kantor', 'checked_in_at' => '2026-10-14 08:30:00', 'arrival_status' => 'Tepat waktu']);
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = $this->actingAs($admin)->get('/admin/export')->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString('J001', $csv);
        $this->assertStringContainsString('08:30:00', $csv);
    }
}
