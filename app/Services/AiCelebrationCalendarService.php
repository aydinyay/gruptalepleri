<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiCelebrationCalendarService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcoming(int $days = 7, bool $forceRefresh = false): array
    {
        $safeDays = max(1, min($days, 30));
        $start = now()->startOfDay();
        $cacheKey = sprintf(
            'ai_celebration_upcoming_%s_%d',
            $start->toDateString(),
            $safeDays
        );

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($start, $safeDays): array {
            return $this->buildUpcoming(CarbonImmutable::parse($start), $safeDays);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildUpcoming(CarbonImmutable $start, int $days): array
    {
        $end = $start->addDays($days - 1)->endOfDay();
        $years = range($start->year, $end->year);
        $events = [];

        foreach ($years as $year) {
            foreach ((array) config('special_days.fixed', []) as $definition) {
                $date = CarbonImmutable::create(
                    $year,
                    (int) ($definition['month'] ?? 1),
                    (int) ($definition['day'] ?? 1),
                    0,
                    0,
                    0,
                    config('app.timezone', 'UTC')
                );
                $this->pushIfInRange($events, $definition, $date, $start, $end);
            }

            foreach ((array) config('special_days.floating', []) as $definition) {
                $date = $this->resolveFloatingDate((string) ($definition['rule'] ?? ''), (int) $year);
                if ($date === null) {
                    continue;
                }
                $this->pushIfInRange($events, $definition, $date, $start, $end);
            }

            $yearly = (array) data_get(config('special_days.yearly', []), (string) $year, []);
            foreach ($yearly as $definition) {
                if (empty($definition['date'])) {
                    continue;
                }
                $date = CarbonImmutable::parse((string) $definition['date'])->startOfDay();
                $this->pushIfInRange($events, $definition, $date, $start, $end);
            }
        }

        usort($events, function (array $a, array $b): int {
            if ($a['event_date'] === $b['event_date']) {
                return ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100);
            }
            return strcmp($a['event_date'], $b['event_date']);
        });

        return $events;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @param  array<string, mixed>  $definition
     */
    private function pushIfInRange(
        array &$events,
        array $definition,
        CarbonImmutable $date,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): void {
        if (! $date->betweenIncluded($start, $end)) {
            return;
        }

        $name = trim((string) ($definition['name'] ?? 'Ozel Gun'));
        $slug = (string) ($definition['key'] ?? Str::slug($name));

        $events[] = [
            'source_key' => $slug . '-' . $date->format('Ymd'),
            'event_key' => $slug,
            'event_name' => $name,
            'event_date' => $date->toDateString(),
            'category' => (string) ($definition['category'] ?? 'genel'),
            'default_prompt' => (string) ($definition['default_prompt'] ?? ''),
            'priority' => (int) ($definition['priority'] ?? 100),
        ];
    }

    private function resolveFloatingDate(string $rule, int $year): ?CarbonImmutable
    {
        return match ($rule) {
            'second_sunday_may' => $this->nthWeekdayOfMonth($year, 5, 7, 2),
            'third_sunday_june' => $this->nthWeekdayOfMonth($year, 6, 7, 3),
            'last_monday_march' => $this->lastWeekdayOfMonth($year, 3, 1),
            default => null,
        };
    }

    private function nthWeekdayOfMonth(int $year, int $month, int $isoWeekday, int $nth): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone', 'UTC'));
        $shift = ($isoWeekday - $date->dayOfWeekIso + 7) % 7;

        return $date
            ->addDays($shift)
            ->addWeeks(max($nth - 1, 0))
            ->startOfDay();
    }

    private function lastWeekdayOfMonth(int $year, int $month, int $isoWeekday): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone', 'UTC'))
            ->endOfMonth()
            ->startOfDay();

        while ($date->dayOfWeekIso !== $isoWeekday) {
            $date = $date->subDay();
        }

        return $date;
    }
}
