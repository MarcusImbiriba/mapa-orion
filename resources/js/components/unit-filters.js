import { isOperationalArea } from './operational-area.js';

export function isUnitPoint(location) {
    if (location?.type !== 'Point' || !Array.isArray(location.coordinates)
        || location.coordinates.length !== 2) {
        return false;
    }

    const [longitude, latitude] = location.coordinates;

    return Number.isFinite(longitude) && Number.isFinite(latitude)
        && longitude >= -180 && longitude <= 180
        && latitude >= -90 && latitude <= 90;
}

function normalizeSearchText(value) {
    return String(value ?? '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

export function filterUnits(units, query) {
    if (!Array.isArray(units)) {
        return [];
    }

    const terms = normalizeSearchText(query).trim().split(/\s+/).filter(Boolean);

    return units.filter((unit) => {
        const text = normalizeSearchText([
            unit?.code,
            unit?.name,
            unit?.acronym,
            unit?.served_localities,
            unit?.address,
        ].join(' '));

        return terms.every((term) => text.includes(term));
    });
}

export function countUnitGeometries(units) {
    const counts = { points: 0, areas: 0, unlocated: 0 };

    for (const unit of Array.isArray(units) ? units : []) {
        if (isUnitPoint(unit?.location)) {
            counts.points++;
        } else {
            counts.unlocated++;
        }

        if (isOperationalArea(unit?.operational_area)) {
            counts.areas++;
        }
    }

    return counts;
}
