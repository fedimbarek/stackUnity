<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_boot_creates_the_default_roles_once(): void
    {
        Role::query()->delete();
        $provider = new AppServiceProvider($this->app);

        $provider->boot();
        $provider->boot(); // idempotent

        $this->assertEqualsCanonicalizing(
            ['admin', 'gestionnaire', 'resident'],
            Role::where('guard_name', 'web')->pluck('name')->all()
        );
        $this->assertSame(3, Role::count());
    }

    public function test_boot_keeps_the_existing_roles(): void
    {
        $admin = Role::findOrCreate('admin', 'web');

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame($admin->id, Role::where('name', 'admin')->value('id'));
    }

    public function test_boot_sets_the_default_string_length_and_bootstrap_four_pagination(): void
    {
        (new AppServiceProvider($this->app))->boot();

        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertSame('pagination::bootstrap-4', Paginator::$defaultView);
    }
}
