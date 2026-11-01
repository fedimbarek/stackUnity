<?php

namespace Tests\Feature\Weather;

use App\Models\AlertThreshold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AlertThresholdControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    public function test_only_an_admin_can_open_the_thresholds(): void
    {
        $this->get(route('admin.weather.thresholds.edit'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.weather.thresholds.edit'))->assertForbidden();
        $this->actingAs($this->gestionnaire())->get(route('admin.weather.thresholds.edit'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.weather.thresholds.edit'))->assertOk();
    }

    public function test_edit_page_receives_the_configured_thresholds(): void
    {
        config(['services.weather.heat_warning' => 35, 'services.weather.heat_threshold' => 38]);

        $this->actingAs($this->admin())->get(route('admin.weather.thresholds.edit'))
            ->assertOk()
            ->assertViewIs('admin.weather.thresholds.edit')
            ->assertViewHas('thresholds', ['warning' => 35.0, 'canicule' => 38.0]);
    }

    public function test_edit_page_prefers_the_thresholds_stored_in_database(): void
    {
        AlertThreshold::create(['level' => 'warning', 'temp_max' => 33]);
        AlertThreshold::create(['level' => 'canicule', 'temp_max' => 41]);

        $this->actingAs($this->admin())->get(route('admin.weather.thresholds.edit'))
            ->assertViewHas('thresholds', ['warning' => 33.0, 'canicule' => 41.0]);
    }

    public function test_update_creates_the_two_thresholds(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.weather.thresholds.update'), ['warning' => 34, 'canicule' => 39.5])
            ->assertRedirect(route('admin.weather.thresholds.edit'))
            ->assertSessionHas('status');

        $this->assertSame(34.0, AlertThreshold::where('level', 'warning')->value('temp_max'));
        $this->assertSame(39.5, AlertThreshold::where('level', 'canicule')->value('temp_max'));
    }

    public function test_update_modifies_existing_thresholds_without_duplicating_them(): void
    {
        AlertThreshold::create(['level' => 'warning', 'temp_max' => 30]);
        AlertThreshold::create(['level' => 'canicule', 'temp_max' => 36]);

        $this->actingAs($this->admin())
            ->put(route('admin.weather.thresholds.update'), ['warning' => 35, 'canicule' => 40]);

        $this->assertSame(2, AlertThreshold::count());
        $this->assertSame(35.0, AlertThreshold::where('level', 'warning')->value('temp_max'));
        $this->assertSame(40.0, AlertThreshold::where('level', 'canicule')->value('temp_max'));
    }

    public function test_update_is_rejected_with_invalid_values(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.weather.thresholds.update'), [])
            ->assertSessionHasErrors(['warning', 'canicule']);

        $this->actingAs($admin)->put(route('admin.weather.thresholds.update'), ['warning' => 5, 'canicule' => 80])
            ->assertSessionHasErrors(['warning', 'canicule']);

        $this->actingAs($admin)->put(route('admin.weather.thresholds.update'), ['warning' => 40, 'canicule' => 38])
            ->assertSessionHasErrors('canicule');

        $this->assertSame(0, AlertThreshold::count());
    }

    public function test_non_admin_cannot_update(): void
    {
        $this->actingAs($this->gestionnaire())
            ->put(route('admin.weather.thresholds.update'), ['warning' => 35, 'canicule' => 40])
            ->assertForbidden();

        $this->assertSame(0, AlertThreshold::count());
    }
}
