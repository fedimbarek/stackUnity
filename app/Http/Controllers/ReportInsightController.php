<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportInsightService;
use Illuminate\Http\RedirectResponse;

class ReportInsightController extends Controller
{
    public function store(Report $report, ReportInsightService $insights): RedirectResponse
    {
        $this->authorize('view', $report);

        $insights->generate($report);

        return back()->with('status', 'report-insight-generated');
    }
}