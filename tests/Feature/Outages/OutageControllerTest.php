<?php                                                                                                                                
                                                                                                                                     
                                                                                                                                     
namespace Tests\Feature\Outages;                                                                                                     
                                                                                                                                     
                                                                                                                                     
use App\Models\Neighborhood;                                                                                                         
use App\Models\OutageReport;                                                                                                         
use App\Models\PowerOutage;                                                                                                          
use Illuminate\Foundation\Testing\RefreshDatabase;                                                                                   
use Illuminate\Support\Facades\Notification;                                                                                         
use Tests\Concerns\InteractsWithRoles;                                                                                               
use Tests\TestCase;                                                                                                                  
                                                                                                                                     
                                                                                                                                     
class OutageControllerTest extends TestCase                                                                                          
{                                                                                                                                    
    use RefreshDatabase, InteractsWithRoles;                                                                                         
                                                                                                                                     
                                                                                                                                     
    private function outage(Neighborhood $n, array $attrs = []): PowerOutage                                                         
    {                                                                                                                                
        return PowerOutage::create($attrs + [                                                                                        
            'neighborhood_id' => $n->id,                                                                                             
            'status' => 'reported',                                                                                                  
            'source' => 'user',                                                                                                      
            'started_at' => now(),                                                                                                   
        ]);                                                                                                                          
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_guest_is_redirected_to_login(): void                                                                        
    {                                                                                                                                
        $this->get(route('outages.index'))->assertRedirect(route('login'));                                                          
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_resident_only_sees_outages_of_his_neighborhood(): void                                                      
    {                                                                                                                                
        $mine = Neighborhood::factory()->create();                                                                                   
        $other = Neighborhood::factory()->create();                                                                                  
        $this->outage($mine);                                                                                                        
        $this->outage($other);                                                                                                       
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($this->resident($mine))                                                                                      
            ->get(route('outages.index'))                                                                                            
            ->assertOk()                                                                                                             
            ->assertViewIs('outages.index')                                                                                          
            ->assertViewHas('outages', fn ($p) => $p->count() === 1);                                                                
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_admin_sees_every_outage_and_json_is_supported(): void                                                       
    {                                                                                                                                
        $a = Neighborhood::factory()->create();                                                                                      
        $b = Neighborhood::factory()->create();                                                                                      
        $this->outage($a);                                                                                                           
        $this->outage($b);                                                                                                           
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($this->admin())                                                                                              
            ->getJson(route('outages.index'))                                                                                        
            ->assertOk()                                                                                                             
            ->assertJsonPath('total', 2);                                                                                            
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_store_requires_a_neighborhood_in_html_and_json(): void                                                      
    {                                                                                                                                
        $user = $this->resident(null);                                                                                               
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($user)                                                                                                       
            ->post(route('outages.store'))                                                                                           
            ->assertSessionHasErrors('neighborhood');                                                                                
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($user)                                                                                                       
            ->postJson(route('outages.store'))                                                                                       
            ->assertStatus(422)                                                                                                      
            ->assertJsonPath('message', "Tu dois d'abord renseigner ton quartier dans ton profil.");                                 
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_store_creates_an_outage_report_in_html_and_json(): void                                                     
    {                                                                                                                                
        Notification::fake();                                                                                                        
        $n = Neighborhood::factory()->create();                                                                                      
        $user = $this->resident($n);                                                                                                 
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($user)                                                                                                       
            ->post(route('outages.store'), ['latitude' => 36.8, 'longitude' => 10.18])                                               
            ->assertRedirect()                                                                                                       
            ->assertSessionHas('status', 'outage-reported');                                                                         
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($user)                                                                                                       
            ->postJson(route('outages.store'))                                                                                       
            ->assertCreated()                                                                                                        
            ->assertJsonPath('neighborhood.id', $n->id);                                                                             
                                                                                                                                     
                                                                                                                                     
        $this->assertSame(2, OutageReport::count());                                                                                 
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_store_validates_coordinates(): void                                                                         
    {                                                                                                                                
        $user = $this->resident(Neighborhood::factory()->create());                                                                  
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($user)                                                                                                       
            ->postJson(route('outages.store'), ['latitude' => 500])                                                                  
            ->assertStatus(422)                                                                                                      
            ->assertJsonValidationErrors('latitude');                                                                                
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_show_returns_the_outage_with_its_reports(): void                                                            
    {                                                                                                                                
        $n = Neighborhood::factory()->create();                                                                                      
        $outage = $this->outage($n);                                                                                                 
        $user = $this->resident($n);                                                                                                 
        OutageReport::create(['power_outage_id' => $outage->id, 'user_id' => $user->id, 'trust_weight' => 1.0, 'trust_flags' => []]);
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($user)                                                                                                       
            ->getJson(route('outages.show', $outage))                                                                                
            ->assertOk()                                                                                                             
            ->assertJsonPath('id', $outage->id)                                                                                      
            ->assertJsonPath('reports.0.user.id', $user->id);                                                                        
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_map_only_lists_active_outages_with_coordinates(): void                                                      
    {                                                                                                                                
        $n = Neighborhood::factory()->create();                                                                                      
        $this->outage($n, ['latitude' => 36.8, 'longitude' => 10.1]);                                                                
        $this->outage($n); // sans coordonnées                                                                                       
        $this->outage($n, ['latitude' => 36.8, 'longitude' => 10.1, 'status' => 'resolved']);                                        
                                                                                                                                     
                                                                                                                                     
        $this->getJson(route('front.map.data'))->assertOk()->assertJsonCount(1);                                                     
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($this->resident($n))                                                                                         
            ->getJson(route('outages.map'))                                                                                          
            ->assertOk()                                                                                                             
            ->assertJsonCount(1);                                                                                                    
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_admin_can_confirm_and_resolve_in_html_and_json(): void                                                      
    {                                                                                                                                
        Notification::fake();                                                                                                        
        $n = Neighborhood::factory()->create();                                                                                      
        $admin = $this->admin();                                                                                                     
                                                                                                                                     
                                                                                                                                     
        $o1 = $this->outage($n);                                                                                                     
        $this->actingAs($admin)                                                                                                      
            ->put(route('outages.confirm', $o1))                                                                                     
            ->assertSessionHas('status', 'outage-confirmed');                                                                        
        $this->assertSame('confirmed', $o1->fresh()->status);                                                                        
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($admin)                                                                                                      
            ->put(route('outages.resolve', $o1))                                                                                     
            ->assertSessionHas('status', 'outage-resolved');                                                                         
        $this->assertSame('resolved', $o1->fresh()->status);                                                                         
                                                                                                                                     
                                                                                                                                     
        $o2 = $this->outage($n);                                                                                                     
        $this->actingAs($admin)                                                                                                      
            ->putJson(route('outages.confirm', $o2))                                                                                 
            ->assertOk()                                                                                                             
            ->assertJsonPath('status', 'confirmed');                                                                                 
        $this->actingAs($admin)                                                                                                      
            ->putJson(route('outages.resolve', $o2))                                                                                 
            ->assertOk()                                                                                                             
            ->assertJsonPath('status', 'resolved');                                                                                  
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_resident_cannot_confirm_resolve_edit_or_delete(): void                                                      
    {                                                                                                                                
        $n = Neighborhood::factory()->create();                                                                                      
        $outage = $this->outage($n);                                                                                                 
        $resident = $this->resident($n);                                                                                             
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($resident)->put(route('outages.confirm', $outage))->assertForbidden();                                       
        $this->actingAs($resident)->put(route('outages.resolve', $outage))->assertForbidden();                                       
        $this->actingAs($resident)->get(route('outages.edit', $outage))->assertForbidden();                                          
        $this->actingAs($resident)->delete(route('outages.destroy', $outage))->assertForbidden();                                    
    }                                                                                                                                
                                                                                                                                     
                                                                                                                                     
    public function test_gestionnaire_can_edit_update_and_delete_an_outage(): void                                                   
    {                                                                                                                                
        $n = Neighborhood::factory()->create();                                                                                      
        $other = Neighborhood::factory()->create();                                                                                  
        $outage = $this->outage($n);                                                                                                 
        $manager = $this->gestionnaire($n);                                                                                          
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($manager)                                                                                                    
            ->get(route('outages.edit', $outage))                                                                                    
            ->assertOk()                                                                                                             
            ->assertViewIs('outages.edit');                                                                                          
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($manager)                                                                                                    
            ->put(route('outages.update', $outage), [                                                                                
                'neighborhood_id' => $other->id,                                                                                     
                'status' => 'confirmed',                                                                                             
                'latitude' => 36.8,                                                                                                  
                'longitude' => 10.2,                                                                                                 
            ])                                                                                                                       
            ->assertRedirect(route('outages.index'))                                                                                 
            ->assertSessionHas('status', 'outage-updated');                                                                          
                                                                                                                                     
                                                                                                                                     
        $this->assertSame($other->id, $outage->fresh()->neighborhood_id);                                                            
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($manager)                                                                                                    
            ->put(route('outages.update', $outage), ['status' => 'nope'])                                                            
            ->assertSessionHasErrors(['neighborhood_id', 'status']);                                                                 
                                                                                                                                     
                                                                                                                                     
        $this->actingAs($manager)                                                                                                    
            ->delete(route('outages.destroy', $outage))                                                                              
            ->assertSessionHas('status', 'outage-deleted');                                                                          
        $this->assertModelMissing($outage);                                                                                          
    }                                                                                                                                
}                                                                                                                                    