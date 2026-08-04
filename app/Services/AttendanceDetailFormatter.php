<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AttendanceDetailFormatter
{
    public function buildDayDetail(Collection $logs, ?int $inTs = null, ?int $outTs = null): array
    {
        $inEvents = $logs->where('status', 'IN')->values();
        $outEvents = $logs->where('status', 'OUT')->values();

        $inEvent = $this->resolveEvent($inEvents, $inTs);
        $outEvent = $this->resolveEvent($outEvents, $outTs);

        $inSource = $this->formatSource($inEvent);
        $outSource = $this->formatSource($outEvent);

        $sourceSummary = $this->buildSourceSummary($inSource, $outSource);

        return [
            'in' => [
                'event' => $inEvent,
                'timestamp' => $inTs,
                'source' => $inSource,
                'photo_url' => $inEvent?->photo_url ?? null,
                'is_mobile_app' => $this->isMobileApp($inEvent),
            ],
            'out' => [
                'event' => $outEvent,
                'timestamp' => $outTs,
                'source' => $outSource,
                'photo_url' => $outEvent?->photo_url ?? null,
                'is_mobile_app' => $this->isMobileApp($outEvent),
            ],
            'source_summary' => $sourceSummary,
            'has_mobile_photo' => $this->hasMobilePhoto($inEvent, $outEvent),
        ];
    }

    private function resolveEvent(Collection $events, ?int $timestamp): ?object
    {
        if ($events->isEmpty()) {
            return null;
        }

        if ($timestamp !== null) {
            foreach ($events as $event) {
                $eventTimestamp = strtotime((string) ($event->datetime ?? ''));
                if ($eventTimestamp === $timestamp) {
                    return $event;
                }
            }
        }

        return $events->first();
    }

    private function formatSource(?object $event): string
    {
        if (! $event) {
            return '-';
        }

        $machineName = trim((string) ($event->machine_name ?? ''));

        if ($machineName === 'Mobile App') {
            return 'Mobile App';
        }

        return $machineName !== '' ? $machineName : '-';
    }

    private function buildSourceSummary(string $inSource, string $outSource): string
    {
        if ($inSource === '-' && $outSource === '-') {
            return '-';
        }

        if ($inSource === '-' && $outSource !== '-') {
            return 'OUT: ' . $outSource;
        }

        if ($outSource === '-' && $inSource !== '-') {
            return 'IN: ' . $inSource;
        }

        return 'IN: ' . $inSource . ' / OUT: ' . $outSource;
    }

    private function hasMobilePhoto(?object $inEvent, ?object $outEvent): bool
    {
        return $this->isMobileApp($inEvent) && ! empty($inEvent?->photo_url)
            || $this->isMobileApp($outEvent) && ! empty($outEvent?->photo_url);
    }

    private function isMobileApp(?object $event): bool
    {
        return $event && trim((string) ($event->machine_name ?? '')) === 'Mobile App';
    }
}
