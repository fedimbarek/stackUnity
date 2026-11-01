<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\ForceJsonResponse;
use App\Models\AdminAuditLog;
use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AdminBackOfficeTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    // ---------------------------------------------------------------- dashboard

    public function test_dashboard_is_reserved_to_admins(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->gestionnaire())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_dashboard_shows_the_totals_and_weekly_kpis(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Menzel']);
        PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()]);
        $admin = $this->admin();
        $this->resident($n);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('totalUsers', 2)
            ->assertViewHas('totalNeighborhoods', 1)
            ->assertViewHas('kpis', fn ($k) => $k['current']['total_outages'] === 1)
            ->assertViewHas('byNeighborhood', fn ($rows) => $rows->first()->neighborhood === 'Menzel');
    }

    // ---------------------------------------------------------------- utilisateurs

    public function test_users_index_is_paginated_and_reserved_to_admins(): void
    {
        $this->actingAs($this->gestionnaire())->get(route('admin.users.index'))->assertForbidden();

        User::factory()->count(12)->create();

        $response = $this->actingAs($this->admin())->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewIs('admin.users.index');

        $this->assertCount(10, $response->viewData('users'));
        $this->assertSame(14, $response->viewData('users')->total()); // 12 + gestionnaire + admin
    }

    public function test_admin_can_change_the_role_of_another_user(): void
    {
        Role::findOrCreate('gestionnaire', 'web');
        $target = $this->resident();

        $this->actingAs($this->admin())
            ->put(route('admin.users.updateRole', $target), ['role' => 'gestionnaire'])
            ->assertRedirect()
            ->assertSessionHas('status', 'role-updated');

        $target->refresh();
        $this->assertTrue($target->hasRole('gestionnaire'));
        $this->assertFalse($target->hasRole('resident'));
    }

    public function test_admin_cannot_change_his_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.updateRole', $admin), ['role' => 'resident'])
            ->assertSessionHasErrors(['role' => 'Tu ne peux pas modifier ton propre rôle.']);

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_role_must_be_a_known_value(): void
    {
        $target = $this->resident();
        $this->actingAs($this->admin());

        $this->put(route('admin.users.updateRole', $target), ['role' => 'superman'])->assertSessionHasErrors('role');
        $this->put(route('admin.users.updateRole', $target), [])->assertSessionHasErrors('role');
        $this->assertTrue($target->fresh()->hasRole('resident'));
    }

    public function test_non_admin_cannot_change_roles(): void
    {
        $target = $this->resident();

        $this->actingAs($this->gestionnaire())
            ->put(route('admin.users.updateRole', $target), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    // ---------------------------------------------------------------- journal d'audit

    public function test_audit_log_page_is_reserved_to_admins(): void
    {
        $this->get(route('admin.audit-logs.index'))->assertRedirect(route('login'));
        $this->actingAs($this->gestionnaire())->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertViewIs('audit-logs.index')
            ->assertViewHas('logs');
    }

    public function test_observer_logs_created_updated_and_deleted_actions_of_a_logged_in_user(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        AdminAuditLog::query()->delete();

        $n = Neighborhood::create(['name' => 'Gammarth', 'city' => 'Tunis']);
        $n->update(['city' => 'La Marsa']);
        $n->delete();

        $logs = AdminAuditLog::orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame('Neighborhood', $logs[0]->entity_type);
        $this->assertSame($n->id, $logs[0]->entity_id);
        $this->assertSame($admin->id, $logs[0]->admin_id);
        $this->assertSame('Gammarth', $logs[0]->new_values['name']);
        $this->assertNull($logs[0]->old_values);
        $this->assertSame('La Marsa', $logs[1]->new_values['city']);
        $this->assertSame('Tunis', $logs[1]->old_values['city']);
        $this->assertNull($logs[2]->new_values);
        $this->assertTrue($logs[0]->admin->is($admin));
    }

    public function test_observer_ignores_changes_made_without_a_logged_in_user(): void
    {
        Neighborhood::create(['name' => 'Seeder Quartier', 'city' => 'Tunis']);

        $this->assertDatabaseCount('admin_audit_logs', 0);
    }

    public function test_audit_log_page_lists_the_recorded_actions(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        Neighborhood::create(['name' => 'Soukra', 'city' => 'Ariana']);

        $this->get(route('admin.audit-logs.index'))->assertOk()->assertSee('Neighborhood');
    }

    // ---------------------------------------------------------------- middleware

    public function test_force_json_middleware_sets_the_accept_header(): void
    {
        $request = Request::create('/api/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);

        $response = (new ForceJsonResponse())->handle($request, fn (Request $r) => response($r->header('Accept')));

        $this->assertSame('application/json', $response->getContent());
        $this->assertTrue($request->expectsJson());
    }
}
