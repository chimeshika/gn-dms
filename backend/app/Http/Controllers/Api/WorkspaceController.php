<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Document, Letter};
use App\Services\AccessScope;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function officer(Request $request, int $id)
    {
        $officer = AccessScope::officer($request->user(), $id)->load('user', 'district', 'dsDivision', 'gnDivision', 'serviceHistories');
        if (! $request->user()->isMinistryHead()) $officer->load('documents');
        return response()->json($officer);
    }

    public function letters(Request $request)
    {
        abort_if($request->user()->isMinistryHead(), 403);
        $query = Letter::with('officer', 'creator', 'letterBatch')->whereIn('officer_id', AccessScope::officers($request->user())->select('id'));
        if ($request->user()->isOfficer()) $query->where('status', 'final');
        elseif (! $request->user()->isMainAdmin()) $query->where('created_by', $request->user()->id);
        if ($request->filled('officer_id')) $query->where('officer_id', $request->integer('officer_id'));
        if ($request->filled('search')) $query->where('ref_no', 'like', '%'.substr($request->string('search'), 0, 100).'%');
        return response()->json($query->latest()->paginate(20));
    }

    public function audit(Request $request)
    {
        abort_unless($request->user()->isMainAdmin(), 403);
        $query = AuditLog::with('user:id,name');
        if ($request->filled('search')) {
            $search = '%'.substr($request->string('search'), 0, 100).'%';
            $query->where(fn ($q) => $q->where('action', 'like', $search)->orWhere('auditable_type', 'like', $search)->orWhere('ip_address', 'like', $search)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $search)));
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function analytics(Request $request)
    {
        $request->validate(['from' => 'nullable|date', 'to' => $request->filled('from') ? 'nullable|date|after_or_equal:from' : 'nullable|date']);
        $officers = AccessScope::officers($request->user());
        foreach (['current_district_id', 'current_ds_division_id', 'current_grade', 'service_status', 'designation'] as $field) {
            if ($request->filled($field)) $officers->where($field, $request->input($field));
        }
        $documents = Document::whereIn('officer_id', (clone $officers)->select('id'));
        $letters = Letter::whereIn('officer_id', (clone $officers)->select('id'))->where('status', 'final');
        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if ($request->filled($key)) {
                $documents->whereDate('issue_date', $operator, $request->input($key));
                $letters->whereDate('letter_date', $operator, $request->input($key));
            }
        }
        $group = fn ($q, $field) => $q->select($field)->selectRaw('COUNT(*) as total')->groupBy($field)->get()->map(fn ($row) => ['name' => $row->getRawOriginal($field) ?: 'Not recorded', 'value' => $row->total]);
        $districts = (clone $officers)->select('current_district_id')->selectRaw('COUNT(*) as total')->groupBy('current_district_id')->with('district')->get()->map(fn ($row) => ['name' => $row->district?->name_en ?? 'Not recorded', 'value' => $row->total]);
        $months = (clone $letters)->select('letter_date')->selectRaw('COUNT(*) as total')->groupBy('letter_date')->get()->groupBy(fn ($row) => substr($row->getRawOriginal('letter_date'), 0, 7))->map(fn ($rows, $month) => ['name' => $month, 'value' => $rows->sum('total')])->sortKeys()->values();
        $result = [
            'total_officers' => (clone $officers)->count(),
            'active_officers' => (clone $officers)->whereNotIn('service_status', ['retired', 'interdicted'])->whereHas('user', fn ($q) => $q->where('status', 'active'))->count(),
            'documents' => (clone $documents)->count(), 'letters' => (clone $letters)->count(),
            'districts' => $districts, 'grades' => $group(clone $officers, 'current_grade'),
            'statuses' => $group(clone $officers, 'service_status'), 'document_types' => $group(clone $documents, 'document_type'), 'months' => $months,
        ];
        if ($request->filled('export')) {
            $request->validate(['export' => 'required|in:xlsx,pdf']);
            $rows = [['Report', 'Category', 'Total']];
            foreach (['districts', 'grades', 'statuses', 'document_types', 'months'] as $section) {
                foreach ($result[$section] as $row) $rows[] = [$section, $row['name'], $row['value']];
            }
            return app(\App\Services\TabularExportService::class)->download($rows, $request->input('export'), 'report-summary');
        }
        return response()->json($result);
    }
}
