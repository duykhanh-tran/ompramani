<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\PrayerIntentions\Pages\ListPrayerIntentions;
use App\Filament\Resources\PrayerIntentions\Pages\ViewPrayerIntention;
use App\Filament\Resources\PrayerIntentions\PrayerIntentionResource;
use App\Filament\Resources\PrayerIntentions\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\PrayerResponses\PrayerResponseResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerRoomFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_has_only_one_navigation_item_phong_loi_nguyen(): void
    {
        // PrayerIntentionResource is now the unified "Phòng Lời Nguyện"
        $this->assertEquals('Phòng Lời Nguyện', PrayerIntentionResource::getNavigationLabel());
        $this->assertEquals('Phòng Lời Nguyện', PrayerIntentionResource::getPluralModelLabel());

        // PrayerResponseResource is removed from navigation
        $this->assertFalse(PrayerResponseResource::shouldRegisterNavigation());
    }

    public function test_phong_loi_nguyen_includes_responses_relation_manager(): void
    {
        $relations = PrayerIntentionResource::getRelations();
        $this->assertContains(ResponsesRelationManager::class, $relations);
    }

    public function test_regular_user_cannot_access_phong_loi_nguyen_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->assertFalse(PrayerIntentionResource::canViewAny());
        $this->assertFalse(PrayerIntentionResource::canCreate());
    }

    public function test_admin_can_access_phong_loi_nguyen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->assertTrue(PrayerIntentionResource::canViewAny());
    }
}
