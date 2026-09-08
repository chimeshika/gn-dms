@extends('layouts.app')

@section('title', 'Administrative Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Officers</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ $totalOfficers }}</div>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Appointed</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ $appointedCount }}</div>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Confirmed</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ $confirmedCount }}</div>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documents Issued</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ $documentCount }}</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Pending Verification</h2>
                    <a href="/admin/officers?tableFilters%5Buser.status%5D%5Bvalue%5D=pending_verification" class="text-sm font-semibold text-brand-700 hover:underline">Open panel</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">NIC</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">DS Division</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pendingOfficers as $officer)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $officer->full_name_en }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $officer->nic_no }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $officer->dsDivision?->name_en ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="/admin/officers/{{ $officer->id }}/edit" class="font-semibold text-brand-700 hover:underline">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-slate-500">No pending verifications.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Quick Actions</h2>
                </div>
                <div class="mt-4 space-y-3">
                    <a href="{{ route('letters.create') }}" class="btn-primary w-full justify-center">New Letter Batch</a>
                    <a href="{{ route('letters.index') }}" class="btn-ghost w-full justify-center">Manage Letter Batches</a>
                    <a href="/admin/officers/create" class="btn-ghost w-full justify-center">Register Officer Manually</a>
                    <a href="/admin/signatories" class="btn-ghost w-full justify-center">Manage Signatories</a>
                </div>
            </div>
        </div>
    </div>
@endsection
