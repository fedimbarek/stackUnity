<?php

namespace App\IA\CoolingIA;

class LogisticRegression
{
    public array $weights = [];
    public float $bias    = 0.0;

    public function __construct(array $weights = [], float $bias = 0.0)
    {
        $this->weights = $weights;
        $this->bias    = $bias;
    }

    public function sigmoid(float $z): float
    {
        return 1.0 / (1.0 + exp(-$z));
    }

    public function score(array $features): float
    {
        $z = $this->bias;
        foreach ($this->weights as $name => $w) {
            $z += $w * ($features[$name] ?? 0.0);
        }
        return $this->sigmoid($z);
    }

    /** Batch gradient descent on binary cross-entropy. */
    public function train(array $samples, int $epochs = 500, float $lr = 0.1): array
    {
        $features = FeatureExtractor::FEATURES;
        $this->weights = array_fill_keys($features, 0.0);
        $this->bias = 0.0;

        $n = count($samples);
        if ($n === 0) {
            return ['samples' => 0, 'loss' => 0.0];
        }

        $lastLoss = INF;
        for ($e = 0; $e < $epochs; $e++) {
            $gradW = array_fill_keys($features, 0.0);
            $gradB = 0.0;
            $loss  = 0.0;

            foreach ($samples as $s) {
                $x = $s['features'];
                $y = (float) $s['label'];
                $yHat = $this->score($x);
                $err  = $yHat - $y;
                foreach ($features as $f) {
                    $gradW[$f] += $err * ($x[$f] ?? 0.0);
                }
                $gradB += $err;
                $loss -= $y * log($yHat + 1e-9) + (1 - $y) * log(1 - $yHat + 1e-9);
            }

            foreach ($features as $f) {
                $this->weights[$f] -= $lr * $gradW[$f] / $n;
            }
            $this->bias -= $lr * $gradB / $n;
            $lastLoss = $loss / $n;
        }

        return ['samples' => $n, 'loss' => round($lastLoss, 4)];
    }

    public function evaluate(array $samples): array
    {
        $n = count($samples);
        if ($n === 0) {
            return ['samples' => 0, 'accuracy' => 0.0, 'mse' => 0.0];
        }
        $correct = 0;
        $sqErr = 0.0;
        foreach ($samples as $s) {
            $p = $this->score($s['features']);
            $y = (float) $s['label'];
            $sqErr += ($p - $y) ** 2;
            if (($p >= 0.5 ? 1 : 0) === ($y >= 0.5 ? 1 : 0)) $correct++;
        }
        return [
            'samples'  => $n,
            'accuracy' => round($correct / $n, 4),
            'mse'      => round($sqErr / $n, 4),
        ];
    }
}