import assert from 'node:assert/strict';
import test from 'node:test';
import { countUnitGeometries, filterUnits, isUnitPoint } from '../../resources/js/components/unit-filters.js';

for (const [field, value, query] of [
    ['code', 'qcg-pmma', 'QCG-PMMA'],
    ['name', 'Comando de São Luís', 'sao LUIS'],
    ['acronym', '1º BPM', 'bpm'],
    ['served_localities', 'Paço do Lumiar, Raposa', 'PACO'],
    ['address', 'Avenida São Sebastião', 'SEBASTIAO'],
]) {
    test(`finds units by ${field} without case or accent differences`, () => {
        const match = { code: 'matching-unit', [field]: value };
        const units = [{ code: 'other-unit' }, match];

        const result = filterUnits(units, query);

        assert.deepEqual(result, [match]);
    });
}

test('requires all search terms even when they match different fields', () => {
    const match = { code: '2-bpm', name: 'Segundo Batalhão', served_localities: 'São José' };
    const units = [
        { code: '1-bpm', name: 'Primeiro Batalhão', served_localities: 'São Luís' },
        match,
        { code: '3-bpm', name: 'Terceiro Batalhão', served_localities: 'Raposa' },
    ];

    const result = filterUnits(units, '  JOSE   segundo\tbpm  ');

    assert.deepEqual(result, [match]);
});

for (const query of ['', '  \t\n ', null, undefined]) {
    test(`returns every unit for an empty query: ${String(query)}`, () => {
        const units = [{ code: '2-bpm' }, { code: '1-bpm' }];

        const result = filterUnits(units, query);

        assert.deepEqual(result, units);
        assert.notEqual(result, units);
    });
}

test('preserves result order and does not mutate the input or its records', () => {
    const first = Object.freeze({ code: '2-bpm', name: 'Batalhão segundo' });
    const second = Object.freeze({ code: '1-bpm', name: 'Batalhão primeiro' });
    const units = Object.freeze([first, Object.freeze({ code: 'qcg-pmma' }), second]);

    const result = filterUnits(units, 'bpm');

    assert.deepEqual(result, [first, second]);
    assert.deepEqual(units, [first, { code: 'qcg-pmma' }, second]);
});

test('returns no units when there is no match', () => {
    const units = [{ code: '1-bpm', name: 'Primeiro Batalhão' }];

    const result = filterUnits(units, 'inexistente');

    assert.deepEqual(result, []);
});

test('handles nullable fields and records without matching the word null', () => {
    const units = [null, {}, { code: null, name: null, acronym: null, served_localities: null, address: null }];

    const result = filterUnits(units, 'null');

    assert.deepEqual(result, []);
});

test('treats zero as searchable data instead of an empty query', () => {
    const match = { name: 'Destacamento', address: 0 };
    const units = [{ name: 'Sem endereço' }, match];

    const result = filterUnits(units, 0);

    assert.deepEqual(result, [match]);
});

for (const units of [null, undefined]) {
    test(`handles an absent unit list: ${String(units)}`, () => {
        assert.deepEqual(filterUnits(units, 'bpm'), []);
        assert.deepEqual(countUnitGeometries(units), { points: 0, areas: 0, unlocated: 0 });
    });
}

for (const coordinates of [[0, 0], [-180, -90], [180, 90], [-44.3, -2.5]]) {
    test(`accepts valid point coordinates ${coordinates}`, () => {
        assert.equal(isUnitPoint({ type: 'Point', coordinates }), true);
    });
}

for (const [name, location] of [
    ['null', null],
    ['missing geometry', undefined],
    ['wrong geometry type', { type: 'Polygon', coordinates: [0, 0] }],
    ['missing coordinates', { type: 'Point' }],
    ['non-array coordinates', { type: 'Point', coordinates: '0,0' }],
    ['missing latitude', { type: 'Point', coordinates: [0] }],
    ['third coordinate', { type: 'Point', coordinates: [0, 0, 0] }],
    ['string longitude', { type: 'Point', coordinates: ['0', 0] }],
    ['null latitude', { type: 'Point', coordinates: [0, null] }],
    ['infinite longitude', { type: 'Point', coordinates: [Infinity, 0] }],
    ['not-a-number latitude', { type: 'Point', coordinates: [0, NaN] }],
    ['longitude below minimum', { type: 'Point', coordinates: [-180.1, 0] }],
    ['longitude above maximum', { type: 'Point', coordinates: [180.1, 0] }],
    ['latitude below minimum', { type: 'Point', coordinates: [0, -90.1] }],
    ['latitude above maximum', { type: 'Point', coordinates: [0, 90.1] }],
]) {
    test(`rejects an invalid point: ${name}`, () => {
        assert.equal(isUnitPoint(location), false);
    });
}

test('counts points and areas independently and includes area-only units without a point', () => {
    const point = { type: 'Point', coordinates: [0, 0] };
    const polygon = { type: 'Polygon', coordinates: [[[0, 0], [1, 0], [1, 1], [0, 0]]] };
    const units = [
        { location: point, operational_area: null },
        { location: null, operational_area: polygon },
        { location: point, operational_area: { type: 'MultiPolygon', coordinates: [polygon.coordinates] } },
        { location: null, operational_area: null },
        { location: { type: 'Point', coordinates: [0, 120] }, operational_area: { type: 'Polygon', coordinates: [] } },
    ];

    const result = countUnitGeometries(units);

    assert.deepEqual(result, { points: 2, areas: 2, unlocated: 3 });
});

test('counts only the filtered units', () => {
    const units = [
        { code: '1-bpm', location: { type: 'Point', coordinates: [0, 0] } },
        { code: '2-bpm', location: null },
    ];

    const result = countUnitGeometries(filterUnits(units, '2-bpm'));

    assert.deepEqual(result, { points: 0, areas: 0, unlocated: 1 });
});

test('returns zero counts for an empty list', () => {
    assert.deepEqual(countUnitGeometries([]), { points: 0, areas: 0, unlocated: 0 });
});
