<?php                                                                                                                               
                                                                                                                                    
                                                                                                                                    
namespace Tests\Feature\Outages;                                                                                                    
                                                                                                                                    
                                                                                                                                    
use App\Models\Neighborhood;                                                                                                        
use App\Models\OutageReport;                                                                                                        
use App\Models\PowerOutage;                                                                                                         
use App\Models\User;                                                                                                                
use App\Models\WeatherForecast;                                                                                                     
use App\Services\OutageForecastService;                                                                                             
use Carbon\Carbon;                                                                                                                  
use Illuminate\Foundation\Testing\RefreshDatabase;                                                                                  
use Tests\TestCase;                                                                                                                 
                                                                                                                                    
                                                                                                                                    
class OutageForecastServiceTest extends TestCase                                                                                    
{                                                                                                                                   
    use RefreshDatabase;                                                                                                            
                                                                                                                                    
                                                                                                                                    
    private OutageForecastService $service;                                                                                         
                                                                                                                                    
                                                                                                                                    
    protected function setUp(): void                                                                                                
    {                                                                                                                               
        parent::setUp();                                                                                                            
        Carbon::setTestNow('2026-07-10 12:00:00');                                                                                  
        config(['services.weather.heat_warning' => 35]);                                                                            
        $this->service = new OutageForecastService();                                                                               
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    protected function tearDown(): void                                                                                             
    {                                                                                                                               
        Carbon::setTestNow();                                                                                                       
        parent::tearDown();                                                                                                         
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    private function active(Neighborhood $n, ?string $startedAt = null): PowerOutage                                                
    {                                                                                                                               
        return PowerOutage::create([                                                                                                
            'neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user',                                                
            'started_at' => $startedAt ?? now(),                                                                                    
        ]);                                                                                                                         
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    /** Coupure résolue ayant duré $minutes minutes. */                                                                             
    private function resolved(Neighborhood $n, int $minutes, int $daysAgo = 1): PowerOutage                                         
    {                                                                                                                               
        $start = now()->subDays($daysAgo);                                                                                          
                                                                                                                                    
                                                                                                                                    
        return PowerOutage::create([                                                                                                
            'neighborhood_id' => $n->id, 'status' => 'resolved', 'source' => 'user',                                                
            'started_at' => $start, 'resolved_at' => $start->copy()->addMinutes($minutes),                                          
        ]);                                                                                                                         
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    private function reports(PowerOutage $o, int $count): void                                                                      
    {                                                                                                                               
        $user = User::factory()->create();                                                                                          
        for ($i = 0; $i < $count; $i++) {                                                                                           
            OutageReport::create(['power_outage_id' => $o->id, 'user_id' => $user->id, 'trust_weight' => 1.0, 'trust_flags' => []]);
        }                                                                                                                           
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    private function forecast(Neighborhood $n, float $max, string $level = 'normal', ?string $date = null): void                    
    {                                                                                                                               
        WeatherForecast::forceCreate([                                                                                              
            'neighborhood_id' => $n->id, 'date' => $date ?? today()->toDateString(),                                                
            'temp_max' => $max, 'temp_min' => $max - 10, 'level' => $level,                                                         
        ]);                                                                                                                         
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_duration_falls_back_to_the_default_without_history(): void                                                 
    {                                                                                                                               
        $n = Neighborhood::factory()->create();                                                                                     
        $p = $this->service->predictDuration($this->active($n));                                                                    
                                                                                                                                    
                                                                                                                                    
        $this->assertSame(90, $p['minutes']);                                                                                       
        $this->assertSame('faible', $p['confidence']);                                                                              
        $this->assertSame(0, $p['sample']);                                                                                         
        $this->assertSame('2026-07-10 13:30:00', $p['eta']->format('Y-m-d H:i:s'));                                                 
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_duration_uses_the_local_median_with_medium_then_high_confidence(): void                                    
    {                                                                                                                               
        $n = Neighborhood::factory()->create();                                                                                     
        foreach ([30, 40, 50, 60, 70] as $m) {                                                                                      
            $this->resolved($n, $m);                                                                                                
        }                                                                                                                           
        $p = $this->service->predictDuration($this->active($n));                                                                    
        $this->assertSame(50, $p['minutes']);                                                                                       
        $this->assertSame('moyenne', $p['confidence']);                                                                             
        $this->assertSame(5, $p['sample']);                                                                                         
                                                                                                                                    
                                                                                                                                    
        foreach (range(1, 10) as $i) {                                                                                              
            $this->resolved($n, 50);                                                                                                
        }                                                                                                                           
        $this->assertSame('haute', $this->service->predictDuration($this->active($n))['confidence']);                               
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_duration_uses_the_global_median_when_the_neighborhood_has_no_history(): void                               
    {                                                                                                                               
        $n = Neighborhood::factory()->create();                                                                                     
        $other = Neighborhood::factory()->create();                                                                                 
        foreach ([10, 20, 30, 40, 50, 60] as $m) { // médiane paire = 35                                                            
            $this->resolved($other, $m);                                                                                            
        }                                                                                                                           
                                                                                                                                    
                                                                                                                                    
        $p = $this->service->predictDuration($this->active($n));                                                                    
                                                                                                                                    
                                                                                                                                    
        $this->assertSame(35, $p['minutes']);                                                                                       
        $this->assertSame('faible', $p['confidence']);                                                                              
        $this->assertSame(6, $p['sample']);                                                                                         
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_absurd_durations_are_ignored(): void                                                                       
    {                                                                                                                               
        $n = Neighborhood::factory()->create();                                                                                     
        foreach ([0, 3000, 3000, 3000, 3000, 3000] as $m) {                                                                         
            $this->resolved($n, $m);                                                                                                
        }                                                                                                                           
                                                                                                                                    
                                                                                                                                    
        $this->assertSame(0, $this->service->predictDuration($this->active($n))['sample']);                                         
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_duration_multipliers_night_heatwave_and_many_reports(): void                                               
    {                                                                                                                               
        $n = Neighborhood::factory()->create();                                                                                     
                                                                                                                                    
                                                                                                                                    
        $night = $this->active($n, '2026-07-10 23:00:00');                                                                          
        $this->assertSame(113, $this->service->predictDuration($night)['minutes']); // 90 x 1.25                                    
                                                                                                                                    
                                                                                                                                    
        $earlyMorning = $this->active($n, '2026-07-10 03:00:00');                                                                   
        $this->assertSame(113, $this->service->predictDuration($earlyMorning)['minutes']);                                          
                                                                                                                                    
                                                                                                                                    
        $this->forecast($n, 42, 'canicule');                                                                                        
        $day = $this->active($n, '2026-07-10 12:00:00');                                                                            
        $this->assertSame(117, $this->service->predictDuration($day)['minutes']); // 90 x 1.3                                       
                                                                                                                                    
                                                                                                                                    
        $this->reports($day, 10);                                                                                                   
        $p = $this->service->predictDuration($day);                                                                                 
        $this->assertSame(140, $p['minutes']); // 90 x 1.3 x 1.2                                                                    
        $this->assertCount(3, $p['factors']); // base + canicule + signalements (pas de nuit à 12h)                                                                                       
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_risk_score_is_zero_without_any_data(): void                                                                
    {                                                                                                                               
        $r = $this->service->riskScore(Neighborhood::factory()->create());                                                          
                                                                                                                                    
                                                                                                                                    
        $this->assertSame(0, $r['score']);                                                                                          
        $this->assertSame('faible', $r['level']);                                                                                   
        $this->assertSame(['historique_30j' => 0, 'chaleur_prevue' => 0, 'activite_en_cours' => 0], $r['factors']);                 
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_risk_score_is_critical_with_history_heat_and_an_active_outage(): void                                      
    {                                                                                                                               
        $n = Neighborhood::factory()->create();                                                                                     
        foreach (range(1, 9) as $i) { // plafonné à 8                                                                               
            $this->resolved($n, 30, 2);                                                                                             
        }                                                                                                                           
        $this->forecast($n, 45);                                                                                                    
        $this->active($n);                                                                                                          
                                                                                                                                    
                                                                                                                                    
        $r = $this->service->riskScore($n);                                                                                         
                                                                                                                                    
                                                                                                                                    
        $this->assertSame(['historique_30j' => 40, 'chaleur_prevue' => 35, 'activite_en_cours' => 25], $r['factors']);              
        $this->assertSame(100, $r['score']);                                                                                        
        $this->assertSame('critique', $r['level']);                                                                                 
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_risk_levels_high_moderate_and_recent_reports(): void                                                       
    {                                                                                                                               
        // élevé : historique 1 coupure (5) + chaleur 35 + 5 signalements sur coupure résolue (15) = 55                                                        
        $high = Neighborhood::factory()->create();                                                                                  
        $this->forecast($high, 40);                                                                                                 
        $this->reports($this->resolved($high, 30), 5);                                                                              
        $r = $this->service->riskScore($high);                                                                                      
        $this->assertSame(15, $r['factors']['activite_en_cours']);                                                                  
        $this->assertSame(5, $r['factors']['historique_30j']);
        $this->assertSame(55, $r['score']);                                                                                         
        $this->assertSame('élevé', $r['level']);                                                                                    
                                                                                                                                    
                                                                                                                                    
        // modéré : chaleur partielle (36 °C -> 35 pts x 0,6 = 21)                                                                  
        $moderate = Neighborhood::factory()->create();                                                                              
        $this->forecast($moderate, 36);                                                                                             
        $r = $this->service->riskScore($moderate);                                                                                  
        $this->assertSame(21, $r['factors']['chaleur_prevue']);                                                                     
        $this->assertSame('modéré', $r['level']);                                                                                   
                                                                                                                                    
                                                                                                                                    
        // sous le seuil de chaleur : 0                                                                                             
        $cool = Neighborhood::factory()->create();                                                                                  
        $this->forecast($cool, 20);                                                                                                 
        $this->assertSame(0, $this->service->riskScore($cool)['factors']['chaleur_prevue']);                                        
    }                                                                                                                               
                                                                                                                                    
                                                                                                                                    
    public function test_neighborhoods_are_ranked_from_the_riskiest(): void                                                         
    {                                                                                                                               
        $calm = Neighborhood::factory()->create(['name' => 'Aaa calme']);                                                           
        $hot = Neighborhood::factory()->create(['name' => 'Zzz chaud']);                                                            
        $this->forecast($hot, 45);                                                                                                  
                                                                                                                                    
                                                                                                                                    
        $ranking = $this->service->rankNeighborhoods();                                                                             
                                                                                                                                    
                                                                                                                                    
        $this->assertSame($hot->id, $ranking[0]['neighborhood']->id);                                                               
        $this->assertSame($calm->id, $ranking[1]['neighborhood']->id);                                                              
        $this->assertGreaterThan($ranking[1]['score'], $ranking[0]['score']);                                                       
    }                                                                                                                               
}                                                                                                                                   