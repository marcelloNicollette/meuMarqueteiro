<?php

return [
    'sync_snapshot' => [
        'enabled' => (bool) env('RADAR_SYNC_SNAPSHOT_ENABLED', false),
        'daily_enabled' => (bool) env('RADAR_SYNC_SNAPSHOT_DAILY_ENABLED', true),
        'weekly_enabled' => (bool) env('RADAR_SYNC_SNAPSHOT_WEEKLY_ENABLED', true),
        'recipients' => array_values(array_filter(array_map(
            static fn (string $email) => trim($email),
            explode(',', (string) env('RADAR_SYNC_SNAPSHOT_RECIPIENTS', ''))
        ))),
        'daily_time' => (string) env('RADAR_SYNC_SNAPSHOT_DAILY_TIME', '08:10'),
        'weekly_day' => (int) env('RADAR_SYNC_SNAPSHOT_WEEKLY_DAY', 1),
        'weekly_time' => (string) env('RADAR_SYNC_SNAPSHOT_WEEKLY_TIME', '08:30'),
    ],
];
