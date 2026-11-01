<?php

namespace App\Console\Commands;

use App\IA\CoolingIA\ModelStorage;
use App\IA\CoolingIA\Trainer;
use Illuminate\Console\Command;

class TrainCoolingModel extends Command
{
    protected $signature = 'ia:cooling-train {--samples=2000} {--epochs=400}';
    protected $description = 'Train the cooling points recommendation model';

    public function handle(): int
    {
        $this->info('🎓 Training model...');
        $result = (new Trainer())->train(
            (int) $this->option('samples'),
            (int) $this->option('epochs'),
        );

        $path = ModelStorage::save($result['model'], [
            'version' => '1.0',
            'metrics' => [
                'train_accuracy' => $result['train']['accuracy'],
                'test_accuracy'  => $result['test']['accuracy'],
                'loss'           => $result['train']['loss'],
            ],
        ]);

        $this->info("✅ Model saved: {$path}");
        $this->table(['Metric', 'Value'], [
            ['Train accuracy', $result['train']['accuracy']],
            ['Test accuracy',  $result['test']['accuracy']],
            ['Loss',           $result['train']['loss']],
            ['Test MSE',       $result['test']['mse']],
        ]);

        return self::SUCCESS;
    }
}