<?php

use App\Modules\ForgeUi\DesignTokens;

$id = $id ?? 'fw-modal';
$size = $size ?? 'md';
$closable = $closable ?? true;
$backdrop = $backdrop ?? true;

$modalClasses = class_merge(DesignTokens::modal($size), ['hidden'], $class ?? '');
$contentClasses = class_merge(DesignTokens::modalContent($size), $class ?? '');
?>
<div id="<?= e($id) ?>" class="<?= $modalClasses ?>" data-modal-id="<?= e($id) ?>">
    <?php if ($backdrop): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" data-modal-close></div>
    <?php endif; ?>

    <div class="relative z-50 w-full h-full flex items-center justify-center p-4">
        <div class="<?= $contentClasses ?>">
            <?php if ($closable): ?>
                <?php if (isset($slots['closeButton'])): ?>
                    <?= $slots['closeButton'] ?>
                <?php else: ?>
                    <div class="flex justify-end p-2">
                        <button class="fw-modal-close text-gray-400 hover:text-gray-600 transition-colors" data-modal-close aria-label="Close">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($slots['icon'])): ?>
                <div class="fw-modal-icon flex justify-center mb-4">
                    <?= $slots['icon'] ?>
                </div>
            <?php endif; ?>

            <?php if (isset($slots['header']) || isset($slots['title'])): ?>
            <div class="fw-modal-header px-6 pt-6 pb-4">
                <?php if (isset($slots['title'])): ?>
                    <h3 class="fw-modal-title text-lg font-semibold text-gray-900"><?= $slots['title'] ?></h3>
                <?php elseif (isset($slots['header'])): ?>
                    <?= $slots['header'] ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="fw-modal-body px-6 py-4">
                <?php if (isset($slots['default'])): ?>
                    <?= $slots['default'] ?>
                <?php elseif (isset($slots['message'])): ?>
                    <p class="fw-modal-message text-gray-600"><?= $slots['message'] ?></p>
                <?php endif; ?>
            </div>

            <?php if (isset($slots['footer']) || isset($slots['actions'])): ?>
            <div class="fw-modal-footer px-6 pb-6 pt-4 border-t border-gray-200">
                <?php if (isset($slots['actions'])): ?>
                    <?= $slots['actions'] ?>
                <?php elseif (isset($slots['footer'])): ?>
                    <?= $slots['footer'] ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('<?= e($id) ?>');
    if (!modal) return;

    function setupModalEvents() {
        const closeElements = modal.querySelectorAll('[data-modal-close]');
        closeElements.forEach(el => {
            el.addEventListener('click', () => {
                closeModal();
            });
        });

        const backdrop = modal.querySelector('.fw-modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    closeModal();
                }
            });
        }
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            modal.style.display = '';
        });
    }

    function closeModal() {
        modal.classList.add('hidden');
        const visibleModals = document.querySelectorAll('.fw-modal:not(.hidden)');
        if (visibleModals.length === 0) {
            document.body.style.overflow = '';
        }
    }

    setupModalEvents();

    document.addEventListener('fw:event:openModal', (e) => {
        if (e.detail.id === '<?= e($id) ?>') {
            openModal();
        }
    });

    document.addEventListener('fw:event:closeModal', (e) => {
        const { id } = e.detail || {};
        if (!id || id === '<?= e($id) ?>') {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>
