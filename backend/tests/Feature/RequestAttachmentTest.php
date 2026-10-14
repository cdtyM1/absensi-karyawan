<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['kind' => 'Sakit', 'scope' => 'Sehari penuh', 'start_date' => '2026-10-15', 'end_date' => '2026-10-15', 'reason' => 'Istirahat sesuai surat dokter'], $extra);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('evidence');
    }

    public function test_photo_is_private_and_accessible_to_owner_and_admin_only(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post('/requests', $this->data(['attachment' => UploadedFile::fake()->image('surat.jpg', 640, 480)]))->assertSessionHasNoErrors();
        $item = WorkRequest::first();
        Storage::disk('evidence')->assertExists($item->attachment_path);
        $url = '/requests/'.$item->id.'/attachment';
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/requests')->assertOk()->assertSee('Lihat lampiran foto');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertOk();
        $this->get('/admin/requests')->assertOk()->assertSee('Lihat lampiran foto');
        $this->post('/logout');
        $this->get($url)->assertRedirect('/login');
    }

    public function test_attachment_is_optional_and_missing_photo_returns_404(): void
    {
        $this->actingAs(User::factory()->create())->post('/requests', $this->data())->assertSessionHasNoErrors();
        $item = WorkRequest::first();
        $this->assertNull($item->attachment_path);
        $this->get('/requests/'.$item->id.'/attachment')->assertNotFound();
    }

    public function test_non_image_and_oversized_files_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/requests', $this->data(['attachment' => UploadedFile::fake()->create('evil.php', 1, 'text/plain')]))->assertSessionHasErrors('attachment');
        $this->post('/requests', $this->data(['attachment' => UploadedFile::fake()->image('big.png')->size(5121)]))->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('work_requests', 0);
        $this->assertCount(0, Storage::disk('evidence')->allFiles());
    }

    public function test_duplicate_request_does_not_leave_an_orphan_photo(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/requests', $this->data(['attachment' => UploadedFile::fake()->image('first.jpg')]))->assertSessionHasNoErrors();
        $this->post('/requests', $this->data(['attachment' => UploadedFile::fake()->image('duplicate.jpg')]))->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('work_requests', 1);
        $this->assertCount(1, Storage::disk('evidence')->allFiles());
    }

    public function test_missing_attachment_migration_preserves_old_requests(): void
    {
        Schema::table('work_requests', fn ($table) => $table->dropColumn('attachment_path'));
        $this->actingAs(User::factory()->create());
        $this->post('/requests', $this->data(['attachment' => UploadedFile::fake()->image('surat.jpg')]))->assertSessionHasErrors('attachment');
        $this->post('/requests', $this->data())->assertSessionHasNoErrors();
        $this->get('/requests')->assertOk();
    }
}
