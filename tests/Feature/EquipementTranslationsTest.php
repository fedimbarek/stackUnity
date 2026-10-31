<?php

namespace Tests\Feature;

use App\Models\Equipement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipementTranslationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_translation_returns_names_and_types_for_existing_equipment(): void
    {
        $first = Equipement::factory()->create([
            'nom' => 'Réservoir',
            'type_equipement' => 'Hydratation',
        ]);
        $second = Equipement::factory()->create([
            'nom' => 'Grande réserve',
            'type_equipement' => 'Hydratation',
        ]);

        Http::fake([
            '*libretranslate.de/translate' => function (ClientRequest $request) {
                return Http::response([
                    'translatedText' => 'AR '.$request->data()['q'],
                ]);
            },
        ]);

        $this->getJson(route('front.equipements.translations', [
            'lang' => 'ar',
            'ids' => [$first->id, $second->id],
        ]))
            ->assertOk()
            ->assertExactJson([
                'lang' => 'ar',
                'items' => [
                    $first->id => [
                        'nom' => 'AR Réservoir',
                        'type_equipement' => 'AR Hydratation',
                    ],
                    $second->id => [
                        'nom' => 'AR Grande réserve',
                        'type_equipement' => 'AR Hydratation',
                    ],
                ],
            ]);

        Http::assertSentCount(3);
    }

    public function test_translation_failure_returns_the_original_french_text(): void
    {
        $equipement = Equipement::factory()->create([
            'nom' => 'Réservoir',
            'type_equipement' => 'Hydratation',
        ]);

        Http::fake([
            '*' => Http::response(['error' => 'Unavailable'], 503),
        ]);

        $this->getJson(route('front.equipements.translations', [
            'lang' => 'ar',
            'ids' => [$equipement->id],
        ]))
            ->assertOk()
            ->assertJsonPath("items.{$equipement->id}.nom", 'Réservoir')
            ->assertJsonPath("items.{$equipement->id}.type_equipement", 'Hydratation');
    }

    public function test_invalid_language_is_rejected(): void
    {
        $this->getJson(route('front.equipements.translations', ['lang' => 'xx']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lang');
    }

    public function test_french_language_does_not_send_external_requests(): void
    {
        $equipement = Equipement::factory()->create([
            'nom' => 'Réservoir',
            'type_equipement' => 'Hydratation',
        ]);

        Http::fake();

        $this->getJson(route('front.equipements.translations', [
            'lang' => 'fr',
            'ids' => [$equipement->id],
        ]))
            ->assertOk()
            ->assertJsonPath("items.{$equipement->id}.nom", 'Réservoir')
            ->assertJsonPath("items.{$equipement->id}.type_equipement", 'Hydratation');

        Http::assertNothingSent();
    }
}
