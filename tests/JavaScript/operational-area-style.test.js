import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { getOperationalAreaStyle, hasOperationalAreaStyle } from '../../resources/js/components/operational-area-style.js';

const units = JSON.parse(readFileSync(new URL('../../database/seeders/data/police_units.json', import.meta.url), 'utf8'));

test('every catalog unit has its own operational area style', () => {
    for (const unit of units) {
        assert.equal(hasOperationalAreaStyle(unit.code), true, `Define an operational area style for ${unit.code}.`);
    }
});

test('catalog units have distinct fill and border colors', () => {
    const styles = units.map((unit) => getOperationalAreaStyle(unit.code));

    assert.equal(new Set(styles.map((style) => style.fillColor)).size, units.length);
    assert.equal(new Set(styles.map((style) => style.color)).size, units.length);
});

test('area styles preserve 80 percent transparency and opaque two-pixel borders', () => {
    for (const code of [...units.map((unit) => unit.code), 'unknown-unit']) {
        const style = getOperationalAreaStyle(code);

        assert.match(style.color, /^#[0-9a-f]{6}$/i);
        assert.match(style.fillColor, /^#[0-9a-f]{6}$/i);
        assert.equal(style.fillOpacity, 0.20);
        assert.equal(style.opacity, 1);
        assert.equal(style.weight, 2);
    }
});

test('unit colors remain stable after reordering, filtering and repeated lookup', () => {
    const initialStyles = new Map(units.map((unit) => [unit.code, getOperationalAreaStyle(unit.code)]));

    for (const unit of [...units].reverse()) {
        assert.deepEqual(getOperationalAreaStyle(unit.code), initialStyles.get(unit.code));
    }

    const tourismStyle = getOperationalAreaStyle('1-bptur');
    getOperationalAreaStyle('20-bpm');
    getOperationalAreaStyle('unknown-unit');

    assert.equal(tourismStyle.fillColor, '#3b82f6');
    assert.deepEqual(getOperationalAreaStyle('1-bptur'), tourismStyle);
    assert.notEqual(getOperationalAreaStyle('20-bpm').fillColor, tourismStyle.fillColor);
});

test('changing a returned style cannot alter subsequent unit or fallback styles', () => {
    for (const code of ['1-bptur', 'unknown-unit']) {
        const originalStyle = getOperationalAreaStyle(code);
        const changedStyle = getOperationalAreaStyle(code);
        changedStyle.weight = 4;
        changedStyle.fillOpacity = 1;
        changedStyle.fillColor = '#000000';
        changedStyle.opacity = 0;

        assert.deepEqual(getOperationalAreaStyle(code), originalStyle);
    }
});

for (const code of ['unknown-unit', '__proto__', 'constructor', 'toString', '', null, undefined, 1, {}]) {
    test(`uses a neutral fallback for an unassigned code: ${String(code)}`, () => {
        assert.equal(hasOperationalAreaStyle(code), false);
        assert.deepEqual(getOperationalAreaStyle(code), {
            color: '#525252',
            fillColor: '#a3a3a3',
            opacity: 1,
            weight: 2,
            fillOpacity: 0.20,
        });
    });
}
