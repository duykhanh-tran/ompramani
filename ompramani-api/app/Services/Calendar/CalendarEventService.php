<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Enums\CalendarEventRecurrence;
use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;

class CalendarEventService
{
    public function __construct(
        protected CalendarEventOccurrenceService $occurrenceService
    ) {}

    /**
     * Get published event by slug with relations and upcoming occurrences.
     */
    public function getPublishedEventBySlug(string $slug): ?array
    {
        $event = CalendarEvent::query()
            ->published()
            ->with(['traditions', 'buddhistFigures'])
            ->where('slug', $slug)
            ->first();

        if ($event === null) {
            if (! FixedCalendarEventRegistry::has($slug)) {
                return null;
            }
            FixedCalendarEventRegistry::syncToDatabase();
            $event = CalendarEvent::query()
                ->published()
                ->with(['traditions', 'buddhistFigures'])
                ->where('slug', $slug)
                ->first();

            if ($event === null) {
                return null;
            }
        }

        $upcoming = $this->occurrenceService->previewEventOccurrences($event, 3);

        return [
            'id' => $event->id,
            'slug' => $event->slug,
            'title' => $event->title,
            'short_description' => $event->short_description,
            'description' => $event->description,
            'event_type' => $event->event_type->value,
            'event_type_label' => $event->event_type->label(),
            'event_type_color' => $event->event_type->color(),
            'calendar_system' => $event->calendar_system->value,
            'recurrence_type' => $event->recurrence_type->value,
            'rule_summary' => $this->summarizeRule($event),
            'is_general' => $event->is_general,
            'traditions' => $event->traditions->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug]),
            'buddhist_figures' => $event->buddhistFigures->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'slug' => $f->slug, 'thumbnail' => $f->thumbnail]),
            'source' => [
                'title' => $event->source_title,
                'url' => $event->source_url,
                'note' => $event->source_note,
                'last_verified_at' => $event->last_verified_at?->toIso8601String(),
            ],
            'upcoming_occurrences' => array_slice($upcoming, 0, 5),
        ];
    }

    /**
     * Get list of all event types and descriptions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventTypes(): array
    {
        $types = [];
        foreach (CalendarEventType::cases() as $type) {
            $types[] = [
                'key' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
                'color' => $type->color(),
                'priority' => $type->priority(),
            ];
        }

        return $types;
    }

    /**
     * Provide a human-readable summary of the recurrence rule.
     */
    public function summarizeRule(CalendarEvent $event): string
    {
        $fixed = FixedCalendarEventRegistry::get($event->slug);
        if ($fixed !== null && isset($fixed['rule_summary'])) {
            return $fixed['rule_summary'];
        }

        return match ($event->recurrence_type) {
            CalendarEventRecurrence::AnnualLunar => sprintf('Hằng năm vào ngày %02d/%02d Âm lịch (Chính sách nhuận: %s)', $event->lunar_day, $event->lunar_month, $event->lunar_leap_policy?->label() ?? 'Chỉ tháng chính'),
            CalendarEventRecurrence::MonthlyLunar => sprintf('Hằng tháng vào các ngày Âm lịch: %s', implode(', ', $event->lunar_days_json ?: [$event->lunar_day ?: 1])),
            CalendarEventRecurrence::OneTimeLunar => sprintf('Một lần duy nhất vào ngày %02d/%02d/%04d%s Âm lịch', $event->lunar_day, $event->lunar_month, $event->specific_lunar_year, $event->lunar_is_leap_month ? ' (Nhuận)' : ''),
            CalendarEventRecurrence::AnnualSolar => sprintf('Hằng năm vào ngày %02d/%02d Dương lịch', $event->solar_day, $event->solar_month),
            CalendarEventRecurrence::OneTimeSolar => sprintf('Một lần duy nhất vào ngày %s Dương lịch', $event->specific_solar_date?->format('d/m/Y')),
            CalendarEventRecurrence::SolarTerm => 'Tiết khí Lập Xuân xác định theo kinh độ Mặt Trời (315°)',
        };
    }
}
