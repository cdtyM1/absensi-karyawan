<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['version' => 0, 'start_time' => '08:30', 'weekday_end_time' => '17:00', 'saturday_end_time' => '12:30', 'tolerance_minutes' => 10, 'require_location' => 0, 'office_latitude' => -6.3259438, 'office_longitude' => 106.9686316, 'office_radius_m' => 100, 'office_max_accuracy_m' => 100], $extra);
    }

    public function test_only_admin_can_read_and_save_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/settings')->assertForbidden();
        $this->patch('/admin/settings', $this->data())->assertForbidden();
        $this->assertDatabaseCount('attendance_settings', 0);
    }

    public function test_initial_values_preserve_environment_and_missing_migration_is_safe(): void
    {
        config(['attendance.require_location' => false, 'attendance.office_latitude' => -6.3259438]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/settings')->assertOk()->assertSee('value="08:30"', false)->assertSee('value="-6.3259438"', false);
        $this->assertDatabaseCount('attendance_settings', 0);
        Schema::drop('attendance_settings');
        $this->get('/admin/settings')->assertOk()->assertSee('Pengaturan belum siap');
        $this->patch('/admin/settings', $this->data())->assertSessionHasErrors('settings');
    }

    public function test_settings_apply_to_subsequent_attendance_and_old_records_are_unchanged(): void
    {
        Storage::fake('evidence');
        config(['attendance.require_evidence' => false]);
        $u = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $old = Attendance::create(['user_id' => $u->id, 'work_date' => '2026-10-13', 'mode' => 'Kantor', 'checked_in_at' => '2026-10-13 08:45:00', 'arrival_status' => 'Terlambat', 'late_minutes' => 15]);
        $this->actingAs($admin)->patch('/admin/settings', $this->data(['start_time' => '09:00', 'weekday_end_time' => '18:00', 'saturday_end_time' => '13:00', 'tolerance_minutes' => 5]))->assertSessionHasNoErrors();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:05:00', 'Asia/Jakarta'));
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('09:00–18:00')->assertSee('Toleransi 5 menit');
        $this->post('/attendance/in', ['mode' => 'Kantor'])->assertSessionHasNoErrors();
        $today = Attendance::whereDate('work_date', '2026-10-14')->first();
        $this->assertEquals('Dalam toleransi', $today->arrival_status);
        $this->assertEquals('18:00', $today->scheduled_end_at->format('H:i'));
        $this->assertEquals(5, $today->tolerance_minutes);
        $this->assertEquals('Terlambat', $old->fresh()->arrival_status);
        $this->assertEquals(15, $old->fresh()->late_minutes);
        $this->actingAs($admin)->patch('/admin/settings', $this->data(['version' => 1, 'weekday_end_time' => '19:00']))->assertSessionHasNoErrors();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 18:00:00', 'Asia/Jakarta'));
        $this->actingAs($u)->post('/attendance/out')->assertSessionHasNoErrors();
        $this->assertEquals('Selesai', $today->fresh()->departure_status);
    }

    public function test_saturday_and_zero_tolerance_use_admin_schedule(): void
    {
        config(['attendance.require_evidence'=>false]);
        $this->actingAs(User::factory()->create(['role'=>'admin']))->patch('/admin/settings',$this->data(['start_time'=>'09:00','saturday_end_time'=>'13:00','tolerance_minutes'=>0]))->assertSessionHasNoErrors();
        $u=User::factory()->create();
        $this->travelTo(CarbonImmutable::parse('2026-10-17 09:00:01','Asia/Jakarta'));
        $this->actingAs($u)->post('/attendance/in',['mode'=>'Kantor'])->assertSessionHasNoErrors();
        $a=Attendance::first();
        $this->assertEquals('Terlambat',$a->arrival_status);
        $this->assertEquals(1,$a->late_minutes);
        $this->assertEquals('13:00',$a->scheduled_end_at->format('H:i'));
        $this->travelTo(CarbonImmutable::parse('2026-10-17 13:00:00','Asia/Jakarta'));
        $this->post('/attendance/out')->assertSessionHasNoErrors();
        $this->assertEquals('Selesai',$a->fresh()->departure_status);
    }

    public function test_invalid_times_coordinates_and_tolerance_do_not_change_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patch('/admin/settings', $this->data(['weekday_end_time' => '08:00']))->assertSessionHasErrors('weekday_end_time');
        $this->patch('/admin/settings', $this->data(['tolerance_minutes' => 121]))->assertSessionHasErrors('tolerance_minutes');
        $this->patch('/admin/settings', $this->data(['office_longitude' => null]))->assertSessionHasErrors('office_longitude');
        $this->patch('/admin/settings', $this->data(['require_location' => 1, 'office_latitude' => null, 'office_longitude' => null]))->assertSessionHasErrors(['office_latitude', 'office_longitude']);
        $this->assertDatabaseCount('attendance_settings', 0);
    }

    public function test_stale_admin_form_cannot_overwrite_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patch('/admin/settings', $this->data())->assertSessionHasNoErrors();
        $this->patch('/admin/settings', $this->data(['start_time' => '09:00']))->assertSessionHasErrors('version');
        $stored = json_decode(DB::table('attendance_settings')->value('data'), true);
        $this->assertEquals('08:30', $stored['start_time']);
        $this->assertEquals(1, DB::table('attendance_settings')->value('version'));
    }

    public function test_gps_policy_and_radius_use_saved_settings(): void
    {
        Storage::fake('evidence');
        config(['attendance.require_evidence' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patch('/admin/settings', $this->data(['require_location' => 1]))->assertSessionHasNoErrors();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 08:30:00', 'Asia/Jakarta'));
        $this->actingAs(User::factory()->create());
        $this->post('/attendance/in', ['mode' => 'Kantor', 'selfie' => UploadedFile::fake()->image('selfie.jpg', 640, 480)])->assertSessionHasErrors('latitude');
        $this->post('/attendance/in', ['mode' => 'Kantor', 'latitude' => -7, 'longitude' => 106.9686316, 'accuracy' => 10, 'selfie' => UploadedFile::fake()->image('selfie.jpg', 640, 480)])->assertSessionHasErrors('location');
        $this->post('/attendance/in', ['mode' => 'Kantor', 'latitude' => -6.3259438, 'longitude' => 106.9686316, 'accuracy' => 10, 'selfie' => UploadedFile::fake()->image('selfie.jpg',640,480)])->assertSessionHasNoErrors();
        $this->assertEquals('Dalam radius kantor',Attendance::first()->in_location_note);
    }
}
