<?php
?>
<div id="fw-modals-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-modals-container');
    if (!container) return;

    const sizeClasses = {
        sm: 'fw-modal-content-sm',
        md: 'fw-modal-content',
        lg: 'fw-modal-content-lg',
        xl: 'fw-modal-content-xl',
        '2xl': 'fw-modal-content-2xl',
        full: 'fw-modal-content-full',
    };

    function createModal(id, title, message, options = {}) {
        const existing = document.getElementById(id);
        if (existing) {
            openModal(id, title, message, options);
            return;
        }

        const size = options.size || 'md';
        const sizeClass = sizeClasses[size] || sizeClasses.md;
        const closable = options.closable !== false;
        const backdrop = options.backdrop !== false;
        const showCancel = options.showCancel !== false;
        const confirmText = options.confirmText || 'Confirm';
        const cancelText = options.cancelText || 'Cancel';

        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'fw-modal hidden';
        modal.setAttribute('data-modal-id', id);

        modal.innerHTML = `
            ${backdrop ? '<div class="fw-modal-backdrop" data-modal-close></div>' : ''}
            <div class="fw-modal-wrapper">
                <div class="${sizeClass}">
                    ${closable ? `
                    <div class="flex justify-end p-2">
                        <button class="fw-modal-close" data-modal-close aria-label="Close">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    ` : ''}
                    ${title ? `
                    <div class="fw-modal-header">
                        <h3 class="fw-modal-title">${title}</h3>
                    </div>
                    ` : ''}
                    <div class="fw-modal-body">
                        <p class="fw-modal-message">${message}</p>
                    </div>
                    ${showCancel ? `
                    <div class="fw-modal-footer">
                        <button class="fw-modal-cancel" data-modal-close>
                            ${cancelText}
                        </button>
                        <button class="fw-modal-confirm" data-modal-confirm>
                            ${confirmText}
                        </button>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;

        setupModalEvents(modal, id, options);
        container.appendChild(modal);
        openModal(id, title, message, options);
    }

    function openModal(id, title, message, options = {}) {
        const modal = document.getElementById(id);
        if (!modal) return;

        const titleEl = modal.querySelector('.fw-modal-title');
        const messageEl = modal.querySelector('.fw-modal-message');
        
        if (titleEl) titleEl.textContent = title || '';
        if (messageEl) messageEl.textContent = message || '';

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        requestAnimationFrame(() => {
            modal.style.display = '';
        });
    }

    function closeModal(id) {
        const modal = id ? document.getElementById(id) : container.querySelector('.fw-modal:not(.hidden)');
        if (!modal) return;

        modal.classList.add('hidden');
        const visibleModals = container.querySelectorAll('.fw-modal:not(.hidden)');
        if (visibleModals.length === 0) {
            document.body.style.overflow = '';
        }
    }

    function setupModalEvents(modal, id, options) {
        const closeElements = modal.querySelectorAll('[data-modal-close]');
        closeElements.forEach(el => {
            el.addEventListener('click', () => {
                if (options.cancelAction) {
                    const event = new CustomEvent('fw:modal:cancel', { detail: { id, ...options } });
                    window.dispatchEvent(event);
                }
                closeModal(id);
            });
        });

        const confirmBtn = modal.querySelector('[data-modal-confirm]');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (options.confirmAction) {
                    const event = new CustomEvent('fw:modal:confirm', { detail: { id, ...options } });
                    window.dispatchEvent(event);
                }
                closeModal(id);
            });
        }

        const backdrop = modal.querySelector('.fw-modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    closeModal(id);
                }
            });
        }
    }

    document.addEventListener('fw:event:openModal', (e) => {
        const { id, title = '', message = '', ...options } = e.detail;
        const existingModal = document.getElementById(id);
        if (existingModal) {
            openModal(id, title, message, options);
        } else {
            createModal(id, title, message, options);
        }
    });

    document.addEventListener('fw:event:closeModal', (e) => {
        const { id } = e.detail || {};
        closeModal(id);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const visibleModal = document.querySelector('.fw-modal:not(.hidden)');
            if (visibleModal) {
                closeModal(visibleModal.id);
            }
        }
    });
})();
</script>
