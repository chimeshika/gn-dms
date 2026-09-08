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
            'officers' => AccessScope::officers($user)->with('user'),
            'documents', 'service-histories' => self::MODELS[$resource]::with('officer')->whereIn('officer_id', AccessScope::officers($user)->select('id')),
            'signatories' => Signatory::query()->when(! $user->isMainAdmin(), fn ($q) => $q->where('district_id', $user->district_id ?? -1)),
            'users' => User::query(),
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

        return response()->json($query->latest()->paginate(20));
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
                'nic_no' => ['required', 'string', 'max:15', Rule::unique('officers')->ignore($id), Rule::unique('users')->ignore($record->user_id)],
                'full_name_en' => 'required|string|max:150', 'full_name_si' => 'nullable|string|max:150', 'full_name_ta' => 'nullable|string|max:150',
                'dob' => 'nullable|date|before:today', 'gender' => 'required|in:male,female,Male,Female', 'medium' => 'required|in:si,ta,en,Sinhala,Tamil,English',
                'address_line1' => 'nullable|string|max:150', 'address_line2' => 'nullable|string|max:150', 'address_line3' => 'nullable|string|max:150',
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
                'officer_id' => 'required|exists:officers,id', 'document_type' => ['required', Rule::enum(DocumentType::class)],
                'ref_no' => 'nullable|string|max:100', 'issue_date' => 'required|date', 'file' => ($id ? 'nullable' : 'required').'|file|mimes:pdf|max:10240',
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
        DB::transaction(function () use ($record, $data, $resource) {
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
        abort_if($request->user()->isOfficer(), 403);
        $record = AccessScope::officer($request->user(), $officer);
        abort_unless($record->user, 422, 'This officer has no linked user account.');
        $record->user->update(['status' => UserStatus::Active, 'email_verified_at' => now()]);
        $this->audit->record($request->user(), 'officer.verified', $record, ['status' => UserStatus::PendingVerification->value], ['status' => UserStatus::Active->value]);

        return response()->json(['message' => 'Officer approved and activated.']);
    }

    public function download(Request $request, Document $document)
    {
        AccessScope::officer($request->user(), $document->officer_id);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404, 'Document file not found.');

        return Storage::disk('local')->download($document->file_path);
    }
}
