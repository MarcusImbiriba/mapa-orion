import assert from 'node:assert/strict';
import test from 'node:test';
import { BasemapSwitcher } from '../../resources/js/components/basemap-switcher.js';

class TileLayerDouble {
    listeners = new Map();

    constructor(maxZoom) {
        this.options = { maxZoom };
    }

    on(handlers) {
        for (const [type, handler] of Object.entries(handlers)) {
            const listeners = this.listeners.get(type) ?? new Set();
            listeners.add(handler);
            this.listeners.set(type, listeners);
        }

        return this;
    }

    off(handlers) {
        for (const [type, handler] of Object.entries(handlers)) {
            this.listeners.get(type)?.delete(handler);
        }

        return this;
    }

    fire(type) {
        for (const handler of this.listeners.get(type) ?? []) {
            handler();
        }
    }

    addTo(map) {
        map.layers.add(this);

        return this;
    }
}

function setup() {
    const point = { code: 'visible-point' };
    const area = { code: 'visible-area' };
    const map = {
        layers: new Set([point, area]),
        setMaxZoom(value) { this.maxZoom = value; },
        removeLayer(layer) { this.layers.delete(layer); },
        setView() { assert.fail('Switching a base layer must not reset the view.'); },
        panTo() { assert.fail('Switching a base layer must not move the center.'); },
    };
    const streets = new TileLayerDouble(19);
    const satellite = new TileLayerDouble(17);
    const changes = [];
    const messages = [];
    const switcher = new BasemapSwitcher(map, new Map([
        ['streets', { layer: streets, label: 'OpenStreetMap' }],
        ['satellite', { layer: satellite, label: 'Satélite Esri' }],
    ]), {
        onChange: (name) => changes.push(name),
        onStatus: (message) => messages.push(message),
    });

    return { map, point, area, streets, satellite, switcher, changes, messages };
}

test('changes only the base layer and preserves visible unit overlays in both directions', () => {
    const { switcher, map, point, area, streets, satellite, changes } = setup();
    switcher.select('streets');

    assert.equal(switcher.select('satellite'), true);

    assert.deepEqual([...map.layers], [point, area, satellite]);
    assert.deepEqual(changes, ['streets', 'satellite']);

    switcher.select('streets');

    assert.deepEqual([...map.layers], [point, area, streets]);
    assert.deepEqual(changes, ['streets', 'satellite', 'streets']);
});

for (const name of ['streets', 'dark', 'unknown', '__proto__']) {
    test(`ignores a redundant or unsupported selection: ${name}`, () => {
        const { switcher, map, changes, messages } = setup();
        switcher.select('streets');
        const layers = [...map.layers];
        const initialMessages = [...messages];

        const changed = switcher.select(name);

        assert.equal(changed, false);
        assert.deepEqual([...map.layers], layers);
        assert.deepEqual(changes, ['streets']);
        assert.deepEqual(messages, initialMessages);
    });
}

test('applies the selected providers zoom limit and restores it when returning to streets', () => {
    const { switcher, map } = setup();
    switcher.select('streets');
    assert.equal(map.maxZoom, 19);

    switcher.select('satellite');
    assert.equal(map.maxZoom, 17);

    switcher.select('streets');
    assert.equal(map.maxZoom, 19);
});

test('keeps tile failures visible after load and clears them after a successful new load', () => {
    const { switcher, satellite, messages } = setup();
    switcher.select('satellite');
    assert.equal(messages.at(-1), 'Carregando Satélite Esri…');

    satellite.fire('loading');
    satellite.fire('tileerror');
    satellite.fire('load');

    assert.equal(messages.at(-1), 'Não foi possível carregar parte do mapa Satélite Esri. Verifique sua conexão ou escolha outro tipo de mapa.');

    satellite.fire('loading');
    satellite.fire('load');

    assert.equal(messages.at(-1), '');
});

test('recovers from a satellite failure by switching back to streets', () => {
    const { switcher, satellite, streets, messages, changes } = setup();
    switcher.select('satellite');
    satellite.fire('tileerror');

    switcher.select('streets');
    streets.fire('load');

    assert.equal(changes.at(-1), 'streets');
    assert.equal(messages.at(-1), '');
});

test('ignores stale callbacks even after the same layer is selected again', () => {
    const { switcher, streets, satellite, messages } = setup();
    switcher.select('streets');
    const stale = [...streets.listeners.values()].flatMap((listeners) => [...listeners]);
    switcher.select('satellite');
    satellite.fire('load');
    const beforeStaleEvents = [...messages];

    streets.fire('loading');
    streets.fire('tileerror');
    streets.fire('load');

    assert.deepEqual(messages, beforeStaleEvents);
    switcher.select('streets');
    const beforeStaleCallbacks = [...messages];

    stale.forEach((handler) => handler());

    assert.deepEqual(messages, beforeStaleCallbacks);
    streets.fire('load');
    assert.equal(messages.at(-1), '');
});

test('repeated switching leaves only one base layer and one listener for each active event', () => {
    const { switcher, map, satellite, streets, messages } = setup();

    for (let index = 0; index < 10; index++) {
        switcher.select('streets');
        switcher.select('satellite');
    }

    assert.equal(map.layers.size, 3);
    assert.equal(map.layers.has(satellite), true);
    for (const type of ['loading', 'tileerror', 'load']) {
        assert.equal(streets.listeners.get(type).size, 0);
        assert.equal(satellite.listeners.get(type).size, 1);
    }
    const messageCount = messages.length;
    satellite.fire('loading');
    assert.equal(messages.length, messageCount + 1);
});

test('destroy removes base tiles and listeners without touching overlays and is safe twice', () => {
    const { switcher, map, streets, point, area, messages } = setup();
    switcher.select('streets');
    const staleError = [...streets.listeners.get('tileerror')][0];
    const beforeDestroy = [...messages];

    switcher.destroy();
    switcher.destroy();
    streets.fire('tileerror');
    staleError();

    assert.deepEqual([...map.layers], [point, area]);
    assert.deepEqual(messages, beforeDestroy);
    assert.equal(switcher.select('satellite'), false);
    for (const listeners of streets.listeners.values()) {
        assert.equal(listeners.size, 0);
    }
});
