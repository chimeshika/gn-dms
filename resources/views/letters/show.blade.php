@extends('layouts.app')

@section('title', $batch->name)

@section('content')
    <div class="space-y-6">
        {{-- ── Batch Header ── --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $batch->name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">
                            {{ is_object($batch->document_type) && method_exists($batch->document_type, 'label') ? $batch->document_type->label() : ($batch->document_type->value ?? $batch->document_type ?? '-') }}
                        </span>
                        <span class="ml-2">My Ref: {{ $batch->my_ref_no ?? '—' }}</span>
                        <span class="ml-2">Letters: {{ $letters->count() }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($sampleLetter)
                        <a href="{{ route('letters.preview', $sampleLetter) }}" target="_blank" class="btn-primary">Sample Preview (PDF)</a>
                    @endif
                    @if ($letters->isNotEmpty())
                        <a href="{{ route('letters.bulk.pdf', $batch) }}" class="btn-success">Bulk PDF (ZIP)</a>
                    @endif
                    <a href="{{ route('letters.index') }}" class="btn-ghost">All Batches</a>
                </div>
            </div>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <dt class="text-xs font-medium text-slate-500">Cabinet Approval</dt>
                    <dd class="font-semibold text-slate-800">{{ $batch->cabinet_app_no ?? '—' }} / {{ $batch->cabinet_app_date?->format('d-m-Y') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <dt class="text-xs font-medium text-slate-500">Exam Date</dt>
                    <dd class="font-semibold text-slate-800">{{ $batch->exam_date?->format('d-m-Y') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <dt class="text-xs font-medium text-slate-500">Probation Effective</dt>
                    <dd class="font-semibold text-slate-800">{{ $batch->probation_effective_date?->format('d-m-Y') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <dt class="text-xs font-medium text-slate-500">Training Complete</dt>
                    <dd class="font-semibold text-slate-800">{{ $batch->training_complete_date?->format('d-m-Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- ── Import Section ── --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">1. Import Officer List</h2>
            <p class="mt-1 text-sm text-slate-600">
                Upload a CSV or Excel file (.csv, .xlsx, .xls) with a heading row.
            </p>

            <div class="mt-3 rounded-lg bg-slate-50 border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Required Excel Headers:</h3>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-xs font-mono">
                    <span class="rounded bg-amber-50 text-amber-800 px-2 py-1 border border-amber-200"><strong>nic_no</strong> <span class="text-amber-600">(required)</span></span>
                    <span class="rounded bg-amber-50 text-amber-800 px-2 py-1 border border-amber-200"><strong>full_name_en</strong> <span class="text-amber-600">(required)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">full_name_si</span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">full_name_ta</span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">dob <span class="text-slate-500">(Y.m.d)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">gender <span class="text-slate-500">(Mr/Mrs/Female/Male)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">medium <span class="text-slate-500">(sinhala/tamil/english)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">first_appointment_date <span class="text-slate-500">(Y.m.d)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">address_line1</span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">address_line2</span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">address_line3</span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">current_grade <span class="text-slate-500">(grade_iii/ii/i)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">district <span class="text-slate-500">(name or ID)</span></span>
                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-1 border border-slate-200">ds_division <span class="text-slate-500">(name or ID)</span></span>
                </div>
            </div>

            <form method="POST" action="{{ route('letters.import', $batch) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-4">
                @csrf
                <div class="flex-1">
                    <input type="file" name="file" id="file" required accept=".csv,.txt,.xlsx,.xls"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                </div>
                <button type="submit" class="btn-primary">Import Officers</button>
            </form>

            @if ($letters->isNotEmpty())
                <div class="mt-6">
                    <h3 class="font-semibold text-slate-900">Officers currently in batch</h3>
                    <p class="text-sm text-slate-600">The following {{ $letters->count() }} officer(s) are attached. Re-importing skips existing NICs.</p>
                </div>
            @endif
        </div>

        {{-- ── Generated Letters Table ── --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">2. Generated Letters</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Finalizing a letter applies the event-driven updates to the officer record, writes the service history entry
                        and archives the generated PDF as an official document.
                    </p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Officer</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">NIC</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Signatory</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($letters as $letter)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $letter->ref_no }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $letter->officer?->full_name_en }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $letter->officer?->nic_no }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($letter->signatory)
                                        <span class="block">{{ $letter->signatory->officer_name }}</span>
                                        <span class="text-xs text-slate-400">{{ $letter->signatory->designation }}</span>
                                    @else
                                        <span class="text-slate-400">No active signatory</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $letter->status === \App\Enums\LetterStatus::Final ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ is_object($letter->status) && method_exists($letter->status, 'label') ? $letter->status->label() : ($letter->status ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        {{-- 1. Preview Button --}}
                                        <a href="{{ route('letters.preview', $letter) }}" target="_blank" 
                                           class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                            Preview
                                        </a>

                                        {{-- 2. Print Button --}}
                                        <a href="{{ route('letters.pdf', $letter) }}" target="_blank" 
                                           class="rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                                            Print
                                        </a>

                                        {{-- 3. Permanent Delete Button --}}
                                        <form method="POST" action="{{ route('letters.destroy', $letter) }}" class="inline"
                                              onsubmit="return confirm('මෙම ලිපිය ඩේටාබේස් එකෙන්ම සම්පූර්ණයෙන්ම ඉවත් කිරීමට ඔබට විශ්වාසද?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 hover:text-red-700">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                                    No letters yet. Import officers, then click below to generate the letters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                <button type="button" onclick="document.getElementById('generate-form').submit()"
                        class="btn-success justify-center px-8 py-3">Generate Letters for All Officers</button>
            </div>

            <form id="generate-form" method="POST" action="{{ route('letters.generate', $batch) }}" class="hidden">
                @csrf
                @foreach ($letters as $letter)
                    <input type="hidden" name="officer_ids[]" value="{{ $letter->officer_id }}">
                @endforeach
            </form>
        </div>
    </div>
@endsection