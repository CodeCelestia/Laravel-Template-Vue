<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_branding_page(): void
    {
        $response = $this->get(route('branding.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_branding_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('branding.edit'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('settings/Branding')
            ->has('branding', fn (Assert $branding) => $branding
                ->where('name', 'PRPIMS-SDN')
                ->where('logo', '/images/sdn-logo.png')
                ->where('favicon', '/favicon.ico')
                ->where('has_custom_logo', false)
                ->where('has_custom_favicon', false)
            )
        );
    }

    public function test_app_name_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('branding.update'), [
                'name' => 'Custom SDN Portal',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branding.edit'));

        $this->assertSame('Custom SDN Portal', Setting::get('branding_name'));
        $this->assertSame('Custom SDN Portal', Setting::getBranding()['name']);
    }

    public function test_app_name_requires_valid_name(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('branding.edit'))
            ->patch(route('branding.update'), [
                'name' => '',
            ]);

        $response
            ->assertSessionHasErrors('name')
            ->assertRedirect(route('branding.edit'));
    }

    public function test_logo_can_be_uploaded_and_stored(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('custom_logo.png', 512, 512);

        $response = $this
            ->actingAs($user)
            ->post(route('branding.logo.update'), [
                'logo' => $file,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branding.edit'));

        $storedLogo = Setting::get('branding_logo');
        $this->assertNotNull($storedLogo);
        Storage::disk('public')->assertExists($storedLogo);

        $branding = Setting::getBranding();
        $this->assertTrue($branding['has_custom_logo']);
        $this->assertSame('/storage/'.$storedLogo, $branding['logo']);
    }

    public function test_logo_can_be_reset_to_default(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('custom_logo.png', 512, 512);

        $this
            ->actingAs($user)
            ->post(route('branding.logo.update'), [
                'logo' => $file,
            ]);

        $storedLogo = Setting::get('branding_logo');
        Storage::disk('public')->assertExists($storedLogo);

        $response = $this
            ->actingAs($user)
            ->delete(route('branding.logo.destroy'));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branding.edit'));

        $this->assertNull(Setting::get('branding_logo'));
        Storage::disk('public')->assertMissing($storedLogo);

        $branding = Setting::getBranding();
        $this->assertFalse($branding['has_custom_logo']);
        $this->assertSame('/images/sdn-logo.png', $branding['logo']);
    }

    public function test_favicon_can_be_uploaded_and_stored(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('custom_favicon.png', 50, 'image/png');

        $response = $this
            ->actingAs($user)
            ->post(route('branding.favicon.update'), [
                'favicon' => $file,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branding.edit'));

        $storedFavicon = Setting::get('branding_favicon');
        $this->assertNotNull($storedFavicon);
        Storage::disk('public')->assertExists($storedFavicon);

        $branding = Setting::getBranding();
        $this->assertTrue($branding['has_custom_favicon']);
        $this->assertSame('/storage/'.$storedFavicon, $branding['favicon']);
    }

    public function test_favicon_can_be_reset_to_default(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('custom_favicon.png', 50, 'image/png');

        $this
            ->actingAs($user)
            ->post(route('branding.favicon.update'), [
                'favicon' => $file,
            ]);

        $storedFavicon = Setting::get('branding_favicon');
        Storage::disk('public')->assertExists($storedFavicon);

        $response = $this
            ->actingAs($user)
            ->delete(route('branding.favicon.destroy'));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('branding.edit'));

        $this->assertNull(Setting::get('branding_favicon'));
        Storage::disk('public')->assertMissing($storedFavicon);

        $branding = Setting::getBranding();
        $this->assertFalse($branding['has_custom_favicon']);
        $this->assertSame('/favicon.ico', $branding['favicon']);
    }
}
