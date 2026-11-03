<?php

namespace App\Support;

use App\Models\ProjectEvent;
use Illuminate\Support\Collection;

class ConsecutiveProjectEvents
{
    /** All events connected to the anchor by overlapping or consecutive calendar days. */
    public function forEvent(ProjectEvent $anchor): Collection
    {
        $events = ProjectEvent::query()
            ->where('project_id', $anchor->project_id)
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $group = collect();
        $end = null;

        foreach ($events as $event) {
            $start = $event->starts_at->copy()->startOfDay();

            if ($end && $start->gt($end->copy()->addDay())) {
                if ($group->contains('id', $anchor->id)) {
                    break;
                }

                $group = collect();
                $end = null;
            }

            $group->push($event);
            $eventEnd = ($event->ends_at ?: $event->starts_at)->copy()->startOfDay();
            $end = $end ? $end->max($eventEnd) : $eventEnd;
        }

        if (! $group->contains('id', $anchor->id) || $group->count() < 2) {
            return collect();
        }

        // Several events on a single day do not form a consecutive-day program.
        return $group->first()->starts_at->isSameDay($end) ? collect() : $group;
    }
}
