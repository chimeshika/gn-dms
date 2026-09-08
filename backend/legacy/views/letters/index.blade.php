@extends('layouts.app')

@section('title', 'Letter Batches')

@section('content')
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Letter Batches</h1>
                <p class="mt-1 text-sm text-slate-600">Group-level information, officer imports and bulk letter generation.</p>
            </div>
            <a href="{{ route('letters.create') }}" class="btn-primary">New Batch</a>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Batch</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Document Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">My Ref No</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Letters</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Created</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($batches as $batch)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $batch->name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-800">
                                    {{ $batch->document_type?->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->my_ref_no ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->letters_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->status?->label() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->created_at?->format('d-m-Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('letters.show', $batch) }}" class="font-semibold text-brand-700 hover:underline">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-slate-500">No batches yet. Create your first letter batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $batches->links() }}</div>
    </div>
@endsection
