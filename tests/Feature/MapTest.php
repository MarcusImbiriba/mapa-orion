<?php

use App\Models\PoliceUnit;
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

test('the map receives all units and the fields needed for their details', function () {
    $unit = PoliceUnit::factory()->withLocation()->create([
        'unit_type' => 'Operacional',
        'address' => 'Rua de teste, 10',
        'commander' => 'Comandante de teste',
        'deputy_commander' => 'Subcomandante de teste',
        'phone' => '(98) 3000-0000',
        'email' => 'unidade@example.com',
        'served_localities' => 'Bairro de teste (parcial); Cidade de teste',
        'officers_count' => 45,
        'enlisted_count' => 245,
        'served_population' => 275000,
        'metrics_are_demo' => true,
    ]);
    $unlocated = PoliceUnit::factory()->create(['code' => 'z-unlocated']);

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $map = $document->getElementsByTagName('mapa-orion-map')->item(0);
    expect(json_decode($map->getAttribute('units'), true, flags: JSON_THROW_ON_ERROR))
        ->toBe(collect([$unit->fresh(), $unlocated->fresh()])->map(fn (PoliceUnit $item) => $item->only(['code', 'name', 'acronym', 'unit_type', 'address', 'commander', 'deputy_commander', 'phone', 'email', 'served_localities', 'location', 'operational_area', 'officers_count', 'enlisted_count', 'served_population', 'metrics_are_demo', 'total_personnel']))->all());
});

test('the map renders with an empty unit list when there are no units', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('units="[]"', false);
});

test('unit names cannot escape the marker data attribute', function () {
    $name = '\"><script>alert(1)</script>';
    PoliceUnit::factory()->withLocation()->create(['name' => $name]);

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $map = $document->getElementsByTagName('mapa-orion-map')->item(0);
    expect(json_decode($map->getAttribute('units'), true, flags: JSON_THROW_ON_ERROR)[0]['name'])->toBe($name);
});

test('guests cannot access unit marker data', function () {
    PoliceUnit::factory()->withLocation()->create(['code' => 'restricted-unit']);

    $this->get('/')
        ->assertRedirect('/login')
        ->assertDontSee('restricted-unit');
});

test('unfilled unit details remain null in the map data', function () {
    PoliceUnit::factory()->withLocation()->create();

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $map = $document->getElementsByTagName('mapa-orion-map')->item(0);
    expect(json_decode($map->getAttribute('units'), true, flags: JSON_THROW_ON_ERROR)[0])
        ->toMatchArray([
            'unit_type' => null,
            'address' => null,
            'commander' => null,
            'deputy_commander' => null,
            'phone' => null,
            'email' => null,
            'served_localities' => null,
            'officers_count' => null,
            'enlisted_count' => null,
            'total_personnel' => null,
            'served_population' => null,
            'metrics_are_demo' => false,
        ]);
});

test('unit details use the reference dialog with accessible closing controls', function () {
    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk()
        ->assertSee('Comando & Gestão Operacional', false)
        ->assertSee('Bairros e setores abrangidos')
        ->assertSee('Efetivo Total')
        ->assertSee('População')
        ->assertDontSee('unit-popup');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $dialog = $xpath->query('//dialog[@data-unit-dialog]')->item(0);
    expect($dialog->getAttribute('aria-labelledby'))->toBe('unit-details-title');
    expect($xpath->query('.//button[@data-unit-close]', $dialog)->length)->toBe(2);
    expect($xpath->query('.//*[@id="unit-details-title"]', $dialog)->length)->toBe(1);
    expect($dialog->hasAttribute('open'))->toBeFalse();
    expect($xpath->query('.//*[@data-unit-field="latitude" or @data-unit-field="longitude" or @data-unit-field="code"]', $dialog)->length)->toBe(0);
    expect($dialog->textContent)->not->toContain('Latitude', 'Longitude', 'Código');
    expect($xpath->query('.//*[@data-unit-metric]', $dialog)->length)->toBe(4);
    expect($xpath->query('.//*[@data-unit-metric]//p[@title="Não informado"]', $dialog)->length)->toBe(4);
    expect($xpath->query('.//*[@data-unit-metric]//*[local-name()="circle"]', $dialog)->length)->toBe(3);
    expect($xpath->query('.//*[@data-unit-command and @hidden]', $dialog)->length)->toBe(1);
    expect($xpath->query('.//*[@data-unit-localities and @hidden]', $dialog)->length)->toBe(1);
});

test('the map receives units with a point an area both or neither', function () {
    $point = PoliceUnit::factory()->withLocation()->create(['code' => 'a-point']);
    $area = PoliceUnit::factory()->withOperationalArea()->create(['code' => 'b-area']);
    $both = PoliceUnit::factory()->withLocation()->withOperationalArea()->create(['code' => 'c-both']);
    PoliceUnit::factory()->create(['code' => 'd-neither']);

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $map = $document->getElementsByTagName('mapa-orion-map')->item(0);
    $units = json_decode($map->getAttribute('units'), true, flags: JSON_THROW_ON_ERROR);
    expect(array_column($units, 'code'))->toBe(['a-point', 'b-area', 'c-both', 'd-neither']);
    expect($units[3]['location'])->toBeNull();
    expect($units[3]['operational_area'])->toBeNull();
    expect($units[0]['operational_area'])->toBeNull();
    expect($units[1]['location'])->toBeNull();
    expect($units[1]['operational_area'])->toBe($area->operational_area);
    expect($units[2]['location'])->toBe($both->location);
    expect($units[2]['operational_area'])->toBe($both->operational_area);
});

test('guests cannot access operational areas', function () {
    PoliceUnit::factory()->withOperationalArea()->create(['code' => 'restricted-area']);

    $this->get('/')->assertRedirect('/login')->assertDontSee('restricted-area');
});
