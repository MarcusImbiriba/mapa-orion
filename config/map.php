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
    'satellite' => [
        'tile_url' => env('MAP_SATELLITE_TILE_URL', 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
        'attribution' => env('MAP_SATELLITE_ATTRIBUTION', 'Tiles &copy; <a href="https://www.arcgis.com/home/item.html?id=10df2279f9684e4a9f6a7f08febac2a9">Esri</a> &mdash; Esri, Vantor, Earthstar Geographics, and the GIS User Community'),
        'max_zoom' => 19,
    ],
];
