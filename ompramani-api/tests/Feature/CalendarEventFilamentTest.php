<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\FixedCalendarEventRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FixedCalendarEventRegistry::syncToDatabase();
    }

    public function test_admin_can_access_calendar_event_resource_but_cannot_create_or_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertTrue(CalendarEventResource::canViewAny());
        // Fixed Event Registry in code: cannot create arbitrary events, cannot delete fixed events
        $this->assertFalse(CalendarEventResource::canCreate());

        $event = CalendarEvent::first();
        $this->assertNotNull($event);
        $this->assertTrue(CalendarEventResource::canEdit($event));
        $this->assertFalse(CalendarEventResource::canDelete($event));
    }

    public function test_regular_user_cannot_access_calendar_event_resource(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->assertFalse(CalendarEventResource::canViewAny());
        $this->assertFalse(CalendarEventResource::canCreate());

        $event = CalendarEvent::first();
        $this->assertNotNull($event);
        $this->assertFalse(CalendarEventResource::canEdit($event));
        $this->assertFalse(CalendarEventResource::canDelete($event));
    }

    public function test_guest_cannot_access_calendar_event_resource(): void
    {
        $this->assertFalse(CalendarEventResource::canViewAny());
        $this->assertFalse(CalendarEventResource::canCreate());

        $event = CalendarEvent::first();
        $this->assertNotNull($event);
        $this->assertFalse(CalendarEventResource::canEdit($event));
        $this->assertFalse(CalendarEventResource::canDelete($event));
    }
}
