<?php

namespace Tests\Unit\Console;

use App\IA\CoolingIA\ModelStorage;
use Tests\Concerns\UsesTemporaryStorage;
use Tests\TestCase;

class TrainCoolingModelCommandTest extends TestCase
{
    use UsesTemporaryStorage;

    public function test_command_trains_and_saves_the_model_with_its_metrics(): void
    {
        $this->assertFalse(ModelStorage::exists());

        $this->artisan('ia:cooling-train', ['--samples' => 200, '--epochs' => 30])
            ->expectsOutputToContain('Training model')
            ->expectsOutputToContain('Model saved')
            ->expectsOutputToContain('Train accuracy')
            ->expectsOutputToContain('Test MSE')
            ->assertSuccessful();

        $this->assertTrue(ModelStorage::exists());

        $saved = json_decode(file_get_contents(ModelStorage::path()), true);
        $this->assertSame('1.0', $saved['version']);
        $this->assertArrayHasKey('train_accuracy', $saved['metrics']);
        $this->assertArrayHasKey('test_accuracy', $saved['metrics']);
        $this->assertArrayHasKey('loss', $saved['metrics']);
        $this->assertNotNull(ModelStorage::load());
    }
}
