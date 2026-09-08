<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\DsDivision;
use App\Models\GnDivision;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['user' => $request->user(), 'csrf_token' => csrf_token()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        if ($request->user()->status !== UserStatus::Active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Your account is pending verification or inactive. Contact your Divisional Secretariat.']);
        }
        $request->session()->regenerate();

        return $this->show($request);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.', 'csrf_token' => csrf_token()]);
    }

    public function locations(Request $request)
    {
        return response()->json([
            'districts' => District::orderBy('name_en')->get(['id', 'name_en']),
            'ds_divisions' => DsDivision::when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))->orderBy('name_en')->get(['id', 'name_en', 'district_id']),
            'gn_divisions' => $request->filled('ds_division_id') ? GnDivision::where('ds_division_id', $request->integer('ds_division_id'))->orderBy('name_en')->get(['id', 'name_en', 'ds_division_id']) : [],
        ]);
    }

    public function register(Request $request)
    {
        $request->merge(['nic_no' => strtoupper(trim((string) $request->input('nic_no')))]);
        $data = $request->validate([
            'nic_no' => ['required', 'string', 'max:15', 'unique:users,nic_no', 'unique:officers,nic_no'],
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'full_name_en' => 'required|string|max:150',
            'full_name_si' => 'nullable|string|max:150', 'full_name_ta' => 'nullable|string|max:150',
            'dob' => 'required|date|before:today', 'gender' => 'required|in:male,female', 'medium' => 'required|in:si,ta,en',
            'phone' => 'nullable|string|max:15',
            'address_line1' => 'nullable|string|max:150', 'address_line2' => 'nullable|string|max:150', 'address_line3' => 'nullable|string|max:150',
            'district_id' => 'required|exists:districts,id',
            'ds_division_id' => ['required', Rule::exists('ds_divisions', 'id')->where('district_id', $request->input('district_id'))],
            'gn_division_id' => ['nullable', Rule::exists('gn_divisions', 'id')->where('ds_division_id', $request->input('ds_division_id'))],
            'first_appointment_date' => 'nullable|date',
        ]);
        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['full_name_en'], 'email' => $data['email'], 'password' => $data['password'],
                'nic_no' => $data['nic_no'], 'phone' => $data['phone'] ?? null, 'role' => UserRole::Officer,
                'status' => UserStatus::PendingVerification, 'district_id' => $data['district_id'], 'ds_division_id' => $data['ds_division_id'],
            ]);
            $profile = collect($data)->except(['email', 'password', 'phone', 'district_id', 'ds_division_id', 'gn_division_id'])->all();
            Officer::create($profile + [
                'user_id' => $user->id, 'current_grade' => 'grade_iii', 'confirmation_status' => 'pending', 'service_status' => 'appointed',
                'current_district_id' => $data['district_id'], 'current_ds_division_id' => $data['ds_division_id'], 'current_gn_division_id' => $data['gn_division_id'] ?? null,
            ]);
        });

        return response()->json(['message' => 'Registration submitted. Your Divisional Secretariat must verify your account before you can sign in.'], 201);
    }
}
