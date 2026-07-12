(function () {
    'use strict';

    var bar = document.querySelector('.fdb-bar');
    if (!bar) return;

    var panelsContainer = document.querySelector('.fdb-panels');
    if (!panelsContainer) return;

    var lastActiveTab = null;

    function closeAll() {
        var activeTabs = bar.querySelectorAll('.fdb-tab--active');
        var activePanels = panelsContainer.querySelectorAll('.fdb-panel--active');
        for (var i = 0; i < activeTabs.length; i++) {
            activeTabs[i].classList.remove('fdb-tab--active');
        }
        for (var j = 0; j < activePanels.length; j++) {
            activePanels[j].classList.remove('fdb-panel--active');
        }
    }

    function openTab(tabName) {
        var panel = document.getElementById('fdb-panel-' + tabName);
        var tab = bar.querySelector('.fdb-tab[data-tab="' + tabName + '"]');
        if (!tab || !panel) return;
        closeAll();
        tab.classList.add('fdb-tab--active');
        panel.classList.add('fdb-panel--active');
    }

    function getActiveTab() {
        var active = bar.querySelector('.fdb-tab--active');
        return active ? active.getAttribute('data-tab') : null;
    }

    bar.addEventListener('click', function (e) {
        var tab = e.target.closest('.fdb-tab');
        if (!tab) return;
        var tabName = tab.getAttribute('data-tab');
        if (tab.classList.contains('fdb-tab--active')) {
            closeAll();
        } else {
            openTab(tabName);
        }
    });

    var brand = bar.querySelector('.fdb-bar__brand');
    if (brand) {
        brand.addEventListener('click', function () {
            var isCollapsed = bar.classList.contains('fdb-bar--collapsed');

            if (isCollapsed) {
                bar.classList.remove('fdb-bar--collapsed');
                if (lastActiveTab) {
                    openTab(lastActiveTab);
                    lastActiveTab = null;
                }
            } else {
                lastActiveTab = getActiveTab();
                closeAll();
                bar.classList.add('fdb-bar--collapsed');
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (bar.classList.contains('fdb-bar--collapsed')) {
                bar.classList.remove('fdb-bar--collapsed');
                if (lastActiveTab) {
                    openTab(lastActiveTab);
                    lastActiveTab = null;
                }
            } else {
                closeAll();
            }
        }
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            if (bar.classList.contains('fdb-bar--collapsed')) return;
            var activeTab = bar.querySelector('.fdb-tab--active');
            if (!activeTab) return;
            var allTabs = bar.querySelectorAll('.fdb-tab');
            var currentIndex = Array.prototype.indexOf.call(allTabs, activeTab);
            var nextIndex = e.key === 'ArrowRight'
                ? (currentIndex + 1) % allTabs.length
                : (currentIndex - 1 + allTabs.length) % allTabs.length;
            e.preventDefault();
            openTab(allTabs[nextIndex].getAttribute('data-tab'));
        }
    });

    var toggles = panelsContainer.querySelectorAll('[data-toggle]');
    for (var k = 0; k < toggles.length; k++) {
        toggles[k].addEventListener('click', function () {
            var target = document.querySelector(this.getAttribute('data-toggle'));
            if (target) {
                target.classList.toggle('is-collapsed');
            }
        });
    }
})();
