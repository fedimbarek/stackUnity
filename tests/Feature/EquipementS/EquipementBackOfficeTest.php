<?php

namespace Tests\Feature\EquipementS;

use App\Models\Equipement;
use App\Services\EquipementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class EquipementBackOfficeTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function payload(array $override = []): array
    {
        return array_merge([
            'nom' => 'Ventilateur',
            'type_equipement' => 'Outillage',
            'date_ajout' => '2026-07-01',
            'prix_louer' => 12.5,
        ], $override);
    }

    public function test_guest_and_resident_cannot_open_the_back_office(): void
    {
        $this->get(route('admin.equipements.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.equipements.create'))->assertForbidden();
    }

    public function test_create_and_edit_forms_are_displayed(): void
    {
        $equipement = Equipement::factory()->create();
        $this->actingAs($this->gestionnaire());

        $this->get(route('admin.equipements.create'))->assertOk()->assertViewIs('Equipement.CreateEquipement');
        $this->get(route('admin.equipements.edit', $equipement))
            ->assertOk()
            ->assertViewIs('Equipement.EditEquipement')
            ->assertViewHas('equipement', fn ($e) => $e->is($equipement));
    }

    public function test_store_uploads_the_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.equipements.store'), $this->payload(['image' => UploadedFile::fake()->image('vent.jpg')]))
            ->assertRedirect(route('admin.equipements.index'))
            ->assertSessionHas('success', 'Équipement ajouté avec succès.');

        $equipement = Equipement::firstWhere('nom', 'Ventilateur');
        $this->assertStringStartsWith('equipements/', $equipement->image);
        Storage::disk('public')->assertExists($equipement->image);
    }

    public function test_store_rejects_an_invalid_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.equipements.store'), $this->payload(['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('equipements', 0);
    }

    public function test_update_replaces_the_image_and_deletes_the_previous_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('equipements/ancienne.jpg', 'x');
        $equipement = Equipement::factory()->create(['image' => 'equipements/ancienne.jpg']);

        $this->actingAs($this->admin())
            ->put(route('admin.equipements.update', $equipement), $this->payload([
                'nom' => 'Ventilateur Pro', 'image' => UploadedFile::fake()->image('nouvelle.jpg'),
            ]))
            ->assertRedirect(route('admin.equipements.index'))
            ->assertSessionHas('success', 'Équipement modifié avec succès.');

        $equipement->refresh();
        $this->assertSame('Ventilateur Pro', $equipement->nom);
        $this->assertNotSame('equipements/ancienne.jpg', $equipement->image);
        Storage::disk('public')->assertMissing('equipements/ancienne.jpg');
        Storage::disk('public')->assertExists($equipement->image);
    }

    public function test_update_without_a_new_image_keeps_the_old_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('equipements/garde.jpg', 'x');
        $equipement = Equipement::factory()->create(['image' => 'equipements/garde.jpg']);

        $this->actingAs($this->admin())
            ->put(route('admin.equipements.update', $equipement), $this->payload())
            ->assertRedirect(route('admin.equipements.index'));

        $this->assertSame('equipements/garde.jpg', $equipement->fresh()->image);
        Storage::disk('public')->assertExists('equipements/garde.jpg');
    }

    public function test_destroy_deletes_the_image_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('equipements/supprime.jpg', 'x');
        $equipement = Equipement::factory()->create(['image' => 'equipements/supprime.jpg']);

        $this->actingAs($this->admin())->delete(route('admin.equipements.destroy', $equipement))
            ->assertRedirect(route('admin.equipements.index'));

        $this->assertModelMissing($equipement);
        Storage::disk('public')->assertMissing('equipements/supprime.jpg');
    }

    public function test_confirming_twice_reports_that_the_reservation_is_already_confirmed(): void
    {
        $equipement = Equipement::factory()->create();
        $reservation = $equipement->reservations()->create([
            'nom' => 'Ben Ali', 'prenom' => 'Amira', 'email' => 'a@test.tn',
            'numero' => '22123456', 'date_debut' => now()->toDateString(), 'date_fin' => now()->addDay()->toDateString(),
        ]);
        $this->actingAs($this->admin());

        $this->patch(route('admin.equipements.reservations.confirm', [$equipement, $reservation]))
            ->assertSessionHas('success', 'La réservation a été confirmée.');
        $this->patch(route('admin.equipements.reservations.confirm', [$equipement, $reservation]))
            ->assertSessionHas('success', 'Cette réservation est déjà confirmée.');
    }

    public function test_service_finds_an_equipement_or_fails(): void
    {
        $equipement = Equipement::factory()->create();

        $this->assertTrue(app(EquipementService::class)->find($equipement->id)->is($equipement));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(EquipementService::class)->find(9999);
    }
}
