@extends('layouts.app')

@section('title', 'Officer Registration')

@section('content')
    <div class="mx-auto max-w-3xl" x-data="registrationForm()">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Officer Self-Registration</h1>
            <p class="mt-1 text-sm text-slate-600">
                Your application will be reviewed by the Divisional Secretariat of the DS Division you select below.
                You will be able to sign in only after your identity is verified.
            </p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-6">
                @csrf

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Identity</legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="nic_no" class="block text-sm font-medium text-slate-700">NIC Number</label>
                            <input type="text" name="nic_no" id="nic_no" value="{{ old('nic_no') }}" required maxlength="15"
                                   placeholder="e.g. 901234567V"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label for="dob" class="block text-sm font-medium text-slate-700">Date of Birth</label>
                            <input type="date" name="dob" id="dob" value="{{ old('dob') }}" required
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="full_name_en" class="block text-sm font-medium text-slate-700">Full Name (English) *</label>
                            <input type="text" name="full_name_en" id="full_name_en" value="{{ old('full_name_en') }}" required
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="full_name_si" class="block text-sm font-medium text-slate-700">Full Name (Sinhala)</label>
                            <input type="text" name="full_name_si" id="full_name_si" value="{{ old('full_name_si') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="full_name_ta" class="block text-sm font-medium text-slate-700">Full Name (Tamil)</label>
                            <input type="text" name="full_name_ta" id="full_name_ta" value="{{ old('full_name_ta') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="gender" class="block text-sm font-medium text-slate-700">Gender</label>
                            <select name="gender" id="gender" required
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label for="medium" class="block text-sm font-medium text-slate-700">Preferred Medium</label>
                            <select name="medium" id="medium" required
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="si">Sinhala</option>
                                <option value="ta">Tamil</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <div>
                            <label for="first_appointment_date" class="block text-sm font-medium text-slate-700">First Appointment Date</label>
                            <input type="date" name="first_appointment_date" id="first_appointment_date" value="{{ old('first_appointment_date') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Contact & Address</legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" maxlength="15"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <div>
                        <label for="address_line1" class="block text-sm font-medium text-slate-700">Address</label>
                        <input type="text" name="address_line1" id="address_line1" value="{{ old('address_line1') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <input type="text" name="address_line2" id="address_line2" value="{{ old('address_line2') }}"
                                   placeholder="Address line 2"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <input type="text" name="address_line3" id="address_line3" value="{{ old('address_line3') }}"
                                   placeholder="Address line 3 (city)"
                                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Station & Jurisdiction</legend>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="district_id" class="block text-sm font-medium text-slate-700">District</label>
                            <select name="district_id" id="district_id" required x-model="districtId" @change="loadDsDivisions()"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Select district</option>
                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name_en }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="ds_division_id" class="block text-sm font-medium text-slate-700">DS Division</label>
                            <select name="ds_division_id" id="ds_division_id" required x-model="dsDivisionId" @change="loadGnDivisions()"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Select DS Division</option>
                                <template x-for="div in dsDivisions" :key="div.id">
                                    <option :value="div.id" x-text="div.name_en"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label for="gn_division_id" class="block text-sm font-medium text-slate-700">GN Division (optional)</label>
                            <select name="gn_division_id" id="gn_division_id" x-model="gnDivisionId"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Select GN Division</option>
                                <template x-for="div in gnDivisions" :key="div.id">
                                    <option :value="div.id" x-text="div.code + ' - ' + div.name_en"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Account Security</legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                            <input type="password" name="password" id="password" required minlength="8"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </fieldset>

                <button type="submit" class="btn-primary w-full justify-center py-3 text-base">Submit Registration</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function registrationForm() {
            return {
                districtId: '',
                dsDivisionId: '',
                gnDivisionId: '',
                dsDivisions: [],
                gnDivisions: [],
                async loadDsDivisions() {
                    this.dsDivisionId = '';
                    this.gnDivisionId = '';
                    this.gnDivisions = [];
                    if (!this.districtId) return;
                    const res = await fetch(`/api/ds-divisions?district_id=${this.districtId}`);
                    this.dsDivisions = await res.json();
                },
                async loadGnDivisions() {
                    this.gnDivisionId = '';
                    if (!this.dsDivisionId) return;
                    const res = await fetch(`/api/gn-divisions?ds_division_id=${this.dsDivisionId}`);
                    this.gnDivisions = await res.json();
                },
            };
        }
    </script>
@endpush
