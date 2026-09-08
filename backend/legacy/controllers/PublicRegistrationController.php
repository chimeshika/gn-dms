<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\DsDivision;
use App\Models\GnDivision;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function create(): View
    {
        return view('registration.create', [
            'districts' => District::orderBy('name_en')->get(),
            'dsDivisions' => DsDivision::orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nic_no' => ['required', 'string', 'max:15'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'full_name_en' => ['required', 'string', 'max:150'],
            'full_name_si' => ['nullable', 'string', 'max:150'],
            'full_name_ta' => ['nullable', 'string', 'max:150'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'medium' => ['required', 'in:si,ta,en'],
            'phone' => ['nullable', 'string', 'max:15'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_line3' => ['nullable', 'string', 'max:255'],
            'district_id' => ['required', 'exists:districts,id'],
            'ds_division_id' => ['required', 'exists:ds_divisions,id'],
            'gn_division_id' => ['nullable', 'exists:gn_divisions,id'],
            'first_appointment_date' => ['nullable', 'date'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => $data['full_name_en'],
                'email' => $data['email'],
                'password' => $data['password'],
                'nic_no' => strtoupper($data['nic_no']),
                'phone' => $data['phone'] ?? null,
                'role' => UserRole::Officer,
                'status' => UserStatus::PendingVerification,
                'ds_division_id' => $data['ds_division_id'],
                'district_id' => $data['district_id'],
            ]);

            Officer::create([
                'user_id' => $user->id,
                'nic_no' => strtoupper($data['nic_no']),
                'full_name_si' => $data['full_name_si'] ?? null,
                'full_name_ta' => $data['full_name_ta'] ?? null,
                'full_name_en' => $data['full_name_en'],
                'dob' => $data['dob'],
                'gender' => $data['gender'],
                'medium' => $data['medium'],
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'address_line3' => $data['address_line3'] ?? null,
                'first_appointment_date' => $data['first_appointment_date'] ?? null,
                'current_grade' => 'grade_iii',
                'current_district_id' => $data['district_id'],
                'current_ds_division_id' => $data['ds_division_id'],
                'current_gn_division_id' => $data['gn_division_id'] ?? null,
                'confirmation_status' => 'pending',
                'service_status' => 'appointed',
            ]);

            return $user;
        });

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('status', 'Registration submitted. Your account is pending verification by the Divisional Secretariat.');
    }

    public function dsDivisions(Request $request)
    {
        $divisions = DsDivision::where('district_id', $request->integer('district_id'))
            ->orderBy('name_en')
            ->get(['id', 'name_en']);

        return response()->json($divisions);
    }

    public function gnDivisions(Request $request)
    {
        $divisions = GnDivision::where('ds_division_id', $request->integer('ds_division_id'))
            ->orderBy('name_en')
            ->get(['id', 'code', 'name_en']);

        return response()->json($divisions);
    }
}
