<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('evidence');
        config(['attendance.require_evidence' => true]);
        $this->travelTo(CarbonImmutable::parse('2026-10-14 08:30:00', 'Asia/Jakarta'));
    }

    private function payload(): array
    {
        return ['mode' => 'Kantor', 'latitude' => -6.2, 'longitude' => 106.8, 'accuracy' => 25, 'selfie' => UploadedFile::fake()->image('selfie.jpg', 640, 480)];
    }

    public function test_missing_evidence_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post('/attendance/in', ['mode' => 'Kantor'])->assertSessionHasErrors(['latitude', 'longitude', 'accuracy', 'selfie']);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_evidence_is_saved_privately_and_other_employee_is_denied(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post('/attendance/in', $this->payload())->assertSessionHasNoErrors();
        $a = Attendance::first();
        Storage::disk('evidence')->assertExists($a->in_selfie_path);
        $this->assertEquals(-6.2, $a->in_latitude);
        $this->assertEquals('Lokasi tercatat; radius belum diatur', $a->in_location_note);
        $this->get('/attendance/'.$a->id.'/selfie/in')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->create())->get('/attendance/'.$a->id.'/selfie/in')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/attendance/'.$a->id.'/selfie/in')->assertOk();
    }

    public function test_checkout_requires_new_evidence_and_keeps_original_record(): void
    {
        $this->actingAs(User::factory()->create())->post('/attendance/in', $this->payload());
        $this->post('/attendance/out')->assertSessionHasErrors('selfie');
        $this->assertNull(Attendance::first()->checked_out_at);
        $this->travelTo(CarbonImmutable::parse('2026-10-14 17:00:00', 'Asia/Jakarta'));
        $this->post('/attendance/out', $this->payload())->assertSessionHasNoErrors();
        $a = Attendance::first();
        $this->assertNotEquals($a->in_selfie_path, $a->out_selfie_path);
        Storage::disk('evidence')->assertExists($a->out_selfie_path);
        $this->assertEquals('Selesai', $a->departure_status);
    }

    public function test_office_radius_and_accuracy_are_validated(): void
    {
        config(['attendance.office_latitude' => -6.2, 'attendance.office_longitude' => 106.8]);
        $this->actingAs(User::factory()->create());
        $p = $this->payload();
        $p['latitude'] = -7;
        $this->post('/attendance/in', $p)->assertSessionHasErrors('location');
        $p = $this->payload();
        $p['accuracy'] = 200;
        $this->post('/attendance/in', $p)->assertSessionHasErrors('location');
        $this->assertDatabaseCount('attendances', 0);
        $this->post('/attendance/in', $this->payload())->assertSessionHasNoErrors();
        $this->assertEquals('Dalam radius kantor', Attendance::first()->in_location_note);
    }

    public function test_invalid_coordinates_and_non_image_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $p = $this->payload();
        $p['latitude'] = 91;
        $p['selfie'] = UploadedFile::fake()->create('payload.php', 10, 'text/plain');
        $this->post('/attendance/in', $p)->assertSessionHasErrors(['latitude', 'selfie']);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_lapangan_is_recorded_without_claiming_office_validation(): void
    {
        config(['attendance.office_latitude' => -6.2, 'attendance.office_longitude' => 106.8]);
        $p = $this->payload();
        $p['mode'] = 'Lapangan';
        $p['assignment'] = 'Kunjungan customer';
        $p['latitude'] = -7;
        $this->actingAs(User::factory()->create())->post('/attendance/in', $p)->assertSessionHasNoErrors();
        $this->assertEquals('Lokasi lapangan tercatat; perlu pemeriksaan HR', Attendance::first()->in_location_note);
        $this->get('/dashboard')->assertOk()->assertSee('Coba lokasi lagi');
    }

    public function test_old_records_without_evidence_are_supported(): void
    {
        $u = User::factory()->create();
        $a = Attendance::create(['user_id' => $u->id, 'work_date' => '2026-10-13', 'mode' => 'Kantor', 'checked_in_at' => '2026-10-13 08:30:00', 'arrival_status' => 'Tepat waktu']);
        $this->actingAs($u)->get('/attendance/'.$a->id.'/selfie/in')->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk()->assertSee('Data lama: belum ada bukti');
    }

    public function test_selfie_only_mode_saves_null_location_and_hr_note(): void
    {
        config(['attendance.require_location' => false, 'attendance.office_latitude' => -6.2, 'attendance.office_longitude' => 106.8]);
        $this->actingAs(User::factory()->create());
        $this->post('/attendance/in', ['mode' => 'Kantor'])->assertSessionHasErrors('selfie');
        $this->assertDatabaseCount('attendances', 0);
        $this->post('/attendance/in', ['mode' => 'Kantor', 'selfie' => UploadedFile::fake()->image('selfie.jpg', 640, 480)])->assertSessionHasNoErrors();
        $a = Attendance::first();
        $this->assertNull($a->in_latitude);
        $this->assertNull($a->in_longitude);
        $this->assertNull($a->in_accuracy);
        Storage::disk('evidence')->assertExists($a->in_selfie_path);
        $this->assertEquals('Lokasi tidak tersedia—perlu pemeriksaan HR', $a->in_location_note);
        $this->post('/attendance/out')->assertSessionHasErrors('selfie');
        $this->post('/attendance/out', ['selfie' => UploadedFile::fake()->image('out.jpg', 640, 480)])->assertSessionHasNoErrors();
        $this->assertNull($a->fresh()->out_latitude);
        Storage::disk('evidence')->assertExists($a->fresh()->out_selfie_path);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk()->assertSee('Lokasi tidak tersedia—perlu pemeriksaan HR')->assertDontSee('Akurasi ± m');
    }

    public function test_optional_location_still_rejects_partial_and_outside_coordinates(): void
    {
        config(['attendance.require_location' => false, 'attendance.office_latitude' => -6.2, 'attendance.office_longitude' => 106.8]);
        $this->actingAs(User::factory()->create());
        $p = $this->payload();
        unset($p['longitude']);
        $this->post('/attendance/in', $p)->assertSessionHasErrors('longitude');
        $p = $this->payload();
        $p['latitude'] = -7;
        $this->post('/attendance/in', $p)->assertSessionHasErrors('location');
        config(['attendance.require_location' => true]);
        $this->post('/attendance/in', ['mode' => 'Kantor', 'selfie' => UploadedFile::fake()->image('selfie.jpg', 640, 480)])->assertSessionHasErrors(['latitude', 'longitude', 'accuracy']);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_does_not_leave_orphan_files(): void
    {
        $this->actingAs(User::factory()->create())->post('/attendance/in', $this->payload());
        $this->post('/attendance/in', $this->payload())->assertSessionHasErrors('attendance');
        $this->assertCount(1,Storage::disk('evidence')->allFiles());
    }
}
