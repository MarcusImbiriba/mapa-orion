<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the map header exposes the reference identity and authenticated controls without global metrics or export', function () {
    $user = User::factory()->create(['name' => 'Pessoa de teste']);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $header = $document->getElementsByTagName('header')->item(0);
    expect($header->textContent)
        ->toContain('PMMA', 'DGTI', 'SISTEMA OPERACIONAL', 'Mapeamento de Jurisdições', 'Pessoa de teste', 'Sair')
        ->not->toContain('Exportar', 'Efetivo', 'Oficiais', 'Praças', 'População', 'Unidades Policiais', 'Região');
    expect($xpath->query('.//button', $header)->length)->toBe(2);
    $fullscreen = $xpath->query('.//button[@aria-label="Entrar em tela cheia"]', $header)->item(0);
    expect($fullscreen->getAttribute('type'))->toBe('button');
    expect($fullscreen->getAttribute('aria-pressed'))->toBe('false');
    expect($fullscreen->hasAttribute('hidden'))->toBeTrue();
    $clock = $xpath->query('.//time', $header)->item(0);
    expect($clock->getAttribute('aria-live'))->toBe('off');
    expect($clock->hasAttribute('hidden'))->toBeTrue();

    $form = $xpath->query('.//form', $header)->item(0);
    expect($form->getAttribute('method'))->toBe('POST');
    expect($form->getAttribute('action'))->toBe(route('logout'));
    expect($form->getAttribute('data-on:submit'))->toContain('@post', 'contentType', 'form');
    expect($xpath->query('.//input[@name="_token"]', $form)->item(0)->getAttribute('value'))->toBe(session()->token());
});
