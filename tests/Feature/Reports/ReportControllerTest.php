<?php

namespace Tests\Feature\Reports;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithRoles;
use Tests\Concerns\UsesTemporaryStorage;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles, UsesTemporaryStorage;

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Rapport de juillet',
            'period_start' => '2026-07-06',
            'period_end' => '2026-07-12',
            'neighborhood_id' => null,
        ], $override);
    }

    private function reportFor(User $author, ?Neighborhood $n = null, array $attrs = []): Report
    {
        return Report::factory()->create(array_merge([
            'neighborhood_id' => $n?->id,
            'generated_by' => $author->id,
            'period_start' => '2026-07-06',
            'period_end' => '2026-07-12',
            'status' => 'generated',
        ], $attrs));
    }

    private function writeCsv(Report $report, string $name = 'fichier.csv'): string
    {
        $dir = storage_path('reports');
        @mkdir($dir, 0775, true);
        file_put_contents($dir.DIRECTORY_SEPARATOR.$name, "Quartier,Nombre de coupures\n");
        $report->update(['file_path' => $name]);

        return $dir.DIRECTORY_SEPARATOR.$name;
    }

    public function test_guest_is_redirected_and_resident_is_forbidden(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('reports.index'))->assertForbidden();
    }

    public function test_admin_and_gestionnaire_can_list_reports(): void
    {
        $admin = $this->admin();
        $this->reportFor($admin, null, ['title' => 'Rapport global']);

        $this->actingAs($admin)->get(route('reports.index'))
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertSee('Rapport global');

        $this->actingAs($this->gestionnaire())->get(route('reports.index'))->assertOk();
    }

    public function test_create_form_receives_the_neighborhoods(): void
    {
        $n = Neighborhood::factory()->create();

        $this->actingAs($this->admin())->get(route('reports.create'))
            ->assertOk()
            ->assertViewHas('neighborhoods', fn ($list) => $list->contains($n));
    }

    public function test_store_creates_the_report_and_generates_the_csv(): void
    {
        $admin = $this->admin();
        $n = Neighborhood::factory()->create(['name' => 'Lafayette']);
        PowerOutage::create([
            'neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user',
            'started_at' => '2026-07-07 10:00:00',
        ]);

        $response = $this->actingAs($admin)->post(route('reports.store'), $this->payload());

        $report = Report::withoutGlobalScopes()->first();
        $response->assertRedirect(route('reports.show', $report))->assertSessionHas('status', 'report-created');

        $this->assertSame('generated', $report->status);
        $this->assertSame($admin->id, $report->generated_by);
        $this->assertSame('report-'.$report->id.'.csv', $report->file_path);
        $csv = file_get_contents(storage_path('reports/'.$report->file_path));
        $this->assertStringContainsString('Lafayette', $csv);
    }

    public function test_store_validates_the_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('reports.store'), $this->payload([
                'title' => '', 'period_end' => '2026-07-01', 'neighborhood_id' => 9999,
            ]))
            ->assertSessionHasErrors(['title', 'period_end', 'neighborhood_id']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_show_displays_the_kpis(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin, null, ['title' => 'Bilan']);

        $this->actingAs($admin)->get(route('reports.show', $report))
            ->assertOk()
            ->assertViewIs('reports.show')
            ->assertViewHas('kpis')
            ->assertSee('Bilan');
    }

    public function test_edit_and_update_regenerate_the_csv(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin);

        $this->actingAs($admin)->get(route('reports.edit', $report))
            ->assertOk()
            ->assertViewHas('report', fn ($r) => $r->is($report));

        $this->put(route('reports.update', $report), $this->payload(['title' => 'Titre modifié']))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('status', 'report-updated');

        $report->refresh();
        $this->assertSame('Titre modifié', $report->title);
        $this->assertSame('generated', $report->status);
        $this->assertSame('report-'.$report->id.'.csv', $report->file_path);
        $this->assertFileExists(storage_path('reports/'.$report->file_path));
    }

    public function test_update_keeps_the_existing_file_name(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin, null, ['file_path' => 'existant.csv']);

        $this->actingAs($admin)->put(route('reports.update', $report), $this->payload())->assertRedirect();

        $this->assertSame('existant.csv', $report->fresh()->file_path);
        $this->assertFileExists(storage_path('reports/existant.csv'));
    }

    public function test_update_is_rejected_with_invalid_data(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin);

        $this->actingAs($admin)->put(route('reports.update', $report), $this->payload(['period_end' => '2020-01-01']))
            ->assertSessionHasErrors('period_end');
    }

    public function test_destroy_deletes_the_report_and_its_file(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin);
        $path = $this->writeCsv($report);

        $this->actingAs($admin)->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('status', 'report-deleted');

        $this->assertModelMissing($report);
        $this->assertFileDoesNotExist($path);
    }

    public function test_destroy_works_without_a_file(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin, null, ['file_path' => null]);

        $this->actingAs($admin)->delete(route('reports.destroy', $report))->assertRedirect(route('reports.index'));

        $this->assertModelMissing($report);
    }

    public function test_download_returns_the_csv_or_404(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin, null, ['file_path' => null]);

        $this->actingAs($admin)->get(route('reports.download', $report))->assertNotFound();

        $this->writeCsv($report);
        $this->get(route('reports.download', $report))
            ->assertOk()
            ->assertDownload($report->title.'.csv');
    }

    public function test_send_requires_a_generated_file(): void
    {
        $admin = $this->admin();
        $report = $this->reportFor($admin, null, ['file_path' => null]);

        $this->actingAs($admin)->post(route('reports.send', $report))->assertStatus(422);
    }

    public function test_send_emails_the_admins_and_marks_the_report_as_sent(): void
    {
        $admin = $this->admin(null, ['email' => 'boss@test.tn']);
        $report = $this->reportFor($admin);
        $this->writeCsv($report);

        $this->actingAs($admin)->post(route('reports.send', $report))
            ->assertRedirect()
            ->assertSessionHas('status', 'report-sent');

        $report->refresh();
        $this->assertSame('sent', $report->status);
        $this->assertNotNull($report->sent_at);
        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('boss@test.tn', $messages->first()->toString());
    }

    public function test_send_marks_the_report_as_failed_when_the_mail_cannot_be_sent(): void
    {
        $gestionnaire = $this->gestionnaire();
        $report = $this->reportFor($gestionnaire);
        $this->writeCsv($report);
        Role::findOrCreate('admin', 'web'); // le rôle existe mais aucun admin => aucun destinataire

        $this->actingAs($gestionnaire)->post(route('reports.send', $report))
            ->assertSessionHas('status', 'report-send-failed');

        $report->refresh();
        $this->assertSame('failed', $report->status);
        $this->assertNull($report->sent_at);
    }

    public function test_gestionnaire_only_sees_reports_of_his_neighborhood_or_global_ones(): void
    {
        $mine = Neighborhood::factory()->create();
        $other = Neighborhood::factory()->create();
        $admin = $this->admin();
        $gestionnaire = $this->gestionnaire($mine);

        $own = $this->reportFor($admin, $mine, ['title' => 'Rapport du quartier']);
        $global = $this->reportFor($admin, null, ['title' => 'Rapport global']);
        $foreign = $this->reportFor($admin, $other, ['title' => 'Rapport etranger']);

        $this->actingAs($gestionnaire)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Rapport du quartier')
            ->assertSee('Rapport global')
            ->assertDontSee('Rapport etranger');

        $this->get(route('reports.show', $foreign))->assertNotFound();
        $this->assertSame(2, Report::count());
        $this->assertSame(3, Report::withoutGlobalScopes()->count());

        $this->actingAs($admin);
        $this->assertSame(3, Report::count());
    }

    public function test_report_scope_does_not_filter_guests_nor_residents(): void
    {
        $admin = $this->admin();
        $this->reportFor($admin, Neighborhood::factory()->create());
        $this->reportFor($admin, null);

        auth()->logout();
        $this->assertSame(2, Report::count());

        $this->actingAs($this->resident());
        $this->assertSame(2, Report::count());
    }

    public function test_policy_rules(): void
    {
        $mine = Neighborhood::factory()->create();
        $other = Neighborhood::factory()->create();
        $admin = $this->admin();
        $gestionnaire = $this->gestionnaire($mine);
        $resident = $this->resident($mine);

        $own = $this->reportFor($gestionnaire, $mine);
        $global = $this->reportFor($admin, null);
        $foreign = $this->reportFor($admin, $other);

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Report::class));
        $this->assertTrue(Gate::forUser($gestionnaire)->allows('viewAny', Report::class));
        $this->assertFalse(Gate::forUser($resident)->allows('viewAny', Report::class));
        $this->assertFalse(Gate::forUser($resident)->allows('create', Report::class));

        $this->assertTrue(Gate::forUser($admin)->allows('view', $foreign));
        $this->assertTrue(Gate::forUser($gestionnaire)->allows('view', $global));
        $this->assertTrue(Gate::forUser($gestionnaire)->allows('view', $own));
        $this->assertFalse(Gate::forUser($gestionnaire)->allows('view', $foreign));
        $this->assertFalse(Gate::forUser($gestionnaire)->allows('update', $foreign));

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $own));
        $this->assertTrue(Gate::forUser($gestionnaire)->allows('delete', $own));
        $this->assertFalse(Gate::forUser($gestionnaire)->allows('delete', $global));
    }

    public function test_report_model_relations(): void
    {
        $admin = $this->admin();
        $n = Neighborhood::factory()->create();
        $report = $this->reportFor($admin, $n);

        $this->assertTrue($report->generatedBy->is($admin));
        $this->assertTrue($report->neighborhood->is($n));
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $report->period_start);
    }
}
