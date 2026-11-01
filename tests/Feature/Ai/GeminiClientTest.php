<?php                                                                                                   
                                                                                                        
                                                                                                        
namespace Tests\Feature\Ai;                                                                             
                                                                                                        
                                                                                                        
use App\Services\GeminiClient;                                                                          
use Illuminate\Http\Client\Request;                                                                     
use Illuminate\Support\Facades\Http;                                                                    
use Tests\TestCase;                                                                                     
                                                                                                        
                                                                                                        
class GeminiClientTest extends TestCase                                                                 
{                                                                                                       
    private function ok(string $text): array                                                            
    {                                                                                                   
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];                     
    }                                                                                                   
                                                                                                        
                                                                                                        
    public function test_it_returns_null_without_api_key_and_does_not_call_the_api(): void              
    {                                                                                                   
        Http::fake();                                                                                   
        config(['services.gemini.key' => null]);                                                        
                                                                                                        
                                                                                                        
        $this->assertNull((new GeminiClient())->generate('Bonjour'));                                   
        Http::assertNothingSent();                                                                      
    }                                                                                                   
                                                                                                        
                                                                                                        
    public function test_it_returns_the_trimmed_text_and_sends_key_and_model(): void                    
    {                                                                                                   
        config(['services.gemini.key' => 'secret', 'services.gemini.model' => 'gemini-test']);          
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->ok("  Salut  \n"))]);
                                                                                                        
                                                                                                        
        $this->assertSame('Salut', (new GeminiClient())->generate('Bonjour', 0.1));                     
                                                                                                        
                                                                                                        
        Http::assertSent(function (Request $request) {                                                  
            return str_contains($request->url(), 'models/gemini-test:generateContent')                  
                && $request->header('x-goog-api-key')[0] === 'secret'                                   
                && $request['generationConfig']['temperature'] === 0.1                                  
                && $request['contents'][0]['parts'][0]['text'] === 'Bonjour';                           
        });                                                                                             
    }                                                                                                   
                                                                                                        
                                                                                                        
    public function test_it_returns_null_when_the_answer_is_empty(): void                               
    {                                                                                                   
        config(['services.gemini.key' => 'secret']);                                                    
        Http::fake(['*' => Http::response(['candidates' => []])]);                                      
                                                                                                        
                                                                                                        
        $this->assertNull((new GeminiClient())->generate('Bonjour'));                                   
    }                                                                                                   
                                                                                                        
                                                                                                        
    public function test_it_returns_null_when_the_api_fails(): void                                     
    {                                                                                                   
        config(['services.gemini.key' => 'secret']);                                                    
        Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);                                  
                                                                                                        
                                                                                                        
        $this->assertNull((new GeminiClient())->generate('Bonjour'));                                   
    }                                                                                                   
}                                                                                                       