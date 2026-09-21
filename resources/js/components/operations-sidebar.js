class OperationsSidebar extends HTMLElement {
    #events;

    connectedCallback() {
        this.#events?.abort();
        this.#events = new AbortController();

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
        }, { signal: this.#events.signal });
    }

    disconnectedCallback() {
        this.#events?.abort();
    }
}

if (!customElements.get('operations-sidebar')) {
    customElements.define('operations-sidebar', OperationsSidebar);
}
