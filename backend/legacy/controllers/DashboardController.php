<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Officer;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->role !== UserRole::Officer) {
            return view('dashboard.admin', [
                'user' => $user,
                'pendingOfficers' => $user->role === UserRole::DivisionalAdmin
                    ? Officer::whereHas('user', fn ($q) => $q->where('status', UserStatus::PendingVerification)->where('ds_division_id', $user->ds_division_id))->get()
                    : Officer::whereHas('user', fn ($q) => $q->where('status', UserStatus::PendingVerification))->get(),
                'totalOfficers' => Officer::count(),
                'appointedCount' => Officer::where('service_status', 'appointed')->count(),
                'confirmedCount' => Officer::where('confirmation_status', 'confirmed')->count(),
                'documentCount' => \App\Models\Document::count(),
            ]);
        }

        $officer = Officer::where('user_id', $user->id)
            ->with(['district', 'dsDivision', 'gnDivision', 'documents', 'serviceHistories'])
            ->first();

        return view('dashboard.officer', [
            'officer' => $officer,
            'timeline' => $officer?->serviceHistories ?? collect(),
            'documents' => $officer?->documents ?? collect(),
            'documentTypes' => DocumentType::cases(),
        ]);
    }
}
