<?php
?>
<div id="fw-accordions-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-accordions-container');
    if (!container) return;

    const accordions = new Map();

    function createAccordion(id, items, options = {}) {
        const existing = document.getElementById('fw-accordion-' + id);
        if (!existing) {
            const accordion = document.createElement('div');
            accordion.id = 'fw-accordion-' + id;
            accordion.className = 'fw-accordion';
            accordion.setAttribute('data-accordion-id', id);
            container.appendChild(accordion);
        }

        const accordion = document.getElementById('fw-accordion-' + id);
        accordion.innerHTML = '';

        const allowMultiple = options.allowMultiple || false;
        const openItems = options.openItems || [];

        items.forEach((item, index) => {
            const itemEl = document.createElement('div');
            itemEl.className = 'fw-accordion-item';
            itemEl.setAttribute('data-item-index', index);

            const trigger = document.createElement('button');
            trigger.className = 'fw-accordion-trigger' + (openItems.includes(index) ? ' open' : '');
            trigger.innerHTML = `
                <span>${item.title || item.label || 'Item ' + (index + 1)}</span>
                <svg class="fw-accordion-trigger-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            `;

            const content = document.createElement('div');
            content.className = 'fw-accordion-content' + (openItems.includes(index) ? ' open' : '');
            content.innerHTML = item.content || '';

            trigger.addEventListener('click', () => {
                toggleItem(id, index, allowMultiple);
            });

            itemEl.appendChild(trigger);
            itemEl.appendChild(content);
            accordion.appendChild(itemEl);
        });

        accordions.set(id, { accordion, items, options, openItems: [...openItems] });
    }

    function toggleItem(id, index, allowMultiple) {
        const data = accordions.get(id);
        if (!data) return;

        const { accordion, openItems } = data;
        const item = accordion.querySelector(`[data-item-index="${index}"]`);
        if (!item) return;

        const trigger = item.querySelector('.fw-accordion-trigger');
        const content = item.querySelector('.fw-accordion-content');
        const isOpen = openItems.includes(index);

        if (isOpen) {
            trigger.classList.remove('open');
            content.classList.remove('open');
            data.openItems = openItems.filter(i => i !== index);
        } else {
            if (!allowMultiple) {
                openItems.forEach(openIndex => {
                    const openItem = accordion.querySelector(`[data-item-index="${openIndex}"]`);
                    if (openItem) {
                        openItem.querySelector('.fw-accordion-trigger').classList.remove('open');
                        openItem.querySelector('.fw-accordion-content').classList.remove('open');
                    }
                });
                data.openItems = [];
            }
            trigger.classList.add('open');
            content.classList.add('open');
            data.openItems.push(index);
        }
    }

    document.addEventListener('fw:event:createAccordion', (e) => {
        const { id, items, ...options } = e.detail;
        createAccordion(id, items, options);
    });

    document.addEventListener('fw:event:toggleAccordion', (e) => {
        const { id, index } = e.detail;
        const data = accordions.get(id);
        if (data) {
            toggleItem(id, index, data.options.allowMultiple || false);
        }
    });
})();
</script>
