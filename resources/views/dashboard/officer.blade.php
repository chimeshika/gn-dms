@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-1">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h1 class="text-xl font-bold text-slate-900">My Profile</h1>

                @if (! $officer)
                    <p class="mt-4 text-sm text-slate-600">
                        Your officer record has not been created yet. Please contact your Divisional Secretariat.
                    </p>
                @else
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Name (English)</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->full_name_en }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Name (Sinhala)</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->full_name_si ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Name (Tamil)</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->full_name_ta ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">NIC</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->nic_no }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Date of Birth</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->dob?->format('d-m-Y') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Grade</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->current_grade?->label() }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Station</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $officer->dsDivision?->name_en }} / {{ $officer->district?->name_en }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">GN Division</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->gnDivision?->display_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Service Status</dt>
                            <dd>
                                <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">
                                    {{ $officer->service_status?->label() }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Confirmation Status</dt>
                            <dd class="font-semibold text-slate-900">{{ $officer->confirmation_status?->label() }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        </section>

        <section class="lg:col-span-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Service Timeline</h2>

                @if ($timeline->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">No service events recorded yet.</p>
                @else
                    <ol class="relative mt-6 space-y-6 border-l-2 border-slate-200 pl-6">
                        @foreach ($timeline as $event)
                            <li class="relative">
                                <span class="absolute -left-[31px] flex h-4 w-4 items-center justify-center rounded-full bg-brand-600 ring-4 ring-white"></span>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-semibold text-slate-900">
                                        {{ $event->event_type?->label() }}
                                        @if ($event->ref_no)
                                            <span class="text-xs font-normal text-slate-500">({{ $event->ref_no }})</span>
                                        @endif
                                    </h3>
                                    <span class="text-xs text-slate-500">{{ $event->effective_date?->format('d-m-Y') }}</span>
                                </div>
                                @if ($event->description)
                                    <p class="mt-1 text-sm text-slate-600">{{ $event->description }}</p>
                                @endif
                                @if ($event->old_value && $event->new_value)
                                    <p class="mt-1 text-xs text-slate-500">
                                        <span class="line-through">{{ $event->old_value }}</span>
                                        <span aria-hidden="true">&rarr;</span>
                                        <span class="font-semibold">{{ $event->new_value }}</span>
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">My Documents</h2>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Issue Date</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">File</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $document->document_type?->label() }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $document->ref_no ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $document->issue_date?->format('d-m-Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($document->file_path)
                                            <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank"
                                               class="font-semibold text-brand-700 hover:underline">View</a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-slate-500">No documents issued yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
