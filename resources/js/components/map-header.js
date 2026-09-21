class MapHeader extends HTMLElement {
    #events;
    #timer;

    connectedCallback() {
        if (this.#events) {
            return;
        }

        this.#events = new AbortController();
        const { signal } = this.#events;
        const clock = this.querySelector('[data-header-clock]');
        const button = this.querySelector('[data-header-fullscreen]');
        const icon = this.querySelector('[data-header-fullscreen-icon]');
        const status = this.querySelector('[data-header-status]');
        const formatter = new Intl.DateTimeFormat('pt-BR', {
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        });

        const updateClock = () => {
            const now = new Date();
            clock.textContent = formatter.format(now);
            clock.dateTime = now.toISOString();
            clock.hidden = false;
        };
        const startClock = () => {
            clearInterval(this.#timer);
            updateClock();
            this.#timer = setInterval(updateClock, 1000);
        };
        const syncFullscreen = () => {
            const active = Boolean(document.fullscreenElement);
            const label = active ? 'Sair da tela cheia' : 'Entrar em tela cheia';
            button.setAttribute('aria-pressed', String(active));
            button.setAttribute('aria-label', label);
            button.title = label;
            icon.setAttribute('d', active
                ? 'M4 14h6v6M20 10h-6V4M14 10l7-7M3 21l7-7'
                : 'M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7');
        };

        startClock();
        window.addEventListener('pagehide', () => clearInterval(this.#timer), { signal });
        window.addEventListener('pageshow', () => {
            startClock();
            syncFullscreen();
        }, { signal });

        button.hidden = !(document.fullscreenEnabled
            && typeof document.documentElement.requestFullscreen === 'function'
            && typeof document.exitFullscreen === 'function');
        syncFullscreen();
        document.addEventListener('fullscreenchange', syncFullscreen, { signal });
        button.addEventListener('click', async () => {
            button.disabled = true;
            status.hidden = true;
            status.textContent = '';

            try {
                if (document.fullscreenElement) {
                    await document.exitFullscreen();
                } else {
                    await document.documentElement.requestFullscreen();
                }
            } catch {
                if (!signal.aborted) {
                    status.textContent = 'Não foi possível alternar a tela cheia. Tente novamente ou use o controle do navegador.';
                    status.hidden = false;
                }
            } finally {
                if (!signal.aborted) {
                    button.disabled = false;
                    syncFullscreen();
                }
            }
        }, { signal });
    }

    disconnectedCallback() {
        clearInterval(this.#timer);
        this.#events?.abort();
        this.#events = undefined;
    }
}

customElements.define('mapa-orion-header', MapHeader);
