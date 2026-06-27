<?php
?>
<div id="fw-progress-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-progress-container');
    if (!container) return;

    const progressBars = new Map();

    function createProgress(id, value, options = {}) {
        const type = options.type || 'linear';
        const showLabel = options.showLabel !== false;
        const max = options.max || 100;
        const color = options.color || 'blue';

        const existing = document.getElementById('fw-progress-' + id);
        if (existing) {
            existing.remove();
        }

        const progressWrapper = document.createElement('div');
        progressWrapper.id = 'fw-progress-' + id;
        progressWrapper.setAttribute('data-progress-id', id);

        if (type === 'linear') {
            const progress = document.createElement('div');
            progress.className = 'fw-progress';
            progress.setAttribute('role', 'progressbar');
            progress.setAttribute('aria-valuenow', value);
            progress.setAttribute('aria-valuemin', '0');
            progress.setAttribute('aria-valuemax', max);

            const bar = document.createElement('div');
            bar.className = 'fw-progress-bar bg-' + color + '-600';
            bar.style.width = Math.min(100, Math.max(0, (value / max) * 100)) + '%';

            progress.appendChild(bar);
            progressWrapper.appendChild(progress);

            if (showLabel) {
                const label = document.createElement('div');
                label.className = 'fw-progress-label';
                label.textContent = Math.round((value / max) * 100) + '%';
                progressWrapper.appendChild(label);
            }
        } else if (type === 'circular') {
            const size = options.size || 100;
            const strokeWidth = options.strokeWidth || 8;
            const radius = (size - strokeWidth) / 2;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (value / max) * circumference;

            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('width', size);
            svg.setAttribute('height', size);
            svg.className = 'fw-progress-circular';

            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', size / 2);
            circle.setAttribute('cy', size / 2);
            circle.setAttribute('r', radius);
            circle.setAttribute('fill', 'none');
            circle.setAttribute('stroke', '#e5e7eb');
            circle.setAttribute('stroke-width', strokeWidth);

            const progressCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            progressCircle.setAttribute('cx', size / 2);
            progressCircle.setAttribute('cy', size / 2);
            progressCircle.setAttribute('r', radius);
            progressCircle.setAttribute('fill', 'none');
            progressCircle.setAttribute('stroke', 'currentColor');
            progressCircle.setAttribute('stroke-width', strokeWidth);
            progressCircle.setAttribute('stroke-dasharray', circumference);
            progressCircle.setAttribute('stroke-dashoffset', offset);
            progressCircle.setAttribute('stroke-linecap', 'round');
            progressCircle.className = 'fw-progress-circle text-' + color + '-600';
            progressCircle.style.transition = 'stroke-dashoffset 0.3s ease';

            svg.appendChild(circle);
            svg.appendChild(progressCircle);
            progressWrapper.appendChild(svg);

            if (showLabel) {
                const label = document.createElement('div');
                label.className = 'fw-progress-label text-center mt-2';
                label.textContent = Math.round((value / max) * 100) + '%';
                progressWrapper.appendChild(label);
            }
        }

        container.appendChild(progressWrapper);
        progressBars.set(id, { progressWrapper, value, max, type, options });
    }

    function updateProgress(id, value) {
        const data = progressBars.get(id);
        if (!data) return;

        const { progressWrapper, max, type, options } = data;
        const percentage = Math.min(100, Math.max(0, (value / max) * 100));

        if (type === 'linear') {
            const bar = progressWrapper.querySelector('.fw-progress-bar');
            if (bar) {
                bar.style.width = percentage + '%';
                progressWrapper.setAttribute('aria-valuenow', value);
            }
            const label = progressWrapper.querySelector('.fw-progress-label');
            if (label) {
                label.textContent = Math.round(percentage) + '%';
            }
        } else if (type === 'circular') {
            const size = options.size || 100;
            const strokeWidth = options.strokeWidth || 8;
            const radius = (size - strokeWidth) / 2;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (value / max) * circumference;

            const progressCircle = progressWrapper.querySelector('.fw-progress-circle');
            if (progressCircle) {
                progressCircle.setAttribute('stroke-dashoffset', offset);
            }
            const label = progressWrapper.querySelector('.fw-progress-label');
            if (label) {
                label.textContent = Math.round(percentage) + '%';
            }
        }

        data.value = value;
    }

    document.addEventListener('fw:event:createProgress', (e) => {
        const { id, value, ...options } = e.detail;
        createProgress(id, value, options);
    });

    document.addEventListener('fw:event:updateProgress', (e) => {
        const { id, value } = e.detail;
        updateProgress(id, value);
    });
})();
</script>
