<?php

namespace Tests\Feature\Contacts;

use App\Http\Requests\ContactCategoryRequest;
use App\Models\ContactCategory;
use App\Models\EmergencyContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class EmergencyContactsTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function contact(array $attrs = []): EmergencyContact
    {
        return EmergencyContact::factory()->create($attrs);
    }

    private function contactPayload(ContactCategory $category, array $override = []): array
    {
        return array_merge([
            'contact_category_id' => $category->id,
            'name' => 'Hôpital Charles Nicolle',
            'phone' => '+216 71 578 000',
            'address' => 'Boulevard 9 Avril',
            'city' => 'Tunis',
            'description' => 'Urgences médicales',
            'is_24h' => 1,
            'is_priority' => 1,
            'is_active' => 1,
        ], $override);
    }

    // ================================================================ FRONT (public)

    public function test_public_index_lists_only_active_contacts_with_category_counts(): void
    {
        $health = ContactCategory::factory()->create(['name' => 'Santé']);
        $this->contact(['contact_category_id' => $health->id, 'name' => 'Clinique Active']);
        $this->contact(['contact_category_id' => $health->id, 'name' => 'Clinique Fermee', 'is_active' => false]);

        $response = $this->get(route('contacts.index'))->assertOk()->assertViewIs('contacts.index');

        $response->assertSee('Clinique Active')->assertDontSee('Clinique Fermee');
        $this->assertSame(1, $response->viewData('categories')->firstWhere('id', $health->id)->contacts_count);
    }

    public function test_public_index_exposes_priority_contacts(): void
    {
        $priority = $this->contact(['name' => 'SAMU', 'is_priority' => true]);
        $this->contact(['name' => 'Pharmacie', 'is_priority' => false]);

        $this->get(route('contacts.index'))
            ->assertViewHas('priorityContacts', fn ($list) => $list->count() === 1 && $list->first()->is($priority));
    }

    public function test_public_index_search_and_category_filter(): void
    {
        $a = ContactCategory::factory()->create();
        $b = ContactCategory::factory()->create();
        $this->contact(['contact_category_id' => $a->id, 'name' => 'Protection Civile', 'city' => 'Tunis', 'phone' => '198']);
        $this->contact(['contact_category_id' => $b->id, 'name' => 'STEG Depannage', 'city' => 'Sfax', 'phone' => '71341077']);

        $names = fn ($r) => $r->viewData('contacts')->pluck('name')->all();

        $this->assertSame(['Protection Civile'], $names($this->get(route('contacts.index', ['q' => 'Protection']))));
        $this->assertSame(['STEG Depannage'], $names($this->get(route('contacts.index', ['q' => 'Sfax']))));
        $this->assertSame(['STEG Depannage'], $names($this->get(route('contacts.index', ['q' => '7134']))));
        $this->assertSame(['STEG Depannage'], $names($this->get(route('contacts.index', ['category' => $b->id]))));
    }

    public function test_public_index_puts_24h_contacts_first(): void
    {
        $this->contact(['name' => 'Aaa Heures de bureau', 'is_24h' => false]);
        $this->contact(['name' => 'Zzz Permanence', 'is_24h' => true]);

        $names = $this->get(route('contacts.index'))->viewData('contacts')->pluck('name')->all();

        $this->assertSame(['Zzz Permanence', 'Aaa Heures de bureau'], $names);
    }

    public function test_public_show_displays_the_contact_and_related_ones(): void
    {
        $category = ContactCategory::factory()->create();
        $contact = $this->contact(['contact_category_id' => $category->id, 'name' => 'Contact Principal']);
        $related = $this->contact(['contact_category_id' => $category->id, 'name' => 'Contact Voisin']);
        $this->contact(['contact_category_id' => $category->id, 'name' => 'Contact Inactif', 'is_active' => false]);

        $this->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertViewIs('contacts.show')
            ->assertSee('Contact Principal')
            ->assertViewHas('related', fn ($list) => $list->count() === 1 && $list->first()->is($related));
    }

    public function test_public_show_hides_inactive_contacts(): void
    {
        $this->get(route('contacts.show', $this->contact(['is_active' => false])))->assertNotFound();
    }

    public function test_phone_link_attribute_strips_formatting(): void
    {
        $this->assertSame('+21671578000', $this->contact(['phone' => '+216 71 578 000'])->phone_link);
    }

    public function test_model_relations(): void
    {
        $category = ContactCategory::factory()->create();
        $contact = $this->contact(['contact_category_id' => $category->id]);

        $this->assertTrue($contact->category->is($category));
        $this->assertTrue($category->contacts->first()->is($contact));
    }

    // ================================================================ BACK (admin + gestionnaire)

    public function test_back_office_is_forbidden_for_guests_and_residents(): void
    {
        $this->get(route('admin.contacts.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.contacts.index'))->assertForbidden();
        $this->get(route('admin.contact-categories.index'))->assertForbidden();
    }

    public function test_admin_index_search_filter_and_pagination(): void
    {
        $a = ContactCategory::factory()->create();
        $b = ContactCategory::factory()->create();
        $this->contact(['contact_category_id' => $a->id, 'name' => 'Pompiers Nord', 'is_active' => false]);
        $this->contact(['contact_category_id' => $b->id, 'name' => 'Police Sud']);
        $this->actingAs($this->gestionnaire());

        $names = fn ($r) => $r->viewData('contacts')->pluck('name')->all();

        // l'admin voit aussi les contacts inactifs
        $this->assertEqualsCanonicalizing(['Pompiers Nord', 'Police Sud'], $names($this->get(route('admin.contacts.index'))));
        $this->assertSame(['Pompiers Nord'], $names($this->get(route('admin.contacts.index', ['q' => 'Pompiers']))));
        $this->assertSame(['Police Sud'], $names($this->get(route('admin.contacts.index', ['category' => $b->id]))));
        $this->get(route('admin.contacts.index'))->assertViewHas('categories');

        EmergencyContact::factory()->count(12)->create();
        $this->assertCount(10, $this->get(route('admin.contacts.index'))->viewData('contacts'));
    }

    public function test_admin_create_and_edit_forms(): void
    {
        $category = ContactCategory::factory()->create();
        $contact = $this->contact(['contact_category_id' => $category->id]);
        $this->actingAs($this->admin());

        $this->get(route('admin.contacts.create'))->assertOk()->assertViewHas('categories');
        $this->get(route('admin.contacts.edit', $contact))->assertOk()->assertViewHas('contact', fn ($c) => $c->is($contact));
    }

    public function test_admin_can_create_a_contact(): void
    {
        $category = ContactCategory::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.contacts.store'), $this->contactPayload($category))
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success', 'Contact d\'urgence ajouté avec succès.');

        $this->assertDatabaseHas('emergency_contacts', [
            'name' => 'Hôpital Charles Nicolle', 'is_24h' => 1, 'is_priority' => 1, 'contact_category_id' => $category->id,
        ]);
    }

    public function test_contact_validation_rules(): void
    {
        $category = ContactCategory::factory()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.contacts.store'), [])
            ->assertSessionHasErrors(['contact_category_id', 'name', 'phone']);
        $this->post(route('admin.contacts.store'), $this->contactPayload($category, ['contact_category_id' => 999]))
            ->assertSessionHasErrors(['contact_category_id' => 'La catégorie choisie est invalide.']);
        $this->post(route('admin.contacts.store'), $this->contactPayload($category, ['name' => 'ab']))
            ->assertSessionHasErrors('name');
        $this->post(route('admin.contacts.store'), $this->contactPayload($category, ['phone' => 'not-a-phone']))
            ->assertSessionHasErrors('phone');
        $this->post(route('admin.contacts.store'), $this->contactPayload($category, ['description' => str_repeat('a', 1001)]))
            ->assertSessionHasErrors('description');
        $this->post(route('admin.contacts.store'), $this->contactPayload($category, ['is_24h' => 'peut-etre']))
            ->assertSessionHasErrors('is_24h');

        $this->assertDatabaseCount('emergency_contacts', 0);
    }

    public function test_request_attribute_names_are_translated(): void
    {
        $category = ContactCategory::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.contacts.store'), $this->contactPayload($category, ['city' => str_repeat('v', 101)]))
            ->assertSessionHasErrors('city');
        $this->assertStringContainsString('ville', session('errors')->first('city'));
    }

    public function test_admin_can_update_a_contact(): void
    {
        $category = ContactCategory::factory()->create();
        $contact = $this->contact(['contact_category_id' => $category->id, 'name' => 'Ancien nom']);

        $this->actingAs($this->admin())
            ->put(route('admin.contacts.update', $contact), $this->contactPayload($category, ['name' => 'Nouveau nom']))
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success', 'Contact modifié avec succès.');

        $this->assertSame('Nouveau nom', $contact->fresh()->name);

        $this->put(route('admin.contacts.update', $contact), $this->contactPayload($category, ['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_delete_a_contact(): void
    {
        $contact = $this->contact();

        $this->actingAs($this->admin())->delete(route('admin.contacts.destroy', $contact))
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success', 'Contact supprimé.');

        $this->assertModelMissing($contact);
    }

    // ================================================================ CATÉGORIES

    public function test_category_pages_are_displayed(): void
    {
        $category = ContactCategory::factory()->create();
        $this->contact(['contact_category_id' => $category->id]);
        $this->actingAs($this->admin());

        $this->get(route('admin.contact-categories.index'))
            ->assertOk()
            ->assertViewHas('categories', fn ($p) => $p->first()->contacts_count === 1);
        $this->get(route('admin.contact-categories.create'))->assertOk();
        $this->get(route('admin.contact-categories.edit', $category))
            ->assertOk()
            ->assertViewHas('category', fn ($c) => $c->is($category));
    }

    public function test_category_can_be_created_updated_and_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.contact-categories.store'), ['name' => 'Eau potable', 'icon' => 'bi-droplet', 'color' => '#00aaff'])
            ->assertRedirect(route('admin.contact-categories.index'))
            ->assertSessionHas('success', 'Catégorie ajoutée.');
        $category = ContactCategory::firstWhere('name', 'Eau potable');
        $this->assertNotNull($category);

        $this->put(route('admin.contact-categories.update', $category), ['name' => 'Eau potable', 'color' => '#112233'])
            ->assertRedirect(route('admin.contact-categories.index'))
            ->assertSessionHas('success', 'Catégorie modifiée.');
        $this->assertSame('#112233', $category->fresh()->color);

        $this->post(route('admin.contact-categories.store'), ['name' => 'ab', 'color' => 'rouge'])
            ->assertSessionHasErrors(['name', 'color']);
        $this->post(route('admin.contact-categories.store'), ['name' => 'Eau potable', 'color' => '#00aaff'])
            ->assertSessionHasErrors(['name' => 'Cette catégorie existe déjà.']);
    }

    public function test_category_with_contacts_cannot_be_deleted(): void
    {
        $category = ContactCategory::factory()->create();
        $this->contact(['contact_category_id' => $category->id]);

        $this->actingAs($this->admin())->delete(route('admin.contact-categories.destroy', $category))
            ->assertSessionHas('error', 'Impossible : cette catégorie contient encore des contacts.');

        $this->assertModelExists($category);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = ContactCategory::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.contact-categories.destroy', $category))
            ->assertRedirect(route('admin.contact-categories.index'))
            ->assertSessionHas('success', 'Catégorie supprimée.');

        $this->assertModelMissing($category);
    }

    public function test_category_request_exposes_the_allowed_icons(): void
    {
        $this->assertContains('bi-hospital', ContactCategoryRequest::icons());
        $this->assertCount(8, ContactCategoryRequest::icons());
    }
}
