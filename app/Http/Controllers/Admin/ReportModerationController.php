<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportModerationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $reports = Report::query()
            ->with(['reporter:id,name', 'reported:id,name', 'swapRequest:id'])
            ->when(in_array($status, ['open', 'resolved', 'dismissed'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function updateStatus(Request $request, Report $report)
    {
        $request->validate([
            'status' => 'required|in:open,resolved,dismissed',
        ]);

        $report->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Report updated.');
    }
}
