<?php
?>
<div id="fw-tabs-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-tabs-container');
    if (!container) return;

    const tabs = new Map();

    function createTabs(id, tabItems, options = {}) {
        const existing = document.getElementById('fw-tabs-' + id);
        if (existing) {
            existing.remove();
        }

        const orientation = options.orientation || 'horizontal';
        const defaultActive = options.defaultActive || 0;

        const tabsWrapper = document.createElement('div');
        tabsWrapper.id = 'fw-tabs-' + id;
        tabsWrapper.className = 'fw-tabs';
        tabsWrapper.setAttribute('data-tabs-id', id);

        const tabsList = document.createElement('div');
        tabsList.className = orientation === 'vertical' ? 'fw-tabs-list fw-tabs-list-vertical' : 'fw-tabs-list';
        tabsList.setAttribute('role', 'tablist');

        const tabsContent = document.createElement('div');
        tabsContent.className = 'fw-tabs-content';

        tabItems.forEach((tab, index) => {
            const trigger = document.createElement('button');
            trigger.className = 'fw-tabs-trigger' + (index === defaultActive ? ' active' : '');
            trigger.setAttribute('role', 'tab');
            trigger.setAttribute('aria-selected', index === defaultActive ? 'true' : 'false');
            trigger.setAttribute('data-tab-index', index);
            trigger.textContent = tab.label || tab.title || 'Tab ' + (index + 1);

            if (tab.icon) {
                const icon = document.createElement('span');
                icon.innerHTML = tab.icon;
                icon.className = 'mr-2';
                trigger.insertBefore(icon, trigger.firstChild);
            }

            if (tab.badge) {
                const badge = document.createElement('span');
                badge.className = 'ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full';
                badge.textContent = tab.badge;
                trigger.appendChild(badge);
            }

            trigger.addEventListener('click', () => {
                switchTab(id, index);
            });

            tabsList.appendChild(trigger);

            const panel = document.createElement('div');
            panel.className = 'fw-tabs-panel' + (index === defaultActive ? ' active' : '');
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-hidden', index === defaultActive ? 'false' : 'true');
            panel.innerHTML = tab.content || '';

            tabsContent.appendChild(panel);
        });

        tabsWrapper.appendChild(tabsList);
        tabsWrapper.appendChild(tabsContent);
        container.appendChild(tabsWrapper);

        tabs.set(id, { tabsWrapper, tabItems, options, activeIndex: defaultActive });
    }

    function switchTab(id, index) {
        const data = tabs.get(id);
        if (!data) return;

        const { tabsWrapper, activeIndex } = data;
        const triggers = tabsWrapper.querySelectorAll('.fw-tabs-trigger');
        const panels = tabsWrapper.querySelectorAll('.fw-tabs-panel');

        triggers[activeIndex].classList.remove('active');
        triggers[activeIndex].setAttribute('aria-selected', 'false');
        panels[activeIndex].classList.remove('active');
        panels[activeIndex].setAttribute('aria-hidden', 'true');

        triggers[index].classList.add('active');
        triggers[index].setAttribute('aria-selected', 'true');
        panels[index].classList.add('active');
        panels[index].setAttribute('aria-hidden', 'false');

        data.activeIndex = index;
    }

    document.addEventListener('fw:event:createTabs', (e) => {
        const { id, tabItems, ...options } = e.detail;
        createTabs(id, tabItems, options);
    });

    document.addEventListener('fw:event:switchTab', (e) => {
        const { id, index } = e.detail;
        switchTab(id, index);
    });

    if (window.FwComponentManager) {
        window.FwComponentManager.delegate('keydown', '.fw-tabs-trigger', function(e) {
            const tabsWrapper = this.closest('.fw-tabs');
            if (!tabsWrapper) return;

            const id = tabsWrapper.getAttribute('data-tabs-id');
            const data = tabs.get(id);
            if (!data) return;

            const triggers = Array.from(tabsWrapper.querySelectorAll('.fw-tabs-trigger'));
            const currentIndex = triggers.indexOf(this);
            let newIndex = currentIndex;

            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                newIndex = currentIndex > 0 ? currentIndex - 1 : triggers.length - 1;
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                newIndex = currentIndex < triggers.length - 1 ? currentIndex + 1 : 0;
            } else if (e.key === 'Home') {
                e.preventDefault();
                newIndex = 0;
            } else if (e.key === 'End') {
                e.preventDefault();
                newIndex = triggers.length - 1;
            }

            if (newIndex !== currentIndex) {
                switchTab(id, newIndex);
                triggers[newIndex].focus();
            }
        });
    }
})();
</script>
