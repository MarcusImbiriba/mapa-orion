<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('authenticated users receive the map with the initial Sao Luis view and logout', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('<mapa-orion-map', false)
        ->assertSee('latitude="-2.5307"', false)
        ->assertSee('longitude="-44.3068"', false)
        ->assertSee('zoom="12"', false)
        ->assertSee('max-zoom="19"', false)
        ->assertSee('https://tile.openstreetmap.org/{z}/{x}/{y}.png', false)
        ->assertSee('https://www.openstreetmap.org/copyright', false)
        ->assertSee('data-ignore-morph', false)
        ->assertSee('Recentralizar')
        ->assertSee('Sair')
        ->assertDontSee('/data/unidades-policiais.geojson', false);
});

test('the map receives server configuration instead of hardcoded view values', function () {
    config()->set('map.center.latitude', -3.12);
    config()->set('map.center.longitude', -45.67);
    config()->set('map.zoom', 9);
    config()->set('map.tile_url', 'https://tiles.example.test/{z}/{x}/{y}.png');

    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('latitude="-3.12"', false)
        ->assertSee('longitude="-45.67"', false)
        ->assertSee('zoom="9"', false)
        ->assertSee('https://tiles.example.test/{z}/{x}/{y}.png', false);
});

test('the login page does not render a map or its tile configuration', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('<mapa-orion-map', false)
        ->assertDontSee('tile.openstreetmap.org', false);
});

test('map configuration cannot break out of its HTML attribute', function () {
    config()->set('map.attribution', '"><script>alert(1)</script>');

    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});
