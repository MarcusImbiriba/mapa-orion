<?php

use App\Models\PoliceUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the map includes an open sidebar with search layer controls and an empty list', function () {
    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertSee('Painel de Unidades & Áreas', false);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $sidebar = $xpath->query('//operations-sidebar')->item(0);
    $toggle = $xpath->query('.//button[@data-sidebar-toggle]', $sidebar)->item(0);
    $content = $xpath->query('.//aside', $sidebar)->item(0);
    expect($sidebar->hasAttribute('data-open'))->toBeTrue();
    expect($toggle->getAttribute('aria-expanded'))->toBe('true');
    expect($toggle->getAttribute('aria-controls'))->toBe($content->getAttribute('id'));
    expect($toggle->getAttribute('aria-label'))->toBe('Recolher painel');
    expect($content->hasAttribute('hidden'))->toBeFalse();
    $search = $xpath->query('.//input[@data-unit-search]', $content)->item(0);
    expect($search->getAttribute('type'))->toBe('search');
    expect($search->getAttribute('aria-controls'))->toBe('unit-list');
    expect($xpath->query('.//label[@for="unit-search"]', $content)->length)->toBe(1);
    expect($xpath->query('.//input[@type="checkbox" and @checked]', $content)->length)->toBe(2);
    expect($xpath->query('.//*[@data-sidebar-search or @data-sidebar-layers]', $sidebar)->length)->toBe(2);
    expect($xpath->query('.//*[@data-sidebar-units]', $sidebar)->length)->toBe(1);
    expect($sidebar->textContent)->toContain('Nenhuma unidade cadastrada.');
    expect($sidebar->textContent)->toContain('0 de 0 unidades', '0 sem ponto de sede');
});

test('empty or comment-only slots do not produce section containers', function () {
    $view = $this->blade(<<<'BLADE'
        <x-operations-sidebar>
            <x-slot:search>   </x-slot:search>
            <x-slot:layers><!-- Future layer controls --></x-slot:layers>
            <x-slot:units>{{-- Future unit list --}}</x-slot:units>
        </x-operations-sidebar>
        BLADE);

    $view->assertDontSee('data-sidebar-search', false)
        ->assertDontSee('data-sidebar-layers', false)
        ->assertDontSee('data-sidebar-units', false);
});

test('populated slots render in order with one unit scroll region', function () {
    $view = $this->blade(<<<'BLADE'
        <x-operations-sidebar id="test-panel">
            <x-slot:search><label>Busca de teste<input name="query"></label></x-slot:search>
            <x-slot:layers><section aria-label="Camadas de teste">Controles de teste</section></x-slot:layers>
            <x-slot:units><section aria-label="Unidades de teste">Lista de teste</section></x-slot:units>
        </x-operations-sidebar>
        BLADE);

    $view->assertSeeInOrder(['Busca de teste', 'Camadas de teste', 'Unidades de teste'])
        ->assertSee('aria-controls="test-panel-content"', false);
    $document = new DOMDocument;
    @$document->loadHTML((string) $view);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//*[@data-sidebar-search]//input[@name="query"]')->length)->toBe(1);
    expect($xpath->query('//*[@data-sidebar-layers]//section[@aria-label="Camadas de teste"]')->length)->toBe(1);
    expect($xpath->query('//*[@data-sidebar-units]')->length)->toBe(1);
    expect($xpath->query('//*[@data-sidebar-units]//section[@aria-label="Unidades de teste"]')->length)->toBe(1);
});

test('units without a headquarters point can be selected even without any geometry', function () {
    PoliceUnit::factory()->withLocation()->create(['code' => 'a-located']);
    $unlocated = PoliceUnit::factory()->create(['code' => 'b-no-geometry', 'name' => 'Unidade sem geometria']);
    $areaOnly = PoliceUnit::factory()->withOperationalArea()->create(['code' => 'c-area-only']);

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk()->assertSee('3 de 3 unidades')->assertSee('2 sem ponto de sede')->assertSee('Unidade sem geometria');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//operations-sidebar//button[@data-unit-details-code]');
    expect($buttons->length)->toBe(3);
    expect($buttons->item(0)->getAttribute('data-unit-details-code'))->toBe('a-located');
    expect($buttons->item(1)->getAttribute('data-unit-details-code'))->toBe($unlocated->code);
    expect($buttons->item(2)->getAttribute('data-unit-details-code'))->toBe($areaOnly->code);
    expect($xpath->query('//operations-sidebar//*[@data-unit-unlocated and not(@hidden)]')->length)->toBe(2);
    expect($buttons->item(0)->getAttribute('aria-haspopup'))->toBe('dialog');
    expect($buttons->item(1)->textContent)->toContain($unlocated->name, $unlocated->acronym);
    expect($xpath->query('//dialog[@data-unit-dialog]')->length)->toBe(1);
});

test('the sidebar lists located units and reports no units without a point', function () {
    PoliceUnit::factory()->withLocation()->create();

    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('1 de 1 unidades')
        ->assertSee('0 sem ponto de sede')
        ->assertSee('data-unit-details-code', false);
});

test('unlocated unit data cannot inject markup into the sidebar', function () {
    PoliceUnit::factory()->create([
        'code' => 'unit-" onclick="alert(1)',
        'name' => '<script>alert(2)</script>',
        'acronym' => '<img src=x onerror=alert(3)>',
    ]);

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertDontSee('<script>alert(2)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(3)>', false);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $button = $xpath->query('//button[@data-unit-details-code]')->item(0);
    expect($button->hasAttribute('onclick'))->toBeFalse();
    expect($button->getAttribute('data-unit-details-code'))->toBe('unit-" onclick="alert(1)');
    expect($button->textContent)->toContain('<script>alert(2)</script>', '<img src=x onerror=alert(3)>');
});

test('unlocated units remain private to authenticated users', function () {
    PoliceUnit::factory()->create(['name' => 'Unidade restrita sem localização']);

    $this->get('/')->assertRedirect('/login')->assertDontSee('Unidade restrita sem localização');
});

test('the sidebar lists QCG first then battalions numerically with geometry-independent totals', function () {
    PoliceUnit::factory()->withLocation()->create(['code' => '10-bpm']);
    PoliceUnit::factory()->withOperationalArea()->create(['code' => '2-bpm']);
    PoliceUnit::factory()->withLocation()->withOperationalArea()->create(['code' => '1-bpm']);
    PoliceUnit::factory()->create(['code' => 'qcg-pmma']);

    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertSee('4 de 4 unidades')->assertSee('2 sem ponto de sede');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $rows = $xpath->query('//operations-sidebar//li[@data-unit-row-code]');
    expect(array_map(fn (DOMElement $row) => $row->getAttribute('data-unit-row-code'), iterator_to_array($rows)))
        ->toBe(['qcg-pmma', '1-bpm', '2-bpm', '10-bpm']);
    $map = $document->getElementsByTagName('mapa-orion-map')->item(0);
    $units = json_decode($map->getAttribute('units'), true, flags: JSON_THROW_ON_ERROR);
    expect(array_is_list($units))->toBeTrue();
    expect(array_column($units, 'code'))->toBe(['qcg-pmma', '1-bpm', '2-bpm', '10-bpm']);
    expect($xpath->query('//operations-sidebar//button[@data-unit-details-code]')->length)->toBe(4);
});
