<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the map includes an initially open sidebar without future feature controls', function () {
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
    expect($xpath->query('.//input | .//button', $content)->length)->toBe(0);
    expect($xpath->query('.//*[@data-sidebar-search or @data-sidebar-layers or @data-sidebar-units]', $sidebar)->length)->toBe(0);
    expect($sidebar->textContent)->not->toContain('Camadas Visíveis', 'Batalhões & Unidades', '0 Unidades', '9 Unidades');
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
