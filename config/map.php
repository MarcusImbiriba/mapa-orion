<?php

return [
    'center' => [
        'latitude' => -2.5307,
        'longitude' => -44.3068,
    ],
    'zoom' => 12,
    'max_zoom' => 19,
    'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'attribution' => env('MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
];
