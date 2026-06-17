<?php
?>
<div id="fw-popovers-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-popovers-container');
    if (!container) return;

    const popovers = new Map();

    function createPopover(target, content, options = {}) {
        const position = options.position || 'auto';
        const trigger = options.trigger || 'click';
        const id = 'popover-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        const popover = document.createElement('div');
        popover.id = id;
        popover.className = 'fw-popover fw-popover-' + position;
        popover.innerHTML = `
            ${content}
            <div class="fw-popover-arrow"></div>
        `;
        popover.style.display = 'none';
        document.body.appendChild(popover);

        const show = () => {
            popover.style.display = 'block';
            updatePosition(target, popover, position);
        };

        const hide = () => {
            popover.style.display = 'none';
        };

        if (trigger === 'hover') {
            let showTimeout, hideTimeout;
            target.addEventListener('mouseenter', () => {
                clearTimeout(hideTimeout);
                showTimeout = setTimeout(show, 200);
            });
            target.addEventListener('mouseleave', () => {
                clearTimeout(showTimeout);
                hideTimeout = setTimeout(hide, 200);
            });
            popover.addEventListener('mouseenter', () => {
                clearTimeout(hideTimeout);
            });
            popover.addEventListener('mouseleave', () => {
                hideTimeout = setTimeout(hide, 200);
            });
        } else if (trigger === 'click') {
            target.addEventListener('click', (e) => {
                e.stopPropagation();
                if (popover.style.display === 'none') {
                    show();
                } else {
                    hide();
                }
            });
        } else if (trigger === 'focus') {
            target.addEventListener('focus', show);
            target.addEventListener('blur', hide);
        }

        document.addEventListener('click', function closeOnOutside(e) {
            if (!popover.contains(e.target) && !target.contains(e.target)) {
                hide();
            }
        });

        popovers.set(id, { popover, target, show, hide });
        return id;
    }

    function updatePosition(target, popover, position) {
        const targetRect = target.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
        let top, left;

        if (position === 'auto') {
            position = getAutoPosition(targetRect, popoverRect);
            popover.className = 'fw-popover fw-popover-' + position;
        }

        switch (position) {
            case 'top':
                top = targetRect.top - popoverRect.height - 8;
                left = targetRect.left + (targetRect.width / 2) - (popoverRect.width / 2);
                break;
            case 'bottom':
                top = targetRect.bottom + 8;
                left = targetRect.left + (targetRect.width / 2) - (popoverRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + (targetRect.height / 2) - (popoverRect.height / 2);
                left = targetRect.left - popoverRect.width - 8;
                break;
            case 'right':
                top = targetRect.top + (targetRect.height / 2) - (popoverRect.height / 2);
                left = targetRect.right + 8;
                break;
        }

        popover.style.top = Math.max(8, top) + 'px';
        popover.style.left = Math.max(8, Math.min(left, window.innerWidth - popoverRect.width - 8)) + 'px';
    }

    function getAutoPosition(targetRect, popoverRect) {
        const space = {
            top: targetRect.top,
            bottom: window.innerHeight - targetRect.bottom,
            left: targetRect.left,
            right: window.innerWidth - targetRect.right
        };

        if (space.bottom >= popoverRect.height + 8) return 'bottom';
        if (space.top >= popoverRect.height + 8) return 'top';
        if (space.right >= popoverRect.width + 8) return 'right';
        if (space.left >= popoverRect.width + 8) return 'left';
        return 'bottom';
    }

    function removePopover(id) {
        const data = popovers.get(id);
        if (data) {
            data.popover.remove();
            popovers.delete(id);
        }
    }

    document.addEventListener('fw:event:showPopover', (e) => {
        const { target, content, ...options } = e.detail;
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            createPopover(element, content, options);
        }
    });

    document.addEventListener('fw:event:hidePopover', (e) => {
        const { id } = e.detail || {};
        if (id) {
            const data = popovers.get(id);
            if (data) {
                data.hide();
            }
        } else {
            popovers.forEach((data) => data.hide());
        }
    });

    document.addEventListener('fw:event:removePopover', (e) => {
        const { id } = e.detail || {};
        if (id) {
            removePopover(id);
        } else {
            popovers.forEach((_, popoverId) => removePopover(popoverId));
        }
    });
})();
</script>
