<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeCodeTest extends TestCase
{
    use RefreshDatabase;

    private function createEmployee(string $email, array $extra = [])
    {
        return $this->post('/admin/users', array_merge(['name' => 'New Staff', 'email' => $email, 'password' => 'LongPassword1234', 'password_confirmation' => 'LongPassword1234'], $extra));
    }

    public function test_admin_remains_accessible_before_numbering_migration(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        \Illuminate\Support\Facades\Schema::drop('employee_code_sequences');
        $this->get('/admin')->assertOk()->assertSee('Rekap kehadiran');
        $this->createEmployee('new@example.test')->assertSessionHasErrors('employee_code');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_ids_continue_existing_sequence_and_ignore_client_supplied_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'z001']);
        User::factory()->create(['employee_code' => 'a001']);
        User::factory()->create(['employee_code' => 'a002']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('value="a003"', false);
        $this->createEmployee('one@example.test', ['employee_code' => 'a001', 'role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'one@example.test', 'employee_code' => 'a003', 'role' => 'employee']);
        $this->createEmployee('two@example.test')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'two@example.test', 'employee_code' => 'a004']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'employee_code' => 'z001']);
    }

    public function test_short_password_is_accepted_but_empty_and_mismatch_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->createEmployee('empty@example.test', ['password' => '', 'password_confirmation' => ''])->assertSessionHasErrors('password');
        $this->createEmployee('wrong@example.test', ['password' => 'a', 'password_confirmation' => 'b'])->assertSessionHasErrors('password');
        $this->createEmployee('short@example.test', ['password' => 'a', 'password_confirmation' => 'a'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a', User::where('email', 'short@example.test')->first()->password));
    }

    public function test_deleting_account_does_not_reuse_number(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'employee_code' => 'z001']));
        $this->createEmployee('one@example.test')->assertSessionHasNoErrors();
        $user = User::where('email', 'one@example.test')->first();
        $this->delete('/admin/users/'.$user->id, ['confirm_code' => $user->employee_code])->assertSessionHasNoErrors();
        $this->createEmployee('two@example.test')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'two@example.test', 'employee_code' => 'a002']);
    }

    public function test_invalid_form_does_not_consume_id_and_sequence_exceeds_999(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'employee_code' => 'z001']));
        DB::table('employee_code_sequences')->where('id', 1)->update(['last_number' => 999]);
        $this->createEmployee('invalid')->assertSessionHasErrors('email');
        $this->assertEquals(999, DB::table('employee_code_sequences')->value('last_number'));
        $this->createEmployee('one@example.test')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'one@example.test', 'employee_code' => 'a1000']);
    }
}
