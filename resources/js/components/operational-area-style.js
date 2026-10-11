const unitAreaColors = new Map([
    ['qcg-pmma', { color: '#475569', fillColor: '#94a3b8' }],
    ['1-bptur', { color: '#1d4ed8', fillColor: '#3b82f6' }],
    ['1-bpm', { color: '#b91c1c', fillColor: '#ef4444' }],
    ['2-bpm', { color: '#15803d', fillColor: '#22c55e' }],
    ['3-bpm', { color: '#7e22ce', fillColor: '#a855f7' }],
    ['4-bpm', { color: '#c2410c', fillColor: '#f97316' }],
    ['5-bpm', { color: '#0e7490', fillColor: '#06b6d4' }],
    ['6-bpm', { color: '#be185d', fillColor: '#ec4899' }],
    ['7-bpm', { color: '#a16207', fillColor: '#eab308' }],
    ['8-bpm', { color: '#4338ca', fillColor: '#6366f1' }],
    ['9-bpm', { color: '#0f766e', fillColor: '#14b8a6' }],
    ['10-bpm', { color: '#be123c', fillColor: '#f43f5e' }],
    ['11-bpm', { color: '#4d7c0f', fillColor: '#84cc16' }],
    ['12-bpm', { color: '#0369a1', fillColor: '#0ea5e9' }],
    ['13-bpm', { color: '#a21caf', fillColor: '#d946ef' }],
    ['14-bpm', { color: '#92400e', fillColor: '#d97706' }],
    ['15-bpm', { color: '#047857', fillColor: '#10b981' }],
    ['16-bpm', { color: '#6d28d9', fillColor: '#8b5cf6' }],
    ['17-bpm', { color: '#9f1239', fillColor: '#fb7185' }],
    ['18-bpm', { color: '#3f6212', fillColor: '#a3e635' }],
    ['19-bpm', { color: '#155e75', fillColor: '#67e8f9' }],
    ['20-bpm', { color: '#86198f', fillColor: '#f0abfc' }],
]);

const fallbackAreaColors = { color: '#525252', fillColor: '#a3a3a3' };

export function hasOperationalAreaStyle(code) {
    return unitAreaColors.has(code);
}

export function getOperationalAreaStyle(code) {
    const colors = unitAreaColors.get(code) ?? fallbackAreaColors;

    return { ...colors, opacity: 1, weight: 2, fillOpacity: 0.20 };
}
