@extends('layouts.app')

@section('title', 'New Letter Batch')

@section('content')
    <div class="max-w-4xl">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">New Letter Batch</h1>
            <p class="mt-1 text-sm text-slate-600">
                Enter the batch-level (global) information. Officer-specific details are filled per letter from the registry.
            </p>

            <form method="POST" action="{{ route('letters.store') }}" class="mt-8 space-y-8">
                @csrf

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Batch Details</legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700">Batch Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   placeholder="e.g. GN Appointment Batch 2026/01"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>

                        <div>
                            <label for="document_type" class="block text-sm font-medium text-slate-700">Document Type *</label>
                            <select name="document_type" id="document_type" required
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @foreach ($documentTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="my_ref_no" class="block text-sm font-medium text-slate-700">My Reference No</label>
                            <input type="text" name="my_ref_no" id="my_ref_no" value="{{ old('my_ref_no') }}"
                                   placeholder="e.g. GN/APP/2026"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="letter_date" class="block text-sm font-medium text-slate-700">Letter / Effective Date</label>
                            <input type="date" name="letter_date" id="letter_date" value="{{ old('letter_date', now()->toDateString()) }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="ds_division_id" class="block text-sm font-medium text-slate-700">Target DS Division</label>
                            <select name="ds_division_id" id="ds_division_id"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Any</option>
                                @foreach (\App\Models\DsDivision::orderBy('name_en')->get() as $div)
                                    <option value="{{ $div->id }}" @selected(old('ds_division_id') == $div->id)>{{ $div->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="district_id" class="block text-sm font-medium text-slate-700">Target District</label>
                            <select name="district_id" id="district_id"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Any</option>
                                @foreach (\App\Models\District::orderBy('name_en')->get() as $dist)
                                    <option value="{{ $dist->id }}" @selected(old('district_id') == $dist->id)>{{ $dist->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Approval / Reference Dates</legend>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label for="cabinet_app_no" class="block text-sm font-medium text-slate-700">Cabinet Approval No</label>
                            <input type="text" name="cabinet_app_no" id="cabinet_app_no" value="{{ old('cabinet_app_no') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="cabinet_app_date" class="block text-sm font-medium text-slate-700">Cabinet Approval Date</label>
                            <input type="date" name="cabinet_app_date" id="cabinet_app_date" value="{{ old('cabinet_app_date') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="exam_date" class="block text-sm font-medium text-slate-700">DS Exam Date</label>
                            <input type="date" name="exam_date" id="exam_date" value="{{ old('exam_date') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="probation_effective_date" class="block text-sm font-medium text-slate-700">Probation Effective Date</label>
                            <input type="date" name="probation_effective_date" id="probation_effective_date" value="{{ old('probation_effective_date') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="training_complete_date" class="block text-sm font-medium text-slate-700">Training Completion Date</label>
                            <input type="date" name="training_complete_date" id="training_complete_date" value="{{ old('training_complete_date') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500">Letter Template</legend>
                    <div>
                        <p class="mb-2 text-xs text-slate-500">
                            Placeholders: <code>{officer_name}</code> <code>{nic_no}</code> <code>{grade}</code> <code>{new_grade}</code>
                            <code>{district}</code> <code>{ds_division}</code> <code>{gn_division}</code> <code>{letter_date}</code>
                            <code>{cabinet_app_no}</code> <code>{cabinet_app_date}</code> <code>{exam_date}</code>
                            <code>{probation_effective_date}</code> <code>{training_complete_date}</code>
                        </p>
                        <textarea name="content_template" id="content_template" rows="10"
                                  class="block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('content_template') }}</textarea>
                    </div>
                </fieldset>

                <button type="submit" class="btn-primary justify-center px-8 py-3">Create Batch</button>
            </form>
        </div>
    </div>
@endsection
