<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\User;
use App\Services\ReportMailer;
use App\Services\StatisticsExporter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(ReportMailer $mailer, StatisticsExporter $exporter): void
    {
        $from = Carbon::now()->subWeek()->startOfWeek();
        $to = Carbon::now()->subWeek()->endOfWeek();

        $systemUser = User::role('admin')->first();

        $report = Report::create([
            'title' => 'Rapport hebdomadaire '.$from->format('d/m').' - '.$to->format('d/m'),
            'period_start' => $from,
            'period_end' => $to,
            'generated_by' => $systemUser?->id,
            'status' => 'draft',
        ]);

        $fileName = 'weekly-'.$report->id.'.csv';
        $exporter->exportToCsv($from, $to, $fileName);
        $report->update(['file_path' => $fileName, 'status' => 'generated']);

        $recipients = User::role('admin')->pluck('email')->toArray();
        $fullPath = storage_path('reports/'.$fileName);

        $sent = $mailer->sendWeeklyReport($report, $recipients, $fullPath);

        $report->update([
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
        ]);
    }
}