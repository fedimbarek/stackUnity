<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Neighborhood;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportMailer;
use App\Services\StatisticsExporter;
use App\Services\StatisticsService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::with(['neighborhood', 'generatedBy'])
            ->latest()
            ->paginate(10);

        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        $this->authorize('create', Report::class);

        $neighborhoods = Neighborhood::orderBy('name')->get();

        return view('reports.create', compact('neighborhoods'));
    }

    public function store(ReportRequest $request, StatisticsExporter $exporter): RedirectResponse
    {
        $this->authorize('create', Report::class);

        $data = $request->validated();

        $report = Report::create([
            ...$data,
            'generated_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        $fileName = 'report-'.$report->id.'.csv';
        $exporter->exportToCsv($report->period_start, $report->period_end, $fileName);
        $report->update(['file_path' => $fileName, 'status' => 'generated']);

        return redirect()->route('reports.show', $report)->with('status', 'report-created');
    }

    public function show(Report $report, StatisticsService $stats)
    {
        $this->authorize('view', $report);

        $kpis = $stats->compareWithPreviousPeriod(
            $report->neighborhood_id,
            $report->period_start,
            $report->period_end
        );

        return view('reports.show', compact('report', 'kpis'));
    }

    public function edit(Report $report)
    {
        $this->authorize('update', $report);

        $neighborhoods = Neighborhood::orderBy('name')->get();

        return view('reports.edit', compact('report', 'neighborhoods'));
    }

    // public function update(ReportRequest $request, Report $report): RedirectResponse
    // {
        // $this->authorize('update', $report);

        // $report->update($request->validated());

        // return redirect()->route('reports.index')->with('status', 'report-updated');
    // }
        public function update(ReportRequest $request, Report $report, StatisticsExporter $exporter): RedirectResponse
    {
        $this->authorize('update', $report);

        $report->update($request->validated());

        // Régénère le fichier CSV avec les nouvelles dates / le nouveau quartier
        $fileName = $report->file_path ?: 'report-'.$report->id.'.csv';
        $exporter->exportToCsv($report->period_start, $report->period_end, $fileName);
        $report->update(['file_path' => $fileName, 'status' => 'generated']);

        return redirect()->route('reports.index')->with('status', 'report-updated');
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        $fullPath = storage_path('reports/'.$report->file_path);

        if ($report->file_path && file_exists($fullPath)) {
            unlink($fullPath);
        }

        $report->delete();

        return redirect()->route('reports.index')->with('status', 'report-deleted');
    }

    public function download(Report $report): BinaryFileResponse
    {
        $this->authorize('view', $report);

        $fullPath = storage_path('reports/'.$report->file_path);

        abort_unless($report->file_path && file_exists($fullPath), 404);

        return response()->download($fullPath, $report->title.'.csv');
    }

    public function send(Report $report, ReportMailer $mailer): RedirectResponse
    {
        $this->authorize('update', $report);

        $fullPath = storage_path('reports/'.$report->file_path);

        abort_unless($report->file_path && file_exists($fullPath), 422, 'Génère le rapport avant de l\'envoyer.');

        $recipients = User::role('admin')->pluck('email')->toArray();
        $sent = $mailer->sendWeeklyReport($report, $recipients, $fullPath);

        $report->update(['status' => $sent ? 'sent' : 'failed', 'sent_at' => $sent ? now() : null]);

        return back()->with('status', $sent ? 'report-sent' : 'report-send-failed');
    }
}