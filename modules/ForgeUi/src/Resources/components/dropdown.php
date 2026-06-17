<?php

use App\Modules\ForgeUi\DesignTokens;

$id = $id ?? 'fw-dropdown-' . uniqid();
$placement = $placement ?? 'bottom-start';

if (isset($slots['trigger']) && isset($slots['menu'])) {
    $triggerClasses = class_merge(['fw-dropdown-trigger'], $class ?? '');
    $menuClasses = class_merge(['fw-dropdown-menu', 'hidden', 'absolute', 'z-50', 'mt-1', 'min-w-[200px]', 'bg-white', 'rounded-md', 'shadow-lg', 'border', 'border-gray-200', 'py-1'], $class ?? '');
    ?>
    <div class="relative inline-block" data-dropdown-id="<?= e($id) ?>">
        <div class="<?= $triggerClasses ?>" data-dropdown-trigger="<?= e($id) ?>">
            <?= $slots['trigger'] ?>
        </div>
        <div id="fw-dropdown-<?= e($id) ?>" class="<?= $menuClasses ?>" data-dropdown-menu="<?= e($id) ?>" role="menu">
            <?= $slots['menu'] ?>
        </div>
    </div>
    <?php
}
?>
<div id="fw-dropdowns-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-dropdowns-container');
    if (!container) return;

    const dropdowns = new Map();

    function createDropdown(id, items, options = {}) {
        const existing = document.getElementById('fw-dropdown-' + id);
        if (existing) {
            existing.remove();
        }

        const dropdown = document.createElement('div');
        dropdown.id = 'fw-dropdown-' + id;
        dropdown.className = 'fw-dropdown';
        dropdown.setAttribute('data-dropdown-id', id);

        const menu = document.createElement('div');
        menu.className = 'fw-dropdown-menu';
        menu.setAttribute('role', 'menu');

        items.forEach((item, index) => {
            if (item.divider) {
                const divider = document.createElement('div');
                divider.className = 'fw-dropdown-divider';
                menu.appendChild(divider);
            } else {
                const menuItem = document.createElement('div');
                menuItem.className = 'fw-dropdown-item';
                if (item.disabled) {
                    menuItem.classList.add('disabled');
                }
                menuItem.setAttribute('role', 'menuitem');
                menuItem.setAttribute('tabindex', item.disabled ? '-1' : '0');
                menuItem.textContent = item.label || item.text || '';

                if (item.icon) {
                    const icon = document.createElement('span');
                    icon.innerHTML = item.icon;
                    icon.className = 'mr-2';
                    menuItem.insertBefore(icon, menuItem.firstChild);
                }

                if (!item.disabled && (item.action || item.onClick)) {
                    menuItem.addEventListener('click', () => {
                        if (item.action) {
                            window.location.href = item.action;
                        } else if (item.onClick) {
                            item.onClick();
                        }
                        closeDropdown(id);
                    });
                }

                menu.appendChild(menuItem);
            }
        });

        dropdown.appendChild(menu);
        container.appendChild(dropdown);

        dropdowns.set(id, { dropdown, menu, items, options });
    }

    function openDropdown(id, targetElement) {
        const data = dropdowns.get(id);
        if (!data) return;

        const { dropdown, menu } = data;
        const targetRect = targetElement.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();

        menu.style.top = (targetRect.bottom + 4) + 'px';
        menu.style.left = targetRect.left + 'px';
        menu.classList.add('open');

        document.addEventListener('click', function closeOnOutside(e) {
            if (!dropdown.contains(e.target) && !targetElement.contains(e.target)) {
                closeDropdown(id);
                document.removeEventListener('click', closeOnOutside);
            }
        });
    }

    function closeDropdown(id) {
        const data = dropdowns.get(id);
        if (data) {
            data.menu.classList.remove('open');
        }
    }

    function toggleDropdown(id, targetElement) {
        const data = dropdowns.get(id);
        if (data && data.menu.classList.contains('open')) {
            closeDropdown(id);
        } else {
            openDropdown(id, targetElement);
        }
    }

    document.addEventListener('fw:event:createDropdown', (e) => {
        const { id, items, ...options } = e.detail;
        createDropdown(id, items, options);
    });

    document.addEventListener('fw:event:openDropdown', (e) => {
        const { id, target } = e.detail;
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            openDropdown(id, element);
        }
    });

    document.addEventListener('fw:event:closeDropdown', (e) => {
        const { id } = e.detail || {};
        if (id) {
            closeDropdown(id);
        } else {
            dropdowns.forEach((_, dropdownId) => closeDropdown(dropdownId));
        }
    });

    document.addEventListener('fw:event:toggleDropdown', (e) => {
        const { id, target } = e.detail;
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            toggleDropdown(id, element);
        }
    });

    if (window.FwComponentManager) {
        window.FwComponentManager.delegate('keydown', '.fw-dropdown-item', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }

    document.querySelectorAll('[data-dropdown-trigger]').forEach(trigger => {
        const id = trigger.getAttribute('data-dropdown-trigger');
        const menu = document.getElementById('fw-dropdown-' + id);
        if (!menu) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                menu.classList.add('hidden');
            } else {
                menu.classList.remove('hidden');
                const rect = trigger.getBoundingClientRect();
                menu.style.top = (rect.bottom + 4) + 'px';
                menu.style.left = rect.left + 'px';
            }
        });

        document.addEventListener('click', function closeOnOutside(e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    });
})();
</script>
