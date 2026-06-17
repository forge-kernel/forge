<?php

use App\Modules\ForgeUi\DesignTokens;

$id = $id ?? 'fw-drawer-' . uniqid();
$position = $position ?? 'right';

if (isset($slots['default'])) {
    $positionClasses = [
        'left' => ['left-0', 'top-0', 'bottom-0'],
        'right' => ['right-0', 'top-0', 'bottom-0'],
        'top' => ['top-0', 'left-0', 'right-0'],
        'bottom' => ['bottom-0', 'left-0', 'right-0'],
    ];
    $posClass = $positionClasses[$position] ?? $positionClasses['right'];
    $drawerClasses = class_merge(['fixed', 'z-50', 'bg-white', 'shadow-xl', 'transform', 'transition-transform'], $posClass, $class ?? '');
    ?>
    <div id="fw-drawer-<?= e($id) ?>" class="<?= $drawerClasses ?> hidden" data-drawer-id="<?= e($id) ?>">
        <?php if (isset($slots['header']) || isset($slots['title'])): ?>
            <div class="fw-drawer-header flex items-center justify-between p-4 border-b border-gray-200">
                <?php if (isset($slots['title'])): ?>
                    <h3 class="fw-drawer-title text-lg font-semibold text-gray-900"><?= $slots['title'] ?></h3>
                <?php elseif (isset($slots['header'])): ?>
                    <?= $slots['header'] ?>
                <?php endif; ?>
                <?php if (isset($slots['closeButton'])): ?>
                    <?= $slots['closeButton'] ?>
                <?php else: ?>
                    <button class="fw-drawer-close text-gray-400 hover:text-gray-600 transition-colors" data-drawer-close="<?= e($id) ?>" aria-label="Close">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="fw-drawer-body p-4 overflow-auto">
            <?= $slots['default'] ?>
        </div>
        <?php if (isset($slots['footer'])): ?>
            <div class="fw-drawer-footer p-4 border-t border-gray-200">
                <?= $slots['footer'] ?>
            </div>
        <?php endif; ?>
    </div>
    <div id="fw-drawer-backdrop-<?= e($id) ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" data-drawer-backdrop="<?= e($id) ?>"></div>
    <?php
}
?>
<div id="fw-drawers-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-drawers-container');
    if (!container) return;

    const drawers = new Map();

    function createDrawer(id, content, options = {}) {
        const existing = document.getElementById('fw-drawer-' + id);
        if (existing) {
            existing.remove();
        }

        const position = options.position || 'right';
        const width = options.width || (position === 'left' || position === 'right' ? '400px' : '100%');
        const height = options.height || (position === 'top' || position === 'bottom' ? '400px' : '100%');

        const backdrop = document.createElement('div');
        backdrop.className = 'fw-drawer-backdrop';
        backdrop.setAttribute('data-drawer-backdrop', id);

        const drawer = document.createElement('div');
        drawer.id = 'fw-drawer-' + id;
        drawer.className = 'fw-drawer fw-drawer-' + position;
        drawer.style.width = position === 'left' || position === 'right' ? width : '100%';
        drawer.style.height = position === 'top' || position === 'bottom' ? height : '100%';
        drawer.setAttribute('data-drawer-id', id);

        const header = document.createElement('div');
        header.className = 'fw-drawer-header';

        const title = document.createElement('h3');
        title.className = 'fw-drawer-title';
        title.textContent = options.title || '';

        const closeBtn = document.createElement('button');
        closeBtn.className = 'fw-drawer-close';
        closeBtn.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        `;
        closeBtn.setAttribute('aria-label', 'Close');

        header.appendChild(title);
        header.appendChild(closeBtn);

        const body = document.createElement('div');
        body.className = 'fw-drawer-body';
        body.innerHTML = content || '';

        drawer.appendChild(header);
        drawer.appendChild(body);

        document.body.appendChild(backdrop);
        document.body.appendChild(drawer);

        const close = () => {
            closeDrawer(id);
        };

        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);

        drawers.set(id, { drawer, backdrop, close, options });
    }

    function openDrawer(id) {
        const data = drawers.get(id);
        if (!data) return;

        const { drawer, backdrop } = data;
        drawer.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer(id) {
        const data = drawers.get(id);
        if (!data) return;

        const { drawer, backdrop } = data;
        drawer.classList.remove('open');
        backdrop.classList.remove('open');

        const visibleDrawers = Array.from(document.querySelectorAll('.fw-drawer.open'));
        if (visibleDrawers.length === 0) {
            document.body.style.overflow = '';
        }
    }

    function removeDrawer(id) {
        const data = drawers.get(id);
        if (!data) return;

        const { drawer, backdrop } = data;
        drawer.remove();
        backdrop.remove();
        drawers.delete(id);
    }

    document.addEventListener('fw:event:openDrawer', (e) => {
        const { id, content, ...options } = e.detail;
        const existing = drawers.get(id);
        if (existing) {
            if (content) {
                const body = existing.drawer.querySelector('.fw-drawer-body');
                if (body) {
                    body.innerHTML = content;
                }
            }
            openDrawer(id);
        } else {
            createDrawer(id, content, options);
            openDrawer(id);
        }
    });

    document.addEventListener('fw:event:closeDrawer', (e) => {
        const { id } = e.detail || {};
        if (id) {
            closeDrawer(id);
        } else {
            drawers.forEach((_, drawerId) => closeDrawer(drawerId));
        }
    });

    document.addEventListener('fw:event:removeDrawer', (e) => {
        const { id } = e.detail || {};
        if (id) {
            removeDrawer(id);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const visibleDrawer = document.querySelector('.fw-drawer.open');
            if (visibleDrawer) {
                const id = visibleDrawer.getAttribute('data-drawer-id');
                if (id) {
                    closeDrawer(id);
                }
            }
        }
    });
})();
</script>
