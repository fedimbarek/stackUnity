<?php

namespace App\IA\CoolingIA;

class ModelStorage
{
    public static function path(): string
    {
        return storage_path('app/ia/cooling/recommender.json');
    }

    public static function save(LogisticRegression $model, array $meta = []): string
    {
        $dir = dirname(self::path());
        if (! is_dir($dir)) @mkdir($dir, 0755, true);

        file_put_contents(self::path(), json_encode([
            'version'    => $meta['version']    ?? '1.0',
            'trained_at' => $meta['trained_at'] ?? now()->toIso8601String(),
            'metrics'    => $meta['metrics']    ?? [],
            'bias'       => $model->bias,
            'weights'    => $model->weights,
        ], JSON_PRETTY_PRINT));

        return self::path();
    }

    public static function load(): ?LogisticRegression
    {
        if (! is_file(self::path())) return null;
        $data = json_decode(file_get_contents(self::path()), true);
        if (! is_array($data) || ! isset($data['weights'])) return null;
        return new LogisticRegression($data['weights'], (float) ($data['bias'] ?? 0));
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }
}