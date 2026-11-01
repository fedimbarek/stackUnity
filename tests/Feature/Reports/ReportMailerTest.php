<?php                                                                                                                              
                                                                                                                                   
                                                                                                                                   
namespace Tests\Feature\Reports;                                                                                                   
                                                                                                                                   
                                                                                                                                   
use App\Jobs\WeeklyReportJob;                                                                                                      
use App\Models\Report;                                                                                                             
use App\Models\User;                                                                                                               
use App\Services\ReportMailer;                                                                                                     
use App\Services\StatisticsExporter;                                                                                               
use Illuminate\Foundation\Testing\RefreshDatabase;                                                                                 
use Illuminate\Support\Facades\Mail;                                                                                               
use Tests\Concerns\InteractsWithRoles;                                                                                             
use Tests\Concerns\UsesTemporaryStorage;                                                                                           
use Spatie\Permission\Models\Role;
use Tests\TestCase;                                                                                                                
                                                                                                                                   
                                                                                                                                   
class ReportMailerTest extends TestCase                                                                                            
{                                                                                                                                  
    use RefreshDatabase, UsesTemporaryStorage, InteractsWithRoles;                                                                 
                                                                                                                                   
                                                                                                                                   
    private function report(): Report                                                                                              
    {                                                                                                                              
        return Report::factory()->create([                                                                                         
            'neighborhood_id' => null,                                                                                             
            'generated_by' => User::factory()->create()->id,                                                                       
            'period_start' => '2026-07-06',                                                                                        
            'period_end' => '2026-07-12',                                                                                          
        ]);                                                                                                                        
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    private function sentMessages()                                                                                                
    {                                                                                                                              
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();                                            
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    public function test_weekly_report_is_sent_with_the_csv_attached(): void                                                       
    {                                                                                                                              
        $file = storage_path('rapport.csv');                                                                                       
        file_put_contents($file, "a,b\n1,2\n");                                                                                    
                                                                                                                                   
                                                                                                                                   
        $ok = app(ReportMailer::class)->sendWeeklyReport($this->report(), ['a@test.tn', 'b@test.tn'], $file);                      
                                                                                                                                   
                                                                                                                                   
        $this->assertTrue($ok);                                                                                                    
        $this->assertCount(1, $this->sentMessages());                                                                              
        $raw = $this->sentMessages()->first()->toString();                                                                         
        // le sujet est encodé/replié en MIME dans le message brut : on le lit sur l'objet
        $subject = $this->sentMessages()->first()->getOriginalMessage()->getSubject();
        $this->assertStringContainsString('Rapport hebdomadaire (06/07 - 12/07)', $subject);                                           
        $this->assertStringContainsString('rapport.csv', $raw);                                                                    
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    public function test_weekly_report_without_recipient_returns_false(): void                                                     
    {                                                                                                                              
        $this->assertFalse(app(ReportMailer::class)->sendWeeklyReport($this->report(), [], '/nope.csv'));                          
        $this->assertCount(0, $this->sentMessages());                                                                              
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    public function test_weekly_report_returns_false_when_the_attachment_is_missing(): void                                        
    {                                                                                                                              
        $this->assertFalse(app(ReportMailer::class)->sendWeeklyReport($this->report(), ['a@test.tn'], storage_path('absent.csv')));
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    public function test_invitation_is_sent_and_failures_are_swallowed(): void                                                     
    {                                                                                                                              
        $user = User::factory()->create(['name' => 'Sami', 'email' => 'sami@test.tn']);                                            
                                                                                                                                   
                                                                                                                                   
        $this->assertTrue(app(ReportMailer::class)->sendInvitation($user, 'https://heatalert.test/login'));                        
        $raw = $this->sentMessages()->first()->toString();                                                                         
        $this->assertStringContainsString('sami@test.tn', $raw);                                                                   
        $this->assertStringContainsString('Bienvenue sur HeatAlert', $raw);                                                        
                                                                                                                                   
                                                                                                                                   
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp down'));                                         
        $this->assertFalse(app(ReportMailer::class)->sendInvitation($user, 'https://heatalert.test/login'));                       
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    public function test_weekly_job_generates_exports_and_sends_the_report(): void                                                 
    {                                                                                                                              
        $admin = $this->admin(null, ['email' => 'boss@test.tn']);                                                                  
                                                                                                                                   
                                                                                                                                   
        (new WeeklyReportJob())->handle(app(ReportMailer::class), app(StatisticsExporter::class));                                 
                                                                                                                                   
                                                                                                                                   
        $report = Report::withoutGlobalScopes()->first();                                                                          
        $this->assertSame('sent', $report->status);                                                                                
        $this->assertNotNull($report->sent_at);                                                                                    
        $this->assertSame($admin->id, $report->generated_by);                                                                      
        $this->assertFileExists(storage_path('reports/'.$report->file_path));                                                      
        $this->assertStringContainsString('boss@test.tn', $this->sentMessages()->first()->toString());                             
    }                                                                                                                              
                                                                                                                                   
                                                                                                                                   
    public function test_weekly_job_marks_the_report_as_failed_when_nobody_can_receive_it(): void                                  
    {                                                                                                                              
        Role::findOrCreate('admin', 'web'); // le rôle existe mais aucun utilisateur ne l'a
        (new WeeklyReportJob())->handle(app(ReportMailer::class), app(StatisticsExporter::class));                                 
                                                                                                                                   
                                                                                                                                   
        $report = Report::withoutGlobalScopes()->first();                                                                          
        $this->assertSame('failed', $report->status);                                                                              
        $this->assertNull($report->sent_at);                                                                                       
        $this->assertNull($report->generated_by);                                                                                  
    }                                                                                                                              
}                                                                                                                                  