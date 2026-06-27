<?php
?>
<div id="fw-tooltip-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-tooltip-container');
    if (!container) return;

    const tooltips = new Map();
    let activeTooltip = null;

    function createTooltip(target, content, options = {}) {
        const position = options.position || 'auto';
        const delay = options.delay || 200;
        const trigger = options.trigger || 'hover';

        const tooltip = document.createElement('div');
        tooltip.className = 'fw-tooltip fw-tooltip-' + position;
        tooltip.innerHTML = `
            ${content}
            <div class="fw-tooltip-arrow"></div>
        `;
        tooltip.style.display = 'none';
        container.appendChild(tooltip);

        const show = () => {
            if (activeTooltip && activeTooltip !== tooltip) {
                hideTooltip(activeTooltip);
            }
            activeTooltip = tooltip;
            tooltip.style.display = 'block';
            updatePosition(target, tooltip, position);
        };

        const hide = () => {
            if (activeTooltip === tooltip) {
                activeTooltip = null;
            }
            tooltip.style.display = 'none';
        };

        if (trigger === 'hover') {
            let showTimeout, hideTimeout;
            target.addEventListener('mouseenter', () => {
                clearTimeout(hideTimeout);
                showTimeout = setTimeout(show, delay);
            });
            target.addEventListener('mouseleave', () => {
                clearTimeout(showTimeout);
                hideTimeout = setTimeout(hide, 100);
            });
        } else if (trigger === 'click') {
            target.addEventListener('click', (e) => {
                e.stopPropagation();
                if (tooltip.style.display === 'none') {
                    show();
                } else {
                    hide();
                }
            });
        } else if (trigger === 'focus') {
            target.addEventListener('focus', show);
            target.addEventListener('blur', hide);
        }

        tooltips.set(target, { tooltip, show, hide });
    }

    function updatePosition(target, tooltip, position) {
        const targetRect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        let top, left;

        if (position === 'auto') {
            position = getAutoPosition(targetRect, tooltipRect);
            tooltip.className = 'fw-tooltip fw-tooltip-' + position;
        }

        switch (position) {
            case 'top':
                top = targetRect.top - tooltipRect.height - 8;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = targetRect.bottom + 8;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.left - tooltipRect.width - 8;
                break;
            case 'right':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.right + 8;
                break;
        }

        tooltip.style.top = Math.max(8, top) + 'px';
        tooltip.style.left = Math.max(8, Math.min(left, window.innerWidth - tooltipRect.width - 8)) + 'px';
    }

    function getAutoPosition(targetRect, tooltipRect) {
        const space = {
            top: targetRect.top,
            bottom: window.innerHeight - targetRect.bottom,
            left: targetRect.left,
            right: window.innerWidth - targetRect.right
        };

        if (space.bottom >= tooltipRect.height + 8) return 'bottom';
        if (space.top >= tooltipRect.height + 8) return 'top';
        if (space.right >= tooltipRect.width + 8) return 'right';
        if (space.left >= tooltipRect.width + 8) return 'left';
        return 'bottom';
    }

    function hideTooltip(tooltip) {
        if (tooltip) {
            tooltip.style.display = 'none';
        }
    }

    document.addEventListener('fw:event:showTooltip', (e) => {
        const { target, content, ...options } = e.detail;
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            const existing = tooltips.get(element);
            if (existing) {
                existing.tooltip.remove();
                tooltips.delete(element);
            }
            createTooltip(element, content, options);
        }
    });

    document.addEventListener('fw:event:hideTooltip', (e) => {
        const { target } = e.detail || {};
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            const existing = tooltips.get(element);
            if (existing) {
                existing.hide();
                existing.tooltip.remove();
                tooltips.delete(element);
            }
        } else if (activeTooltip) {
            hideTooltip(activeTooltip);
        }
    });

    document.addEventListener('click', (e) => {
        if (activeTooltip && !activeTooltip.contains(e.target)) {
            const target = Array.from(tooltips.keys()).find(t => t.contains(e.target));
            if (!target) {
                hideTooltip(activeTooltip);
            }
        }
    });
})();
</script>
