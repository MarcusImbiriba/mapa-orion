import L from 'leaflet';
import { isOperationalArea } from './operational-area';
import { isUnitPoint } from './unit-filters';
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
    #unitDialog;
    #returnFocus;
    #units = [];
    #layers = new Map();
    #ready = false;

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

            this.#unitDialog = this.querySelector('[data-unit-dialog]');
            this.#unitDialog.querySelectorAll('[data-unit-close]').forEach((button) => {
                button.addEventListener('click', () => this.#unitDialog.close(), {
                    signal: this.#events.signal,
                });
            });
            this.#unitDialog.addEventListener('close', () => {
                this.#returnFocus?.focus({ preventScroll: true });
                this.#returnFocus = undefined;
            }, { signal: this.#events.signal });

            this.#units = JSON.parse(this.getAttribute('units') ?? '[]');

            for (const unit of this.#units) {
                const label = `${unit.acronym} — ${unit.name}`;
                const layers = {};
                this.#layers.set(unit.code, layers);

                if (isOperationalArea(unit.operational_area)) {
                    layers.area = L.geoJSON(unit.operational_area, {
                        style: { color: '#b45309', weight: 2, fillColor: '#f59e0b', fillOpacity: 0.16 },
                        onEachFeature: (_feature, layer) => {
                            layer.on('add', () => {
                                const element = layer.getElement();
                                element.setAttribute('tabindex', '0');
                                element.setAttribute('role', 'button');
                                element.setAttribute('aria-label', `Área operacional: ${label}`);
                                element.style.cursor = 'pointer';
                                element.addEventListener('focus', () => layer.setStyle({ weight: 4 }), {
                                    signal: this.#events.signal,
                                });
                                element.addEventListener('blur', () => layer.setStyle({ weight: 2 }), {
                                    signal: this.#events.signal,
                                });
                                element.addEventListener('keydown', (event) => {
                                    if (event.key === 'Enter' || event.key === ' ') {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        this.#showUnitDetails(unit, element);
                                    }
                                }, { signal: this.#events.signal });
                            });
                            layer.on('click', () => this.#showUnitDetails(unit, layer.getElement()));
                        },
                    }).addTo(this.#map);
                }

                const location = unit.location;

                if (!isUnitPoint(location)) {
                    continue;
                }

                const [longitude, latitude] = location.coordinates;

                const marker = L.marker([latitude, longitude], {
                    icon: unitIcon,
                    title: label,
                    alt: label,
                }).addTo(this.#map);

                layers.point = marker;

                marker.on('click', () => this.showUnitDetails(unit.code, marker.getElement(), { recenter: true }));
                marker.on('keydown', ({ originalEvent }) => {
                    if (originalEvent.key === 'Enter' || originalEvent.key === ' ') {
                        originalEvent.preventDefault();
                        originalEvent.stopPropagation();
                        this.showUnitDetails(unit.code, marker.getElement(), { recenter: true });
                    }
                });
            }

            recenterButton.disabled = false;
            recenterButton.addEventListener('click', () => this.recenter(), {
                signal: this.#events.signal,
            });

            this.#resizeObserver = new ResizeObserver(() => {
                this.#map?.invalidateSize({ pan: false, debounceMoveend: true });
            });
            this.#resizeObserver.observe(canvas);
            this.#ready = true;
            this.dispatchEvent(new Event('map-ready'));
        } catch (error) {
            this.disconnectedCallback();
            status.textContent = 'Não foi possível iniciar o mapa. Recarregue a página para tentar novamente.';
            status.hidden = false;
            console.error('Não foi possível iniciar o mapa.', error);
        }
    }

    get ready() {
        return this.#ready;
    }

    getUnits() {
        return this.#units;
    }

    setUnitVisibility(codes, { points = true, areas = true } = {}) {
        if (!this.#ready) {
            return;
        }

        const visible = new Set(codes);

        for (const [code, layers] of this.#layers) {
            for (const [kind, layer] of Object.entries(layers)) {
                const shouldShow = visible.has(code) && (kind === 'point' ? points : areas);
                const isShown = this.#map.hasLayer(layer);

                if (shouldShow && !isShown) {
                    layer.addTo(this.#map);
                } else if (!shouldShow && isShown) {
                    this.#map.removeLayer(layer);
                }
            }
        }
    }

    showUnitDetails(code, trigger, { recenter = false } = {}) {
        const unit = this.#units.find((candidate) => candidate.code === code);

        if (this.#ready && unit && this.#unitDialog && !this.#events?.signal.aborted) {
            const locationUnavailable = recenter && !isUnitPoint(unit.location);

            if (recenter && !locationUnavailable) {
                const [longitude, latitude] = unit.location.coordinates;
                this.#map.panTo([latitude, longitude], {
                    animate: true,
                    duration: 2.2,
                });
            }

            this.#showUnitDetails(unit, trigger, { locationUnavailable });
        }
    }

    #showUnitDetails(unit, trigger, { locationUnavailable = false } = {}) {
        const locationWarning = this.#unitDialog.querySelector('[data-unit-location-warning]');
        locationWarning.hidden = !locationUnavailable;

        if (locationUnavailable) {
            this.#unitDialog.setAttribute('aria-describedby', locationWarning.id);
        } else {
            this.#unitDialog.removeAttribute('aria-describedby');
        }

        this.#unitDialog.querySelectorAll('[data-unit-field]').forEach((element) => {
            const value = unit[element.dataset.unitField];
            const hasValue = value !== null && value !== undefined && String(value).trim() !== '';
            element.textContent = hasValue ? String(value) : '';
            const row = element.closest('[data-unit-row]');

            if (row) {
                row.hidden = !hasValue;
            }
        });

        const numberFormat = new Intl.NumberFormat('pt-BR');
        const compactFormat = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });

        this.#unitDialog.querySelectorAll('[data-unit-metric]').forEach((element) => {
            const field = element.dataset.unitMetric;
            const value = unit[field];
            const hasValue = Number.isSafeInteger(value) && value >= 0;
            const fullValue = hasValue ? numberFormat.format(value) : 'Não informado';
            let displayValue = hasValue ? fullValue : '—';

            if (hasValue && field === 'served_population' && value >= 1000) {
                const divisor = value >= 1000000 ? 1000000 : 1000;
                displayValue = `${compactFormat.format(value / divisor)}${divisor === 1000000 ? 'M' : 'k'}`;
            }

            const accessibleValue = hasValue && field === 'served_population' ? `${fullValue} habitantes` : fullValue;
            element.querySelector('[data-unit-metric-value]').textContent = displayValue;
            element.querySelector('[data-unit-metric-accessible]').textContent = accessibleValue;
            element.querySelector('p').title = accessibleValue;
        });

        this.#unitDialog.querySelector('[data-unit-metrics-demo]').hidden = unit.metrics_are_demo !== true;

        const command = this.#unitDialog.querySelector('[data-unit-command]');
        command.hidden = !Array.from(command.querySelectorAll('[data-unit-row]')).some((row) => !row.hidden);

        const localities = (unit.served_localities ?? '').split(';').map((value) => value.trim()).filter(Boolean);
        const list = this.#unitDialog.querySelector('[data-unit-localities-list]');
        list.replaceChildren();

        for (const locality of localities) {
            const badge = document.createElement('span');
            badge.className = 'rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-300 wrap-anywhere';
            badge.textContent = locality;
            list.append(badge);
        }

        this.#unitDialog.querySelector('[data-unit-localities-count]').textContent = String(localities.length);
        this.#unitDialog.querySelector('[data-unit-localities]').hidden = localities.length === 0;
        this.#returnFocus = trigger;
        this.#unitDialog.showModal();
    }

    disconnectedCallback() {
        this.#ready = false;
        this.dispatchEvent(new Event('map-unavailable'));
        this.#resizeObserver?.disconnect();
        this.#events?.abort();
        this.#unitDialog?.close();
        this.#returnFocus = undefined;
        this.#map?.remove();
        this.#map = undefined;
        this.#layers.clear();

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
