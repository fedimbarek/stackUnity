<?php

namespace Tests\Feature\Ai;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class OutageAssistantTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function geminiAnswer(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }

    private function ask(string $question = 'Y a-t-il une coupure ?')
    {
        return $this->postJson(route('ai.assistant.ask'), ['question' => $question]);
    }

    private function lastPrompt(): string
    {
        $prompt = '';
        Http::assertSent(function (Request $request) use (&$prompt) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return true;
        });

        return $prompt;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('ai.assistant'))->assertRedirect(route('login'));
        $this->post(route('ai.assistant.ask'), ['question' => 'bonjour'])->assertRedirect(route('login'));
    }

    public function test_index_page_is_displayed_for_a_resident(): void
    {
        $this->actingAs($this->resident())->get(route('ai.assistant'))->assertOk()->assertViewIs('ai.assistant');
    }

    public function test_question_is_validated(): void
    {
        $this->actingAs($this->resident());

        $this->postJson(route('ai.assistant.ask'), [])->assertUnprocessable()->assertJsonValidationErrors('question');
        $this->ask('ab')->assertUnprocessable()->assertJsonValidationErrors('question');
        $this->ask(str_repeat('a', 301))->assertUnprocessable()->assertJsonValidationErrors('question');
    }

    public function test_answer_comes_from_gemini_when_available(): void
    {
        config(['services.gemini.key' => 'secret']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiAnswer('Aucune coupure.'))]);
        $this->actingAs($this->resident(Neighborhood::factory()->create()));

        $this->ask()->assertOk()->assertExactJson(['answer' => 'Aucune coupure.']);

        $prompt = $this->lastPrompt();
        $this->assertStringContainsString('Y a-t-il une coupure ?', $prompt);
        $this->assertStringContainsString('UNIQUEMENT à partir du CONTEXTE', $prompt);
    }

    public function test_fallback_answer_contains_the_context_when_gemini_is_unavailable(): void
    {
        config(['services.gemini.key' => null]);
        $n = Neighborhood::factory()->create(['name' => 'Ezzahra']);

        $answer = $this->actingAs($this->resident($n))->ask()->assertOk()->json('answer');

        $this->assertStringContainsString('Assistant IA indisponible', $answer);
        $this->assertStringContainsString('Quartier Ezzahra', $answer);
    }

    public function test_user_without_neighborhood_gets_a_specific_context(): void
    {
        config(['services.gemini.key' => null]);

        $answer = $this->actingAs($this->resident())->ask()->json('answer');

        $this->assertStringContainsString("n'a pas encore de quartier", $answer);
    }

    public function test_resident_context_is_limited_to_his_own_neighborhood(): void
    {
        config(['services.gemini.key' => null]);
        $mine = Neighborhood::factory()->create(['name' => 'Mon Quartier']);
        Neighborhood::factory()->create(['name' => 'Quartier Voisin']);

        $answer = $this->actingAs($this->resident($mine))->ask()->json('answer');

        $this->assertStringContainsString('Mon Quartier', $answer);
        $this->assertStringNotContainsString('Quartier Voisin', $answer);
    }

    public function test_admin_context_lists_every_neighborhood_active_outages_and_weather(): void
    {
        config(['services.gemini.key' => null]);
        $a = Neighborhood::factory()->create(['name' => 'Quartier A']);
        $b = Neighborhood::factory()->create(['name' => 'Quartier B']);
        $withEta = PowerOutage::create([
            'neighborhood_id' => $a->id, 'status' => 'confirmed', 'source' => 'user',
            'started_at' => now()->subHour(), 'estimated_end_time' => now()->addHour(),
        ]);
        $withoutEta = PowerOutage::create([
            'neighborhood_id' => $b->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()->subMinutes(10),
        ]);
        PowerOutage::create([
            'neighborhood_id' => $b->id, 'status' => 'resolved', 'source' => 'user',
            'started_at' => now()->subDay(), 'resolved_at' => now()->subHours(20),
        ]);
        WeatherForecast::factory()->ofLevel('canicule')->create(['neighborhood_id' => $a->id, 'date' => today()]);

        $answer = $this->actingAs($this->admin())->ask()->json('answer');

        $this->assertStringContainsString('Quartier A', $answer);
        $this->assertStringContainsString('Quartier B', $answer);
        $this->assertStringContainsString("Coupure #{$withEta->id} confirmed", $answer);
        $this->assertStringContainsString("Coupure #{$withoutEta->id} reported", $answer);
        $this->assertStringNotContainsString('resolved depuis', $answer);
        $this->assertStringContainsString('(canicule)', $answer);
        $this->assertStringContainsString('non disponible', $answer); // quartier B sans prévision
    }

    public function test_the_ask_endpoint_is_rate_limited(): void
    {
        config(['services.gemini.key' => null]);
        $this->actingAs($this->resident());

        for ($i = 0; $i < 10; $i++) {
            $this->ask()->assertOk();
        }

        $this->ask()->assertStatus(429);
    }
}
