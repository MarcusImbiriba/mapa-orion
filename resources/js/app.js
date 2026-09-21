import './components/map-header.js';
import './components/operations-sidebar.js';

if (document.querySelector('mapa-orion-map')) {
    import('./components/mapa-orion-map.js').catch((error) => {
        const status = document.querySelector('[data-map-status]');

        if (status) {
            status.textContent = 'Não foi possível iniciar o mapa. Recarregue a página para tentar novamente.';
            status.hidden = false;
        }

        console.error('Não foi possível carregar o componente do mapa.', error);
    });
}
