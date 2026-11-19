<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\AlmanacMethods\AlmanacMethodResource;
use App\Models\AlmanacMethod;
use App\Models\User;
use Database\Seeders\AlmanacMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlmanacFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AlmanacMethodSeeder::class);
    }

    public function test_admin_can_access_almanac_method_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertTrue(AlmanacMethodResource::canViewAny());
        $method = AlmanacMethod::first();
        $this->assertNotNull($method);
        $this->assertTrue(AlmanacMethodResource::canEdit($method));

        // Verify AlmanacOverrideResource is removed
        $this->assertFalse(class_exists('App\Filament\Resources\AlmanacOverrides\AlmanacOverrideResource'));
    }

    public function test_regular_user_cannot_access_almanac_method_resource(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $method = AlmanacMethod::first();
        $this->assertFalse(AlmanacMethodResource::canViewAny());
        $this->assertFalse(AlmanacMethodResource::canEdit($method));
    }

    public function test_guest_cannot_access_almanac_method_resource(): void
    {
        $method = AlmanacMethod::first();
        $this->assertFalse(AlmanacMethodResource::canViewAny());
        $this->assertFalse(AlmanacMethodResource::canEdit($method));
    }
}
