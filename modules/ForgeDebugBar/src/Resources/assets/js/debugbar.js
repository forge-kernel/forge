document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.forge-debugbar-tab');
    const panels = document.querySelectorAll('.forge-debugbar-panel');
    const logo = document.querySelector('.forge-debugbar-logo');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const tabName = this.dataset.tab;
            const isActive = this.classList.contains('active');

            if (isActive) {
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                return;
            }

            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const targetPanel = document.getElementById(`debugbar-panel-${tabName}`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    if (logo) {
        logo.addEventListener('click', function (e) {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
        });
    }

    const toggles = document.querySelectorAll('.clickable-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                targetElement.classList.toggle('is-collapsed');
                this.classList.toggle('active');
            }
        });
    });

});
