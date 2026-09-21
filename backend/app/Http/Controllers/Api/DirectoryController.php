<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentType;
use App\Enums\EventType;
use App\Enums\OfficerGrade;
use App\Enums\ServiceStatus;
use App\Enums\SignatoryCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DsDivision;
use App\Models\GnDivision;
use App\Models\Officer;
use App\Models\ServiceHistory;
use App\Models\Signatory;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DirectoryController extends Controller
{
    private const MODELS = ['officers' => Officer::class, 'documents' => Document::class, 'service-histories' => ServiceHistory::class, 'signatories' => Signatory::class, 'users' => User::class];

    public function __construct(private AuditLogService $audit)
    {
    }

    private function authorizeResource(Request $request, string $resource, bool $write = false): void
    {
        abort_unless(isset(self::MODELS[$resource]), 404);
        $user = $request->user();
        if ($user->isMinistryHead()) abort_if($write || ! in_array($resource, ['officers', 'service-histories']), 403);
        if ($resource === 'users') {
            abort_unless($user->isMainAdmin(), 403);
        } elseif ($resource === 'signatories') {
            abort_unless($user->isMainAdmin() || $user->isDistrictAdmin(), 403);
        } elseif ($write) {
            abort_if($user->isOfficer(), 403);
        }
    }

    private function query(Request $request, string $resource)
    {
        $this->authorizeResource($request, $resource);
        $user = $request->user();

        return match ($resource) {
            'officers' => AccessScope::officers($user)->with('user', 'district', 'dsDivision', 'gnDivision'),
            'documents', 'service-histories' => self::MODELS[$resource]::with('officer')->whereIn('officer_id', AccessScope::officers($user)->select('id')),
            'signatories' => Signatory::query()->when(! $user->isMainAdmin(), fn ($q) => $q->where('district_id', $user->district_id ?? -1)),
            'users' => User::query()->with('dsDivision')->addSelect(['last_login' => \App\Models\AuditLog::select('created_at')->whereColumn('user_id', 'users.id')->where('action', 'auth.login')->latest()->limit(1)]),
        };
    }

    public function index(Request $request, string $resource)
    {
        $query = $this->query($request, $resource);
        if ($request->filled('search')) {
            $column = match ($resource) {
                'officers' => 'full_name_en', 'users' => 'name', 'signatories' => 'officer_name', default => 'ref_no'
            };
            $search = substr((string) $request->input('search'), 0, 150);
            $query->where(fn ($q) => $q->where($column, 'like', "%{$search}%")->when($resource === 'officers', fn ($q) => $q->orWhere('nic_no', 'like', "%{$search}%")));
        }

        $filters = match ($resource) {
            'officers' => ['current_district_id', 'current_ds_division_id', 'current_gn_division_id', 'current_grade', 'service_status', 'designation'],
            'documents' => ['officer_id', 'document_type', 'issuing_authority'],
            'service-histories' => ['officer_id', 'event_type'],
            'users' => ['role', 'status'],
            default => [],
        };
        foreach ($filters as $column) {
            if ($request->filled($column)) $query->where($column, $request->input($column));
        }
        if ($resource === 'officers' && $request->filled('nic_no')) $query->where('nic_no', 'like', '%'.substr($request->string('nic_no'), 0, 30).'%');
        if ($resource === 'documents') {
            if ($request->filled('ref_no')) $query->where('ref_no', 'like', '%'.substr($request->string('ref_no'), 0, 100).'%');
            foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
                if ($request->filled($key)) { $request->validate([$key => 'date']); $query->whereDate('issue_date', $operator, $request->input($key)); }
            }
            if (in_array($request->input('format'), ['pdf', 'png', 'jpg', 'jpeg'])) $query->where('file_path', 'like', '%.'.$request->input('format'));
        }
        if ($request->filled('export')) {
            $request->validate(['export' => 'required|in:xlsx,pdf']);
            abort_unless(in_array($resource, ['officers', 'documents']), 422);
            abort_if((clone $query)->count() > 5000, 422, 'Narrow your filters to 5,000 records or fewer before exporting.');
            $records = $query->latest()->get();
            $rows = $resource === 'officers'
                ? [['Officer ID', 'Name', 'NIC', 'Designation', 'DS Division', 'GN Division', 'Grade', 'Status']]
                : [['Document Type', 'Officer', 'Reference', 'Issued Date', 'Issuing Authority']];
            foreach ($records as $row) {
                $rows[] = $resource === 'officers'
                    ? [$row->id, $row->full_name_en, $row->nic_no, $row->designation, $row->dsDivision?->name_en, $row->gnDivision?->name_en, $row->getRawOriginal('current_grade'), $row->service_status]
                    : [$row->getRawOriginal('document_type'), $row->officer?->full_name_en, $row->ref_no, $row->getRawOriginal('issue_date'), $row->issuing_authority];
            }
            return app(\App\Services\TabularExportService::class)->download($rows, $request->input('export'), $resource);
        }
        return response()->json($query->latest()->paginate(min(100, max(1, (int) $request->input('per_page', 20)))));
    }

    public function dashboard(Request $request)
    {
        $officers = AccessScope::officers($request->user());

        return response()->json([
            'total_officers' => (clone $officers)->count(),
            'pending_officers' => (clone $officers)->whereHas('user', fn ($q) => $q->where('status', 'pending_verification'))->count(),
            'documents' => Document::whereIn('officer_id', (clone $officers)->select('id'))->count(),
            'confirmed_officers' => (clone $officers)->where('confirmation_status', 'confirmed')->count(),
            'profile' => $request->user()->isOfficer() ? (clone $officers)->first() : null,
            'recent_history' => ServiceHistory::with('officer')->whereIn('officer_id', (clone $officers)->select('id'))->latest('effective_date')->limit(8)->get(),
        ]);
    }

    public function metadata(Request $request)
    {
        $options = fn ($enum) => array_map(fn ($item) => ['value' => $item->value, 'label' => $item->label()], $enum::cases());

        return response()->json([
            'document_types' => $options(DocumentType::class), 'event_types' => $options(EventType::class),
            'grades' => $options(OfficerGrade::class), 'service_statuses' => $options(ServiceStatus::class),
            'categories' => $options(SignatoryCategory::class), 'roles' => $options(UserRole::class), 'statuses' => $options(UserStatus::class),
            'signatories' => $request->user()->isOfficer() ? [] : Signatory::where('is_active', true)->get(['id', 'officer_name', 'category']),
        ]);
    }

    public function store(Request $request, string $resource)
    {
        return $this->save($request, $resource);
    }

    public function update(Request $request, string $resource, int $id)
    {
        return $this->save($request, $resource, $id);
    }

    private function save(Request $request, string $resource, ?int $id = null)
    {
        $this->authorizeResource($request, $resource, true);
        $record = $id ? $this->query($request, $resource)->findOrFail($id) : new (self::MODELS[$resource]);
        $oldValues = $record->exists ? $record->getAttributes() : null;
        $rules = match ($resource) {
            'officers' => [
                'designation' => 'nullable|string|max:150', 'contact_email' => 'nullable|email|max:255', 'mobile_phone' => 'nullable|string|max:30',
                'dependents' => 'nullable|array|max:50', 'dependents.*.name' => 'required|string|max:150',
                'dependents.*.relationship' => 'required|string|max:100', 'dependents.*.dob' => 'nullable|date|before_or_equal:today',
                'nic_no' => ['required', 'string', 'max:15', Rule::unique('officers')->ignore($id), Rule::unique('users')->ignore($record->user_id)],
                'full_name_en' => 'required|string|max:150', 'full_name_si' => 'nullable|string|max:150', 'full_name_ta' => 'nullable|string|max:150',
                'dob' => 'nullable|date|before:today', 'gender' => 'required|in:male,female,Male,Female', 'medium' => 'required|in:si,ta,en,Sinhala,Tamil,English',
                'address_line1' => 'nullable|string|max:150', 'address_line2' => 'nullable|string|max:150', 'address_line3' => 'nullable|string|max:150',
                'spouse_name' => 'nullable|string|max:150', 'dependants_count' => 'nullable|integer|min:0|max:50',
                'emergency_contact_name' => 'nullable|string|max:150', 'emergency_contact_relationship' => 'nullable|string|max:100',
                'emergency_contact_phone' => 'nullable|string|max:30',
                'first_appointment_date' => 'nullable|date', 'appointment_date' => 'nullable|date', 'confirmation_date' => 'nullable|date',
                'current_grade' => ['required', Rule::enum(OfficerGrade::class)], 'service_status' => ['required', Rule::enum(ServiceStatus::class)],
                'confirmation_status' => 'nullable|in:pending,confirmed',
                'current_district_id' => 'required|exists:districts,id', 'current_ds_division_id' => 'required|exists:ds_divisions,id', 'current_gn_division_id' => 'nullable|exists:gn_divisions,id',
            ],
            'users' => [
                'name' => 'required|string|max:150', 'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
                'nic_no' => ['nullable', 'string', 'max:15', Rule::unique('users')->ignore($id)], 'phone' => 'nullable|string|max:15',
                'password' => ($id ? 'nullable' : 'required').'|string|min:8', 'role' => ['required', Rule::enum(UserRole::class)], 'status' => ['required', Rule::enum(UserStatus::class)],
                'district_id' => 'nullable|exists:districts,id', 'ds_division_id' => 'nullable|exists:ds_divisions,id',
            ],
            'signatories' => [
                'officer_name' => 'required|string|max:150', 'designation' => 'required|string|max:150', 'category' => ['required', Rule::enum(SignatoryCategory::class)],
                'is_active' => 'required|boolean', 'district_id' => 'nullable|exists:districts,id', 'ds_division_id' => 'nullable|exists:ds_divisions,id',
                'file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            ],
            'documents' => [
                'issuing_authority' => 'nullable|string|max:255',
                'officer_id' => 'required|exists:officers,id', 'document_type' => ['required', Rule::enum(DocumentType::class)],
                'ref_no' => 'nullable|string|max:100', 'issue_date' => 'required|date', 'file' => ($id ? 'nullable' : 'required').'|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ],
            'service-histories' => [
                'officer_id' => 'required|exists:officers,id', 'event_type' => ['required', Rule::enum(EventType::class)],
                'ref_no' => 'nullable|string|max:100', 'effective_date' => 'required|date', 'description' => 'nullable|string|max:5000',
                'old_value' => 'nullable|string|max:255', 'new_value' => 'nullable|string|max:255',
            ],
        };
        $data = $request->validate($rules);
        if (isset($data['officer_id'])) {
            AccessScope::officer($request->user(), (int) $data['officer_id']);
        }
        if (in_array($resource, ['officers', 'users', 'signatories'])) {
            $prefix = $resource === 'officers' ? 'current_' : '';
            $district = $data[$prefix.'district_id'] ?? null;
            $division = $data[$prefix.'ds_division_id'] ?? null;
            if ($division && ! DsDivision::whereKey($division)->where('district_id', $district)->exists()) {
                throw ValidationException::withMessages([$prefix.'ds_division_id' => 'Select a division in the selected district.']);
            }
            if (! $request->user()->isMainAdmin()) {
                abort_unless((int) $district === (int) $request->user()->district_id && $district, 403);
                if ($request->user()->isDivisionalAdmin()) {
                    abort_unless((int) $division === (int) $request->user()->ds_division_id && $division, 403);
                }
            }
            if ($resource === 'officers' && ! empty($data['current_gn_division_id']) && ! GnDivision::whereKey($data['current_gn_division_id'])->where('ds_division_id', $division)->exists()) {
                throw ValidationException::withMessages(['current_gn_division_id' => 'Select a GN division in the selected DS division.']);
            }
        }
        if ($resource === 'users') {
            if (empty($data['password'])) {
                unset($data['password']);
            }
            if ($id === $request->user()->id && ($data['role'] !== 'main_admin' || $data['status'] !== 'active')) {
                throw ValidationException::withMessages(['role' => 'You cannot deactivate or demote your own administrator account.']);
            }
        }
        $file = $data['file'] ?? null;
        unset($data['file']);
        if ($file) {
            $column = $resource === 'signatories' ? 'digital_signature_path' : 'file_path';
            $data[$column] = $file->store($resource === 'signatories' ? 'signatures' : 'documents', $resource === 'signatories' ? 'public' : 'local');
        }
        if (! $id && $resource === 'documents') {
            $data['generated_by'] = $request->user()->id;
        }
        if (! $id && $resource === 'service-histories') {
            $data['created_by'] = $request->user()->id;
        }
        DB::transaction(function () use ($record, $data, $resource, $oldValues, $request) {
            if ($resource === 'officers' && ! $record->exists) {
                $user = User::create([
                    'name' => $data['full_name_en'],
                    'email' => strtolower($data['nic_no']).'@'.config('app.pending_email_domain', 'pending.gn.local'),
                    'password' => \Illuminate\Support\Str::random(32),
                    'nic_no' => $data['nic_no'],
                    'role' => UserRole::Officer,
                    'status' => UserStatus::Active,
                    'district_id' => $data['current_district_id'],
                    'ds_division_id' => $data['current_ds_division_id'],
                ]);
                $data['user_id'] = $user->id;
            }
            $record->fill($data)->save();
            if ($resource === 'officers' && $oldValues) {
                foreach (['designation', 'current_grade', 'current_district_id', 'current_ds_division_id', 'current_gn_division_id'] as $field) {
                    if (array_key_exists($field, $data) && (string) ($oldValues[$field] ?? '') !== (string) $data[$field]) {
                        ServiceHistory::create([
                            'officer_id' => $record->id,
                            'event_type' => 'correction',
                            'effective_date' => now()->toDateString(),
                            'description' => 'Profile correction: '.$field.' (recorded on edit date)',
                            'old_value' => $oldValues[$field] ?? null, 'new_value' => $data[$field],
                            'created_by' => $request->user()->id,
                        ]);
                    }
                }
            }
            if ($resource === 'officers' && $record->user) {
                $record->user->update([
                    'name' => $data['full_name_en'], 'nic_no' => $data['nic_no'],
                    'district_id' => $data['current_district_id'], 'ds_division_id' => $data['current_ds_division_id'],
                ]);
            }
            if ($resource === 'users') {
                Role::findOrCreate($record->role->value, 'web');
                $record->syncRoles([$record->role->value]);
            }
        });

        $this->audit->record($request->user(), $id ? $resource.'.updated' : $resource.'.created', $record, $oldValues, $record->getAttributes());

        return response()->json($record->fresh(), $id ? 200 : 201);
    }

    public function destroy(Request $request, string $resource, int $id)
    {
        $this->authorizeResource($request, $resource, true);
        abort_unless($request->user()->isMainAdmin(), 403);
        abort_if($resource === 'users' && $id === $request->user()->id, 422, 'You cannot delete your own account.');
        $record = $this->query($request, $resource)->findOrFail($id);
        $oldValues = $record->getAttributes();
        $record->delete();
        $this->audit->record($request->user(), $resource.'.deleted', $record, $oldValues);

        return response()->json(['message' => 'Record deleted.']);
    }

    public function verify(Request $request, int $officer)
    {
        abort_if($request->user()->isOfficer() || $request->user()->isMinistryHead(), 403);
        $record = AccessScope::officer($request->user(), $officer);
        abort_unless($record->user, 422, 'This officer has no linked user account.');
        $record->user->update(['status' => UserStatus::Active, 'email_verified_at' => now()]);
        $this->audit->record($request->user(), 'officer.verified', $record, ['status' => UserStatus::PendingVerification->value], ['status' => UserStatus::Active->value]);

        return response()->json(['message' => 'Officer approved and activated.']);
    }

    public function download(Request $request, Document $document)
    {
        abort_if($request->user()->isMinistryHead(), 403);
        AccessScope::officer($request->user(), $document->officer_id);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404, 'Document file not found.');

        return $request->boolean('inline') ? Storage::disk('local')->response($document->file_path, null, ['X-Content-Type-Options' => 'nosniff']) : Storage::disk('local')->download($document->file_path);
    }
}
