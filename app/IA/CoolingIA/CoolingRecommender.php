<?php

namespace App\IA\CoolingIA;

use App\Models\CoolingPoint;
use Illuminate\Support\Collection;

class CoolingRecommender
{
    protected ?LogisticRegression $model = null;
    protected FeatureExtractor $extractor;

    public function __construct(?LogisticRegression $model = null)
    {
        $this->model     = $model ?? ModelStorage::load();
        $this->extractor = new FeatureExtractor();
    }

    public function isReady(): bool { return $this->model !== null; }

    public function score(CoolingPoint $point, array $context): ?float
    {
        if (! $this->model) return null;
        $features = $this->extractor->extract($point, $context);
        return round($this->model->score($features), 4);
    }

    /** Ajoute ->ai_score à chaque point et trie par pertinence. */
    public function rank(Collection $points, array $context): Collection
    {
        if (! $this->model) return $points;

        return $points->map(function ($p) use ($context) {
            $p->ai_score = $this->score($p, $context);
            return $p;
        })->sortByDesc('ai_score')->values();
    }
}