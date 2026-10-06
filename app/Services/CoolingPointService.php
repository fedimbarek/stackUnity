<?php

namespace App\Services;

use App\Models\CoolingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoolingPointService
{
    protected string $overpassUrl = 'https://overpass-api.de/api/interpreter';

    public function fetchNearby(float $lat, float $lng, int $radius = 2000): array
    {
        $query = $this->buildOverpassQuery($lat, $lng, $radius);

        $response = Http::timeout(30)->get($this->overpassUrl, [
            'data' => $query,
        ]);

        if ($response->failed()) {
            Log::error('Overpass API error', ['status' => $response->status()]);
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

        // Sauvegarde en base (upsert par osm_id)
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
        out body;
        QUERY;
    }

    protected function normalizeElement(array $element): ?array
    {
        $tags = $element['tags'] ?? [];
        $lat = $element['lat'] ?? ($element['center']['lat'] ?? null);
        $lng = $element['lon'] ?? ($element['center']['lon'] ?? null);

        if (!$lat || !$lng) {
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
