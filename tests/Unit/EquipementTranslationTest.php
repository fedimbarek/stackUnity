<?php

namespace Tests\Unit;

use App\Http\Controllers\EquipementC\Equipementc;
use App\Models\Equipement;
use App\Services\EquipementService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class EquipementTranslationTest extends TestCase
{
    public function test_equipement_can_be_translated_to_english(): void
    {
        $equipement = new Equipement([
            'nom' => 'Tente de secours',
            'type_equipement' => 'Hébergement',
        ]);
        $equipement->setAttribute('id', 4);

        $translator = Mockery::mock(TranslationService::class);
        $translator->shouldReceive('translate')
            ->once()
            ->with('Tente de secours', 'fr', 'en')
            ->andReturn('Emergency tent');
        $translator->shouldReceive('translate')
            ->once()
            ->with('Hébergement', 'fr', 'en')
            ->andReturn('Accommodation');

        $request = Request::create('/equipements/4/translate', 'GET', ['lang' => 'en']);

        $response = (new Equipementc(Mockery::mock(EquipementService::class)))
            ->translate($request, $equipement, $translator);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'id' => 4,
            'lang' => 'en',
            'success' => true,
            'nom' => 'Emergency tent',
            'type_equipement' => 'Accommodation',
        ], $response->getData(true));
    }

    public function test_translation_failure_returns_service_unavailable(): void
    {
        $equipement = new Equipement([
            'nom' => 'Tente de secours',
            'type_equipement' => 'Hébergement',
        ]);
        $translator = Mockery::mock(TranslationService::class);
        $translator->shouldReceive('translate')->twice()->andReturn(null);

        $request = Request::create('/equipements/1/translate', 'GET');

        $response = (new Equipementc(Mockery::mock(EquipementService::class)))
            ->translate($request, $equipement, $translator);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'La traduction est temporairement indisponible.',
        ], $response->getData(true));
    }
}
