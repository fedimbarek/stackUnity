<?php

namespace Tests\Feature;

use App\Models\Neighborhood;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        $neighborhood = Neighborhood::factory()->create();

        return User::factory()->create(array_merge(['neighborhood_id' => $neighborhood->id], $attrs));
    }

    private function payload(User $user, array $extra = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'neighborhood_id' => $user->neighborhood_id,
        ], $extra);
    }

    public function test_a_photo_can_be_uploaded(): void
    {
        Storage::fake('public');
        $user = $this->user();

        $this->actingAs($user)
            ->patch('/profile', $this->payload($user, ['photo' => UploadedFile::fake()->image('moi.jpg', 200, 200)]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $path = $user->fresh()->profile_photo_path;
        $this->assertStringStartsWith('profile-photos/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString($path, $user->fresh()->profile_photo_url);
    }

    public function test_uploading_a_new_photo_replaces_the_previous_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/ancienne.jpg', 'x');
        $user = $this->user(['profile_photo_path' => 'profile-photos/ancienne.jpg']);

        $this->actingAs($user)
            ->patch('/profile', $this->payload($user, ['photo' => UploadedFile::fake()->image('nouvelle.png')]))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('profile-photos/ancienne.jpg');
        Storage::disk('public')->assertExists($user->fresh()->profile_photo_path);
    }

    public function test_the_photo_can_be_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/moi.jpg', 'x');
        $user = $this->user(['profile_photo_path' => 'profile-photos/moi.jpg']);

        $this->actingAs($user)
            ->patch('/profile', $this->payload($user, ['remove_photo' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing('profile-photos/moi.jpg');
        $this->assertStringContainsString('undraw_profile.svg', $user->fresh()->profile_photo_url);
    }

    public function test_photo_must_be_a_small_image(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $this->actingAs($user);

        $this->patch('/profile', $this->payload($user, ['photo' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('photo');
        $this->patch('/profile', $this->payload($user, ['photo' => UploadedFile::fake()->image('enorme.jpg')->size(3000)]))
            ->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_changing_the_email_sends_a_new_verification_mail(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = $this->user();

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['email' => 'nouveau@test.tn']))
            ->assertSessionHasNoErrors();

        \Illuminate\Support\Facades\Notification::assertSentTo($user->fresh(), \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_profile_validation(): void
    {
        $user = $this->user();
        $other = $this->user(['email' => 'pris@test.tn']);
        $this->actingAs($user);

        $this->patch('/profile', $this->payload($user, ['name' => '']))->assertSessionHasErrors('name');
        $this->patch('/profile', $this->payload($user, ['email' => $other->email]))->assertSessionHasErrors('email');
        $this->patch('/profile', $this->payload($user, ['neighborhood_id' => 9999]))->assertSessionHasErrors('neighborhood_id');
        $this->patch('/profile', $this->payload($user, ['phone' => str_repeat('1', 21)]))->assertSessionHasErrors('phone');
    }

    public function test_deleting_the_account_removes_the_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/moi.jpg', 'x');
        $user = $this->user(['profile_photo_path' => 'profile-photos/moi.jpg']);

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertModelMissing($user);
        Storage::disk('public')->assertMissing('profile-photos/moi.jpg');
        $this->assertGuest();
    }
}
