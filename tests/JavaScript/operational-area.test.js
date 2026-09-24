import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { isOperationalArea } from '../../resources/js/components/operational-area.js';

const geometries = JSON.parse(readFileSync(new URL('../Fixtures/operational-areas.json', import.meta.url), 'utf8'));

for (const [name, geometry] of Object.entries(geometries.valid)) {
    test(`accepts ${name}`, () => assert.equal(isOperationalArea(geometry), true));
}

for (const [name, geometry] of Object.entries(geometries.invalid)) {
    test(`rejects ${name}`, () => assert.equal(isOperationalArea(geometry), false));
}

for (const value of [null, undefined, NaN, Infinity]) {
    test(`rejects absent or non-finite geometry: ${value}`, () => assert.equal(isOperationalArea(value), false));
}

test('rejects non-finite coordinates', () => {
    const geometry = structuredClone(geometries.valid.polygon);
    geometry.coordinates[0][1][0] = Infinity;
    assert.equal(isOperationalArea(geometry), false);
});
