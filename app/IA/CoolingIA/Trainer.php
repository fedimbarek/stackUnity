<?php

namespace App\IA\CoolingIA;

class Trainer
{
    /**
     * Synthetic labels from a non-linear "ground truth" + noise.
     * Remplaceable plus tard par du vrai feedback utilisateur.
     */
    public function generateSyntheticData(int $count = 2000, int $seed = 42): array
    {
        mt_srand($seed);
        $samples = [];
        for ($i = 0; $i < $count; $i++) {
            $x = [
                'distance_score' => mt_rand() / mt_getrandmax(),
                'heat_level'     => mt_rand() / mt_getrandmax(),
                'type_heat_fit'  => mt_rand() / mt_getrandmax(),
                'hour_open'      => mt_rand(0, 1),
                'has_address'    => mt_rand(0, 1),
                'has_hours'      => mt_rand(0, 1),
                'source_manual'  => mt_rand(0, 1),
            ];
            $x['heat_fit_interaction'] = $x['heat_level'] * $x['type_heat_fit'];

            $logit = 2.2 * $x['distance_score']
                   + 1.4 * $x['heat_fit_interaction']
                   + 0.8 * $x['hour_open']
                   + 0.5 * $x['has_address']
                   + 0.7 * $x['source_manual']
                   - 1.6;

            $pTrue = 1.0 / (1.0 + exp(-$logit));
            $noisy = $pTrue + (mt_rand() / mt_getrandmax() - 0.5) * 0.15;
            $noisy = max(0.0, min(1.0, $noisy));

            $samples[] = ['features' => $x, 'label' => $noisy];
        }
        return $samples;
    }

    public function train(int $count = 2000, int $epochs = 400, float $lr = 0.1): array
    {
        $data  = $this->generateSyntheticData($count);
        $cut   = (int) (count($data) * 0.8);
        $train = array_slice($data, 0, $cut);
        $test  = array_slice($data, $cut);

        $model = new LogisticRegression();
        $trainMetrics = $model->train($train, $epochs, $lr);

        return [
            'model' => $model,
            'train' => $trainMetrics + $model->evaluate($train),
            'test'  => $model->evaluate($test),
        ];
    }
}