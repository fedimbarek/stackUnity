<?php                                                                                                                                   
                                                                                                                                        
                                                                                                                                        
namespace Tests\Feature\Outages;                                                                                                        
                                                                                                                                        
                                                                                                                                        
use App\Events\OutageConfirmed;                                                                                                         
use App\Events\OutageResolved;                                                                                                          
use App\Listeners\SendOutageConfirmedNotification;                                                                                      
use App\Listeners\SendOutageResolvedNotification;                                                                                       
use App\Models\Neighborhood;                                                                                                            
use App\Models\PowerOutage;                                                                                                             
use App\Models\User;                                                                                                                    
use App\Notifications\OutageConfirmedNotification;                                                                                      
use App\Notifications\OutageResolvedNotification;                                                                                       
use App\Services\OutageAggregationService;                                                                                              
use Illuminate\Foundation\Testing\RefreshDatabase;                                                                                      
use Illuminate\Support\Facades\Notification;                                                                                            
use Tests\TestCase;                                                                                                                     
                                                                                                                                        
                                                                                                                                        
class OutageNotificationsTest extends TestCase                                                                                          
{                                                                                                                                       
    use RefreshDatabase;                                                                                                                
                                                                                                                                        
                                                                                                                                        
    private function outageWithResident(): array                                                                                        
    {                                                                                                                                   
        $n = Neighborhood::factory()->create(['name' => 'Lafayette']);                                                                  
        $resident = User::factory()->create(['neighborhood_id' => $n->id]);                                                             
        $outage = PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()]);
                                                                                                                                        
                                                                                                                                        
        return [$n, $resident, $outage];                                                                                                
    }                                                                                                                                   
                                                                                                                                        
                                                                                                                                        
    public function test_confirming_notifies_the_residents_of_the_neighborhood(): void                                                  
    {                                                                                                                                   
        Notification::fake();                                                                                                           
        [, $resident, $outage] = $this->outageWithResident();                                                                           
        $stranger = User::factory()->create();                                                                                          
                                                                                                                                        
                                                                                                                                        
        app(OutageAggregationService::class)->confirm($outage);                                                                         
                                                                                                                                        
                                                                                                                                        
        Notification::assertSentTo($resident, OutageConfirmedNotification::class);                                                      
        Notification::assertNotSentTo($stranger, OutageConfirmedNotification::class);                                                   
    }                                                                                                                                   
                                                                                                                                        
                                                                                                                                        
    public function test_resolving_notifies_the_residents_of_the_neighborhood(): void                                                   
    {                                                                                                                                   
        Notification::fake();                                                                                                           
        [, $resident, $outage] = $this->outageWithResident();                                                                           
        $stranger = User::factory()->create();                                                                                          
                                                                                                                                        
                                                                                                                                        
        app(OutageAggregationService::class)->resolve($outage);                                                                         
                                                                                                                                        
                                                                                                                                        
        Notification::assertSentTo($resident, OutageResolvedNotification::class);                                                       
        Notification::assertNotSentTo($stranger, OutageResolvedNotification::class);                                                    
    }                                                                                                                                   
                                                                                                                                        
                                                                                                                                        
    public function test_listeners_can_be_called_directly(): void                                                                       
    {                                                                                                                                   
        Notification::fake();                                                                                                           
        [, $resident, $outage] = $this->outageWithResident();                                                                           
                                                                                                                                        
                                                                                                                                        
        (new SendOutageConfirmedNotification())->handle(new OutageConfirmed($outage));                                                  
        (new SendOutageResolvedNotification())->handle(new OutageResolved($outage));                                                    
                                                                                                                                        
                                                                                                                                        
        Notification::assertSentTo($resident, OutageConfirmedNotification::class);                                                      
        Notification::assertSentTo($resident, OutageResolvedNotification::class);                                                       
    }                                                                                                                                   
                                                                                                                                        
                                                                                                                                        
    public function test_notifications_are_stored_in_database_with_a_readable_message(): void                                           
    {                                                                                                                                   
        [, $resident, $outage] = $this->outageWithResident();                                                                           
                                                                                                                                        
                                                                                                                                        
        app(OutageAggregationService::class)->confirm($outage);                                                                         
        app(OutageAggregationService::class)->resolve($outage);                                                                         
                                                                                                                                        
                                                                                                                                        
        $messages = $resident->notifications()->pluck('data')->map(fn ($d) => $d['message'])->all();                                    
        $this->assertCount(2, $messages);                                                                                               
        $this->assertStringContainsString('Lafayette', $messages[0]);                                                                   
                                                                                                                                        
                                                                                                                                        
        $confirmed = new OutageConfirmedNotification($outage);                                                                          
        $this->assertSame(['database'], $confirmed->via($resident));                                                                    
        $this->assertSame('Coupure confirmée', $confirmed->toArray($resident)['title']);                                                
        $this->assertSame($outage->id, $confirmed->toArray($resident)['outage_id']);                                                    
                                                                                                                                        
                                                                                                                                        
        $resolved = new OutageResolvedNotification($outage);                                                                            
        $this->assertSame(['database'], $resolved->via($resident));                                                                     
        $this->assertSame('Coupure résolue', $resolved->toArray($resident)['title']);                                                   
    }                                                                                                                                   
}                                                                                                                                       