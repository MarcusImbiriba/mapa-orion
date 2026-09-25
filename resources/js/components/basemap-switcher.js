export class BasemapSwitcher {
    #map;
    #basemaps;
    #active;
    #handlers;
    #onChange;
    #onStatus;

    constructor(map, basemaps, { onChange, onStatus }) {
        this.#map = map;
        this.#basemaps = basemaps;
        this.#onChange = onChange;
        this.#onStatus = onStatus;
    }

    select(name) {
        const basemap = this.#basemaps.get(name);

        if (!this.#map || !basemap || this.#active?.name === name) {
            return false;
        }

        this.#detach();

        const { layer, label } = basemap;
        let hasTileError = false;
        const handlers = {
            loading: () => {
                if (this.#handlers !== handlers) {
                    return;
                }

                hasTileError = false;
                this.#onStatus(`Carregando ${label}…`);
            },
            tileerror: () => {
                if (this.#handlers !== handlers) {
                    return;
                }

                hasTileError = true;
                this.#onStatus(`Não foi possível carregar parte do mapa ${label}. Verifique sua conexão ou escolha outro tipo de mapa.`);
            },
            load: () => {
                if (this.#handlers === handlers && !hasTileError) {
                    this.#onStatus('');
                }
            },
        };

        this.#active = { name, layer };
        this.#handlers = handlers;
        layer.on(handlers);
        this.#onChange(name);
        this.#onStatus(`Carregando ${label}…`);
        this.#map.setMaxZoom(layer.options.maxZoom);
        layer.addTo(this.#map);

        return true;
    }

    #detach() {
        if (!this.#active) {
            return;
        }

        const { layer } = this.#active;
        layer.off(this.#handlers);
        this.#handlers = undefined;
        this.#active = undefined;
        this.#map.removeLayer(layer);
    }

    destroy() {
        this.#detach();
        this.#basemaps = new Map();
        this.#map = undefined;
    }
}
