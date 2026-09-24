export function isOperationalArea(geometry) {
    if (!geometry || !['Polygon', 'MultiPolygon'].includes(geometry.type)
        || !Array.isArray(geometry.coordinates) || geometry.coordinates.length === 0) {
        return false;
    }

    const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;

    return polygons.every((polygon) => Array.isArray(polygon) && polygon.length > 0
        && polygon.every((ring) => {
            if (!Array.isArray(ring) || ring.length < 4 || !ring.every((position) =>
                Array.isArray(position) && position.length === 2
                && position.every(Number.isFinite)
                && position[0] >= -180 && position[0] <= 180
                && position[1] >= -90 && position[1] <= 90)) {
                return false;
            }

            const first = ring[0];
            const last = ring[ring.length - 1];
            return first[0] === last[0] && first[1] === last[1];
        }));
}
