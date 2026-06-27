<?php
?>
<div id="fw-loading-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-loading-container');
    if (!container) return;

    const loaders = new Map();

    function createLoader(id, options = {}) {
        const type = options.type || 'spinner';
        const overlay = options.overlay || false;
        const fullPage = options.fullPage || false;
        const message = options.message || '';

        const loader = document.createElement('div');
        loader.id = 'fw-loading-' + id;
        loader.className = overlay || fullPage ? 'fw-loading-overlay' : 'fw-loading';

        let loaderContent = '';
        if (type === 'spinner') {
            loaderContent = '<div class="fw-loading-spinner"></div>';
        } else if (type === 'dots') {
            loaderContent = '<div class="fw-loading-dots"><div class="fw-loading-dot"></div><div class="fw-loading-dot"></div><div class="fw-loading-dot"></div></div>';
        }

        if (message) {
            loaderContent += `<p class="mt-4 text-gray-600">${message}</p>`;
        }

        loader.innerHTML = loaderContent;

        if (fullPage) {
            document.body.appendChild(loader);
        } else {
            container.appendChild(loader);
        }

        loaders.set(id, { loader, options });
    }

    function showLoading(id, options = {}) {
        const existing = loaders.get(id);
        if (existing) {
            existing.loader.style.display = 'flex';
            return;
        }
        createLoader(id, options);
    }

    function hideLoading(id) {
        const data = loaders.get(id);
        if (data) {
            data.loader.style.display = 'none';
        }
    }

    function removeLoading(id) {
        const data = loaders.get(id);
        if (data) {
            data.loader.remove();
            loaders.delete(id);
        }
    }

    document.addEventListener('fw:event:showLoading', (e) => {
        const { id, ...options } = e.detail;
        showLoading(id, options);
    });

    document.addEventListener('fw:event:hideLoading', (e) => {
        const { id } = e.detail || {};
        if (id) {
            hideLoading(id);
        } else {
            loaders.forEach((_, loaderId) => hideLoading(loaderId));
        }
    });

    document.addEventListener('fw:event:removeLoading', (e) => {
        const { id } = e.detail || {};
        if (id) {
            removeLoading(id);
        } else {
            loaders.forEach((_, loaderId) => removeLoading(loaderId));
        }
    });
})();
</script>
