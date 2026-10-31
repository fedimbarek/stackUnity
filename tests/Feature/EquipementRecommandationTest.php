<?php

namespace Tests\Feature;

use App\Models\Equipement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EquipementRecommandationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdvisorTablesExist();
        DB::table('weather_alerts')->delete();
        DB::table('weather_forecasts')->delete();
        DB::table('equipements')->delete();

        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
        ]);
    }

    public function test_valid_ai_response_returns_only_available_equipment(): void
    {
        $available = Equipement::factory()->create([
            'nom' => 'Gourde filtrante',
            'type_equipement' => 'Hydratation',
            'etat' => 'disponible',
        ]);
        $reserved = Equipement::factory()->create([
            'nom' => 'Lampe réservée',
            'type_equipement' => 'Éclairage',
            'etat' => 'reserve',
        ]);
        $this->fakeGeminiResponse(json_encode([
            'recommandations' => [
                ['id' => $available->id, 'raison' => 'Elle facilite l’accès à l’eau potable.'],
                ['id' => $reserved->id, 'raison' => 'Cet équipement est réservé.'],
            ],
            'conseil' => 'Pensez à boire régulièrement.',
        ], JSON_UNESCAPED_UNICODE));

        $this->postJson(route('front.equipements.recommander'), [
            'besoin' => 'Besoin d’eau pour la famille',
        ])
            ->assertOk()
            ->assertJsonPath('source', 'ia')
            ->assertJsonPath('recommandations.0.id', $available->id)
            ->assertJsonPath('recommandations.0.nom', 'Gourde filtrante')
            ->assertJsonPath('recommandations.0.type_equipement', 'Hydratation')
            ->assertJsonPath('recommandations.0.raison', 'Elle facilite l’accès à l’eau potable.')
            ->assertJsonCount(1, 'recommandations');
    }

    public function test_ai_recommendations_with_invented_ids_are_ignored(): void
    {
        $available = Equipement::factory()->create([
            'etat' => 'disponible',
        ]);
        $this->fakeGeminiResponse(json_encode([
            'recommandations' => [
                ['id' => 999999, 'raison' => 'Cet équipement inventé ne doit pas être retourné.'],
                ['id' => $available->id, 'raison' => 'Cet équipement existe et est disponible.'],
            ],
            'conseil' => 'Voici un conseil.',
        ], JSON_UNESCAPED_UNICODE));

        $this->postJson(route('front.equipements.recommander'), [
            'besoin' => 'Je cherche un équipement',
        ])
            ->assertOk()
            ->assertJsonPath('source', 'ia')
            ->assertJsonCount(1, 'recommandations')
            ->assertJsonPath('recommandations.0.id', $available->id);
    }

    public function test_invalid_ai_json_uses_rule_based_recommendations(): void
    {
        Equipement::factory()->create([
            'nom' => 'Gourde',
            'type_equipement' => 'Hydratation',
            'etat' => 'disponible',
        ]);
        $this->fakeGeminiResponse('ceci n’est pas du JSON');

        $this->postJson(route('front.equipements.recommander'), [
            'besoin' => 'Besoin d’eau pendant une coupure',
        ])
            ->assertOk()
            ->assertJsonPath('source', 'regles')
            ->assertJsonPath('recommandations.0.nom', 'Gourde');
    }

    public function test_ai_http_error_uses_rule_based_recommendations(): void
    {
        Equipement::factory()->create([
            'nom' => 'Lampe de secours',
            'type_equipement' => 'Éclairage',
            'etat' => 'disponible',
        ]);
        Http::fake([
            '*generativelanguage.googleapis.com/*' => Http::response(['error' => 'Unavailable'], 503),
        ]);

        $this->postJson(route('front.equipements.recommander'), [
            'besoin' => 'Prévoir une coupure de courant',
        ])
            ->assertOk()
            ->assertJsonPath('source', 'regles')
            ->assertJsonPath('recommandations.0.nom', 'Lampe de secours');
    }

    public function test_empty_or_overlong_need_is_rejected(): void
    {
        $this->postJson(route('front.equipements.recommander'), ['besoin' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('besoin');

        $this->postJson(route('front.equipements.recommander'), [
            'besoin' => str_repeat('a', 501),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('besoin');
    }

    private function fakeGeminiResponse(string $text): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                ]],
            ]),
        ]);
    }

    private function ensureAdvisorTablesExist(): void
    {
        if (! Schema::hasTable('equipements')) {
            Schema::create('equipements', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('type_equipement');
                $table->date('date_ajout');
                $table->string('image')->nullable();
                $table->decimal('prix_louer', 10, 2);
                $table->string('etat')->default('disponible');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('neighborhoods')) {
            Schema::create('neighborhoods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('weather_forecasts')) {
            Schema::create('weather_forecasts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('neighborhood_id');
                $table->date('date');
                $table->float('temp_max');
                $table->float('temp_min');
                $table->string('level');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('weather_alerts')) {
            Schema::create('weather_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('neighborhood_id')->nullable();
                $table->string('level');
                $table->string('source');
                $table->text('message');
                $table->timestamps();
            });
        }
    }
}
