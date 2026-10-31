<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    public function translate(string $text, string $source = 'fr', string $target = 'en'): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Cache 30 jours : évite de rappeler l'API pour le même texte
        return Cache::remember(
            "translation:{$source}:{$target}:" . md5($text),
            now()->addDays(30),
            function () use ($text, $source, $target) {
                foreach ($this->providers() as $name => $provider) {
                    try {
                        $translated = $provider($text, $source, $target);

                        if (!$translated) {
                            throw new \RuntimeException('Traduction vide');
                        }

                        Log::info('Translation successful', ['service' => $name]);
                        return $translated;
                    } catch (\Throwable $e) {
                        Log::warning('Translation attempt failed', [
                            'service' => $name,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }

                return null; // rien n'est mis en cache si tout a échoué
            }
        );
    }

    private function providers(): array
    {
        return [
            'libretranslate' => fn ($q, $s, $t) => Http::timeout(5)
                ->asJson()
                ->post('https://libretranslate.de/translate', [
                    'q' => $q, 'source' => $s, 'target' => $t, 'format' => 'text',
                ])
                ->throw()
                ->json('translatedText'),

            'mymemory' => fn ($q, $s, $t) => Http::timeout(5)
                ->get('https://api.mymemory.translated.net/get', [
                    'q' => $q, 'langpair' => "{$s}|{$t}",
                ])
                ->throw()
                ->json('responseData.translatedText'),
        ];
    }
}