<?php

namespace Tests\Feature\Reports;

use App\Models\Report;
use App\Models\User;
use App\Services\ReportMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesTemporaryStorage;
use Tests\TestCase;

class ReportMailerFailureTest extends TestCase
{
    use RefreshDatabase, UsesTemporaryStorage;

    public function test_smtp_failure_is_logged_and_swallowed(): void
    {
        $report = Report::factory()->create([
            'neighborhood_id' => null,
            'generated_by' => User::factory()->create()->id,
            'period_start' => '2026-07-06',
            'period_end' => '2026-07-12',
        ]);
        $file = storage_path('rapport.csv');
        file_put_contents($file, "a,b\n");

        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp down'));
        Log::shouldReceive('error')->once()->withArgs(fn ($message) => str_contains($message, 'sendWeeklyReport failed'));

        $this->assertFalse(app(ReportMailer::class)->sendWeeklyReport($report, ['a@test.tn'], $file));
    }
}
