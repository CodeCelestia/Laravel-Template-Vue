<?php

namespace Tests\Feature;

use App\Models\Municipality;
use App\Models\User;
use Database\Seeders\MunicipalitySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MunicipalitySeeder::class);
    }

    public function test_guests_cannot_view_municipalities(): void
    {
        $response = $this->get(route('municipalities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_cannot_view_municipalities(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('municipalities.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_municipalities_index(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('municipalities.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('municipalities/Index')
            ->has('municipalities.data')
            ->has('filters')
            ->has('stats')
            ->where('stats.total_lgus', 21)
            ->where('stats.total_cities', 1)
            ->where('stats.district_1_count', 9)
            ->where('stats.district_2_count', 12)
        );
    }

    public function test_municipalities_can_be_filtered_by_district(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('municipalities.index', ['district' => 'District 1']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('municipalities/Index')
            ->where('municipalities.total', 9)
        );
    }

    public function test_municipalities_can_be_searched(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('municipalities.index', ['search' => 'Claver']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('municipalities/Index')
            ->where('municipalities.total', 1)
            ->where('municipalities.data.0.name', 'Claver')
        );
    }

    public function test_admin_can_create_municipality_with_location(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('municipalities.store'), [
            'name' => 'Dinagat Annex',
            'code' => 'DNX',
            'type' => 'municipality',
            'district' => 'District 1',
            'zip_code' => '8499',
            'location_description' => 'Sub-office, San Jose Port, Surigao del Norte',
            'latitude' => 10.0123,
            'longitude' => 125.6045,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('municipalities', [
            'name' => 'Dinagat Annex',
            'code' => 'DNX',
            'district' => 'District 1',
            'zip_code' => '8499',
            'latitude' => 10.0123,
            'longitude' => 125.6045,
        ]);
    }

    public function test_municipality_creation_validates_coordinates(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('municipalities.store'), [
            'name' => 'Invalid Coords LGU',
            'type' => 'municipality',
            'district' => 'District 2',
            'latitude' => 120.0, // Invalid latitude (> 90)
            'longitude' => 200.0, // Invalid longitude (> 180)
        ]);

        $response->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_admin_can_update_municipality(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $claver = Municipality::where('name', 'Claver')->firstOrFail();

        $response = $this->put(route('municipalities.update', $claver), [
            'name' => 'Claver (Mining Capital)',
            'code' => 'CLV',
            'type' => 'municipality',
            'district' => 'District 2',
            'zip_code' => '8410',
            'location_description' => 'Updated Municipal Complex, Tayaga, Claver',
            'latitude' => 9.5180,
            'longitude' => 125.7350,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('municipalities', [
            'id' => $claver->id,
            'name' => 'Claver (Mining Capital)',
            'latitude' => 9.5180,
        ]);
    }

    public function test_admin_can_delete_unused_municipality(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $testMuni = Municipality::create([
            'name' => 'Test Temp LGU',
            'code' => 'TMP',
            'type' => 'municipality',
            'district' => 'District 2',
            'is_active' => true,
        ]);

        $response = $this->delete(route('municipalities.destroy', $testMuni));

        $response->assertRedirect();
        $this->assertSoftDeleted('municipalities', [
            'id' => $testMuni->id,
        ]);
    }

    public function test_municipalities_can_be_paginated(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('municipalities.index', ['per_page' => 10, 'page' => 2]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('municipalities/Index')
            ->where('municipalities.current_page', 2)
            ->where('municipalities.per_page', 10)
            ->where('municipalities.last_page', 3)
            ->where('municipalities.total', 21)
            ->where('municipalities.from', 11)
            ->where('municipalities.to', 20)
        );
    }

    public function test_municipalities_per_page_can_be_adjusted(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('municipalities.index', ['per_page' => 50, 'page' => 1]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('municipalities/Index')
            ->where('municipalities.current_page', 1)
            ->where('municipalities.per_page', 50)
            ->where('municipalities.last_page', 1)
            ->where('municipalities.total', 21)
            ->where('municipalities.from', 1)
            ->where('municipalities.to', 21)
        );
    }
}
