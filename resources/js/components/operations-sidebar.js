import { countUnitGeometries, filterUnits, isUnitPoint } from './unit-filters';

class OperationsSidebar extends HTMLElement {
    #events;
    #map;

    connectedCallback() {
        this.#events?.abort();
        this.#events = new AbortController();
        const signal = this.#events.signal;
        const search = this.querySelector('[data-unit-search]');
        const clear = this.querySelector('[data-clear-search]');
        const points = this.querySelector('[data-layer-points]');
        const areas = this.querySelector('[data-layer-areas]');
        const rows = [...this.querySelectorAll('[data-unit-row-code]')];

        const update = () => {
            if (!this.#map?.ready || !search || !points || !areas) {
                return;
            }

            const all = this.#map.getUnits();
            const matches = filterUnits(all, search.value);
            const codes = new Set(matches.map((unit) => unit.code));
            const unitsByCode = new Map(all.map((unit) => [unit.code, unit]));
            const counts = countUnitGeometries(matches);

            rows.forEach((row) => {
                row.hidden = !codes.has(row.dataset.unitRowCode);
                row.querySelector('[data-unit-unlocated]').hidden = isUnitPoint(unitsByCode.get(row.dataset.unitRowCode)?.location);
            });
            this.querySelector('[data-unit-count]').textContent = `${matches.length} de ${all.length} unidades`;
            this.querySelector('[data-unlocated-count]').textContent = `${counts.unlocated} sem ponto de sede`;
            this.querySelector('[data-point-count]').textContent = String(points.checked ? counts.points : 0);
            this.querySelector('[data-area-count]').textContent = String(areas.checked ? counts.areas : 0);
            const empty = this.querySelector('[data-unit-empty]');
            empty.hidden = matches.length > 0;
            empty.textContent = all.length === 0 ? 'Nenhuma unidade cadastrada.' : 'Nenhuma unidade encontrada para esta busca.';
            clear.disabled = search.value.length === 0;
            this.#map.setUnitVisibility([...codes], { points: points.checked, areas: areas.checked });
        };

        search?.addEventListener('input', update, { signal });
        points?.addEventListener('change', update, { signal });
        areas?.addEventListener('change', update, { signal });
        clear?.addEventListener('click', () => {
            search.value = '';
            update();
            search.focus();
        }, { signal });

        this.querySelectorAll('[data-unit-details-code]').forEach((button) => {
            button.addEventListener('click', () => {
                this.#map?.showUnitDetails(button.dataset.unitDetailsCode, button);
            }, { signal });
        });

        customElements.whenDefined('mapa-orion-map').then(() => {
            if (signal.aborted) {
                return;
            }

            this.#map = document.getElementById(this.getAttribute('map-target'));
            if (!this.#map) {
                return;
            }

            const ready = () => {
                this.#setEnabled(this.#map.ready);
                update();
            };
            this.#map.addEventListener('map-ready', ready, { signal });
            this.#map.addEventListener('map-unavailable', () => this.#setEnabled(false), { signal });
            ready();
        });

        const content = this.querySelector('[data-sidebar-content]');
        const toggle = this.querySelector('[data-sidebar-toggle]');
        toggle.addEventListener('click', () => {
            const isOpen = !content.hidden;
            if (isOpen && content.contains(document.activeElement)) {
                toggle.focus({ preventScroll: true });
            }
            content.hidden = isOpen;
            this.toggleAttribute('data-open', !isOpen);
            toggle.setAttribute('aria-expanded', String(!isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Expandir painel' : 'Recolher painel');
            toggle.title = isOpen ? 'Expandir painel' : 'Recolher painel';
        }, { signal });
    }

    #setEnabled(enabled) {
        this.querySelectorAll('[data-unit-details-code], [data-unit-search], [data-clear-search], [data-layer-points], [data-layer-areas]').forEach((control) => {
            control.disabled = !enabled;
        });
    }

    disconnectedCallback() {
        this.#events?.abort();
        this.#setEnabled(false);
        this.#map = undefined;
    }
}

if (!customElements.get('operations-sidebar')) {
    customElements.define('operations-sidebar', OperationsSidebar);
}
