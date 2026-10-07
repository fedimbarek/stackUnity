<?php

namespace App\Services;

use App\Models\CoolingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoolingPointService
{
    protected string $overpassUrl = 'https://overpass-api.de/api/interpreter';

    // Miroirs de secours (essayés dans l'ordre si le principal échoue)
    protected array $mirrors = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
    ];

    public function fetchNearby(float $lat, float $lng, int $radius = 2000): array
    {
        $query = $this->buildOverpassQuery($lat, $lng, $radius);

        $response = null;
        $lastStatus = null;

        foreach ($this->mirrors as $url) {
            $response = Http::withHeaders([
                'User-Agent' => 'HeatAlert/1.0 (contact@heatalert.tn)',
                'Accept'     => 'application/json',
            ])
                ->timeout(90)
                ->retry(2, 2000, throw: false)
                ->withOptions(['verify' => false])
                ->asForm()
                ->post($url, ['data' => $query]);

            if ($response->successful()) {
                Log::info('Overpass OK', ['mirror' => $url]);
                break;
            }

            $lastStatus = $response->status();
            Log::warning('Overpass mirror failed', [
                'url'    => $url,
                'status' => $lastStatus,
                'body'   => substr($response->body(), 0, 200),
            ]);

            $response = null;
        }

        if (! $response) {
            Log::error('All Overpass mirrors failed', ['last_status' => $lastStatus]);
            return [];
        }

        $elements = $response->json('elements', []);
        $points = [];

        foreach ($elements as $element) {
            $point = $this->normalizeElement($element);
            if ($point) {
                $points[] = $point;
            }
        }

        foreach ($points as $point) {
            CoolingPoint::updateOrCreate(
                ['osm_id' => $point['osm_id']],
                $point
            );
        }

        return $points;
    }

    protected function buildOverpassQuery(float $lat, float $lng, int $radius): string
    {
        return <<<QUERY
        [out:json][timeout:25];
        (
          node["amenity"="drinking_water"](around:{$radius},{$lat},{$lng});
          way["leisure"="park"](around:{$radius},{$lat},{$lng});
          node["shop"="mall"](around:{$radius},{$lat},{$lng});
          way["leisure"="garden"](around:{$radius},{$lat},{$lng});
        );
        out center;
        QUERY;
    }

    protected function normalizeElement(array $element): ?array
    {
        $tags = $element['tags'] ?? [];
        $lat = $element['lat'] ?? ($element['center']['lat'] ?? null);
        $lng = $element['lon'] ?? ($element['center']['lon'] ?? null);

        if (! $lat || ! $lng) {
            return null;
        }

        $type = 'park';
        if (($tags['amenity'] ?? '') === 'drinking_water') {
            $type = 'fountain';
        } elseif (($tags['shop'] ?? '') === 'mall') {
            $type = 'mall';
        }

        return [
            'name'          => $tags['name'] ?? 'Point de fraîcheur',
            'type'          => $type,
            'latitude'      => (float) $lat,
            'longitude'     => (float) $lng,
            'address'       => $tags['addr:street'] ?? null,
            'opening_hours' => $tags['opening_hours'] ?? null,
            'source'        => 'osm',
            'osm_id'        => $element['id'] . '_' . $element['type'],
        ];
    }
}
