<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\File;

/**
 * Redirige storage_path() vers un dossier temporaire pendant le test, pour que
 * les exports CSV (storage/reports) n'écrasent jamais de vrais fichiers.
 */
trait UsesTemporaryStorage
{
    protected string $temporaryStorage = '';

    protected function setUpUsesTemporaryStorage(): void
    {
        $this->temporaryStorage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'heatalert_tests_'.uniqid();
        File::ensureDirectoryExists($this->temporaryStorage);
        $this->app->useStoragePath($this->temporaryStorage);
    }

    protected function tearDownUsesTemporaryStorage(): void
    {
        File::deleteDirectory($this->temporaryStorage);
    }
}