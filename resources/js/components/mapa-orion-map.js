import L from 'leaflet';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

const unitIcon = L.icon({
    iconUrl: markerIconUrl,
    iconRetinaUrl: markerIconRetinaUrl,
    shadowUrl: markerShadowUrl,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    shadowSize: [41, 41],
});

class MapaOrionMap extends HTMLElement {
    #map;
    #resizeObserver;
    #events;
    #center;
    #zoom;

    connectedCallback() {
        if (this.#map) {
            return;
        }

        const canvas = this.querySelector('[data-map-canvas]');
        const status = this.querySelector('[data-map-status]');
        const recenterButton = this.querySelector('[data-map-recenter]');

        try {
            this.#center = [Number(this.getAttribute('latitude')), Number(this.getAttribute('longitude'))];
            this.#zoom = Number(this.getAttribute('zoom'));
            this.#events = new AbortController();
            this.#map = L.map(canvas, {
                center: this.#center,
                zoom: this.#zoom,
                zoomControl: false,
            });

            L.control.zoom({
                position: 'bottomright',
                zoomInTitle: 'Aproximar',
                zoomOutTitle: 'Afastar',
            }).addTo(this.#map);

            let hasTileError = false;

            L.tileLayer(this.getAttribute('tile-url'), {
                maxZoom: Number(this.getAttribute('max-zoom')),
                attribution: this.getAttribute('attribution'),
            })
                .on('loading', () => {
                    hasTileError = false;
                })
                .on('tileerror', () => {
                    hasTileError = true;
                    status.textContent = 'Não foi possível carregar parte do mapa. Verifique sua conexão e recarregue a página.';
                    status.hidden = false;
                })
                .on('load', () => {
                    if (!hasTileError) {
                        status.hidden = true;
                    }
                })
                .addTo(this.#map);

            const units = JSON.parse(this.getAttribute('units') ?? '[]');

            for (const unit of units) {
                const location = unit.location;

                if (location?.type !== 'Point' || !Array.isArray(location.coordinates)
                    || location.coordinates.length !== 2) {
                    continue;
                }

                const [longitude, latitude] = location.coordinates;

                if (!Number.isFinite(longitude) || !Number.isFinite(latitude)
                    || longitude < -180 || longitude > 180 || latitude < -90 || latitude > 90) {
                    continue;
                }

                const label = `${unit.acronym} — ${unit.name}`;

                L.marker([latitude, longitude], {
                    icon: unitIcon,
                    title: label,
                    alt: label,
                }).addTo(this.#map);
            }

            recenterButton.disabled = false;
            recenterButton.addEventListener('click', () => this.recenter(), {
                signal: this.#events.signal,
            });

            this.#resizeObserver = new ResizeObserver(() => {
                this.#map?.invalidateSize({ pan: false, debounceMoveend: true });
            });
            this.#resizeObserver.observe(canvas);
        } catch (error) {
            this.disconnectedCallback();
            status.textContent = 'Não foi possível iniciar o mapa. Recarregue a página para tentar novamente.';
            status.hidden = false;
            console.error('Não foi possível iniciar o mapa.', error);
        }
    }

    disconnectedCallback() {
        this.#resizeObserver?.disconnect();
        this.#events?.abort();
        this.#map?.remove();
        this.#map = undefined;

        const recenterButton = this.querySelector('[data-map-recenter]');

        if (recenterButton) {
            recenterButton.disabled = true;
        }
    }

    recenter() {
        this.#map?.setView(this.#center, this.#zoom, { animate: false });
    }
}

if (!customElements.get('mapa-orion-map')) {
    customElements.define('mapa-orion-map', MapaOrionMap);
}
