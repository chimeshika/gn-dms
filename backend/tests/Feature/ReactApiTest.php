<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\Document;
use App\Models\DsDivision;
use App\Models\Letter;
use App\Models\LetterBatch;
use App\Models\Officer;
use App\Models\ServiceHistory;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReactApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => UserRole::MainAdmin, 'status' => UserStatus::Active]);
    }

    private function officer(array $attributes = []): Officer
    {
        return Officer::create($attributes + ['nic_no' => (string) random_int(100000000, 999999999).'V', 'full_name_en' => 'Test Officer', 'current_grade' => 'grade_iii', 'service_status' => 'appointed']);
    }

    private function location(): array
    {
        $district = District::create(['name_en' => 'Test district', 'code' => (string) random_int(100, 999)]);
        $division = DsDivision::create(['name_en' => 'Test division', 'code' => (string) random_int(100, 999), 'district_id' => $district->id]);

        return [$district, $division];
    }

    public function test_guest_session_and_protected_routes_return_json(): void
    {
        $this->getJson('/api/session')->assertOk()->assertJsonPath('user', null)->assertJsonStructure(['csrf_token']);
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/does-not-exist')->assertNotFound();
    }

    public function test_active_login_and_logout_and_pending_rejection(): void
    {
        $user = $this->admin();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->assertJsonPath('user.id', $user->id);
        $this->getJson('/api/dashboard')->assertOk();
        $this->postJson('/api/logout')->assertOk();
        $this->assertGuest();
        $user->update(['status' => UserStatus::PendingVerification]);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_registration_creates_pending_account_without_authenticating(): void
    {
        [$district, $division] = $this->location();
        $data = ['nic_no' => '921234567v', 'full_name_en' => 'Registered Officer', 'email' => 'registration@example.test', 'password' => 'secure-password', 'password_confirmation' => 'secure-password', 'dob' => '1992-01-01', 'gender' => 'male', 'medium' => 'en', 'district_id' => $district->id, 'ds_division_id' => $division->id];
        $this->postJson('/api/register', $data)->assertCreated();
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => $data['email'], 'nic_no' => '921234567V', 'status' => 'pending_verification']);
        $this->assertDatabaseHas('officers', ['nic_no' => '921234567V', 'current_ds_division_id' => $division->id]);
        $this->postJson('/api/register', $data)->assertUnprocessable();
    }

    public function test_officer_cannot_read_other_profiles_or_write_admin_records(): void
    {
        $user = $this->admin(['role' => UserRole::Officer]);
        $mine = $this->officer(['user_id' => $user->id]);
        $this->officer();
        $this->actingAs($user)->getJson('/api/records/officers')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
        $this->postJson('/api/records/officers', [])->assertForbidden();
        $this->getJson('/api/records/users')->assertForbidden();
        $this->getJson('/api/batches')->assertForbidden();
        $user->update(['status' => UserStatus::Inactive]);
        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_divisional_scope_and_verification(): void
    {
        [$district, $division] = $this->location();
        $user = $this->admin(['role' => UserRole::DivisionalAdmin, 'district_id' => $district->id, 'ds_division_id' => $division->id]);
        $pending = $this->admin(['role' => UserRole::Officer, 'status' => UserStatus::PendingVerification]);
        $mine = $this->officer(['user_id' => $pending->id, 'current_district_id' => $district->id, 'current_ds_division_id' => $division->id]);
        $other = $this->officer();
        $this->actingAs($user)->getJson('/api/records/officers')->assertJsonCount(1, 'data');
        $this->postJson('/api/officers/'.$other->id.'/verify')->assertNotFound();
        $this->postJson('/api/officers/'.$mine->id.'/verify')->assertOk();
        $this->assertSame(UserStatus::Active, $pending->fresh()->status);
    }

    public function test_document_upload_and_download_are_scoped(): void
    {
        Storage::fake('local');
        $officer = $this->officer();
        $this->actingAs($this->admin())->post('/api/records/documents', [
            'officer_id' => $officer->id, 'document_type' => 'certificate', 'issue_date' => '2026-09-08',
            'file' => UploadedFile::fake()->createWithContent('document.pdf', '%PDF-1.4 test document'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $document = Document::firstOrFail();
        $this->get('/api/documents/'.$document->id.'/download')->assertOk();
        $this->actingAs($this->admin(['role' => UserRole::Officer]))->get('/api/documents/'.$document->id.'/download')->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'documents.created', 'auditable_id' => $document->id]);
    }

    public function test_batch_generation_editing_and_finalization_are_idempotent(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $officer = $this->officer();
        $this->actingAs($admin);
        $batch = $this->postJson('/api/batches', ['name' => 'Confirmation batch', 'document_type' => 'confirmation', 'letter_date' => '2026-09-08'])->assertCreated()->json();
        $this->postJson('/api/batches/'.$batch['id'].'/generate', ['officer_ids' => [$officer->id]])->assertOk();
        $letter = Letter::firstOrFail();
        $this->putJson('/api/letters/'.$letter->id, ['ref_no' => 'REF/1', 'subject' => 'Edited subject', 'body' => '<p>Edited letter.</p>', 'cc_to' => ['Recipient']])->assertOk();
        $this->postJson('/api/batches/'.$batch['id'].'/generate', ['officer_ids' => [$officer->id]])->assertOk();
        $this->assertSame('<p>Edited letter.</p>', $letter->fresh()->body);
        $this->mock(PdfService::class, function ($mock) {
            $mock->shouldReceive('store')->once()->andReturnUsing(function () {
                Storage::disk('local')->put('letters/test.pdf', '%PDF-1.4 archived');

                return 'letters/test.pdf';
            });
        });
        $this->postJson('/api/letters/'.$letter->id.'/finalize')->assertOk();
        $this->postJson('/api/letters/'.$letter->id.'/finalize')->assertOk();
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('service_histories', 1);
        $this->assertDatabaseCount('audit_logs', 3);
        $this->assertSame('grade_iii', $officer->fresh()->current_grade->value);
        $this->assertSame('confirmed', $officer->fresh()->service_status);
        $this->assertSame('appointed', ServiceHistory::first()->old_value);
        $this->putJson('/api/letters/'.$letter->id, [])->assertConflict();
        $this->deleteJson('/api/letters/'.$letter->id)->assertConflict();
        $this->get('/api/letters/'.$letter->id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_another_administrator_cannot_access_a_private_batch(): void
    {
        [$district, $division] = $this->location();
        $owner = $this->admin();
        $batch = LetterBatch::create(['name' => 'Private', 'document_type' => 'appointment', 'created_by' => $owner->id]);
        $other = $this->admin(['role' => UserRole::DivisionalAdmin, 'district_id' => $district->id, 'ds_division_id' => $division->id]);
        $this->actingAs($other)->getJson('/api/batches/'.$batch->id)->assertNotFound();
    }

    public function test_csv_import_accepts_canonical_headers_and_preserves_special_grade(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $batch = LetterBatch::create(['name' => 'Import', 'document_type' => 'appointment', 'created_by' => $admin->id]);
        $file = UploadedFile::fake()->createWithContent('officers.csv', "nic_no,full_name_en,current_grade,address_line1,dob\n921234567V,Imported Officer,special,Main Street,45000\n");
        $this->actingAs($admin)->post('/api/batches/'.$batch->id.'/import', ['file' => $file], ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseHas('officers', ['nic_no' => '921234567V', 'current_grade' => 'special', 'address_line1' => 'Main Street']);
        $this->assertSame('2023-03-15', Officer::first()->dob->format('Y-m-d'));
        $this->assertDatabaseCount('letters', 1);
    }

    public function test_real_pdf_generation_for_each_document_type(): void
    {
        $admin = $this->admin();
        $officer = $this->officer();
        $this->actingAs($admin);
        foreach (DocumentType::cases() as $type) {
            $batch = LetterBatch::create(['name' => 'Test '.$type->value, 'document_type' => $type, 'letter_date' => '2026-09-08', 'created_by' => $admin->id]);
            $this->postJson('/api/batches/'.$batch->id.'/generate', ['officer_ids' => [$officer->id]])->assertOk();
            $letter = $batch->letters()->firstOrFail();
            $response = $this->get('/api/letters/'.$letter->id.'/pdf');
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    public function test_administrator_record_creation_links_accounts_and_supports_edits(): void
    {
        [$district, $division] = $this->location();
        $this->actingAs($this->admin());
        $data = ['nic_no' => '901234567V', 'full_name_en' => 'Created Officer', 'gender' => 'male', 'medium' => 'en', 'current_grade' => 'grade_iii', 'service_status' => 'appointed', 'current_district_id' => $district->id, 'current_ds_division_id' => $division->id];
        $officer = $this->postJson('/api/records/officers', $data)->assertCreated()->json();
        $this->assertNotNull($officer['user_id']);
        $data['full_name_en'] = 'Updated Officer';
        $this->putJson('/api/records/officers/'.$officer['id'], $data)->assertOk();
        $this->assertDatabaseHas('users', ['id' => $officer['user_id'], 'name' => 'Updated Officer']);
        $this->postJson('/api/records/service-histories', ['officer_id' => $officer['id'], 'event_type' => 'appointment', 'effective_date' => '2026-09-08'])->assertCreated();
        $this->postJson('/api/records/signatories', ['officer_name' => 'Test Secretary', 'designation' => 'Secretary', 'category' => 'secretary', 'is_active' => true])->assertCreated();
        $this->postJson('/api/records/users', ['name' => 'Another Admin', 'email' => 'newadmin@example.test', 'password' => 'secure-password', 'role' => 'district_admin', 'status' => 'active', 'district_id' => $district->id])->assertCreated();
        $this->assertTrue(User::where('email', 'newadmin@example.test')->first()->hasRole('district_admin'));
    }
}
