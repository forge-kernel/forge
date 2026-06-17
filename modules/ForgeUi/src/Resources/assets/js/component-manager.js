(function() {
    'use strict';

    const ComponentManager = {
        components: new Map(),
        eventListeners: new Map(),

        register(componentId, component) {
            this.components.set(componentId, component);
        },

        unregister(componentId) {
            const component = this.components.get(componentId);
            if (component && component.destroy) {
                component.destroy();
            }
            this.components.delete(componentId);
        },

        get(componentId) {
            return this.components.get(componentId);
        },

        delegate(eventType, selector, handler) {
            if (!this.eventListeners.has(eventType)) {
                const listener = (e) => {
                    const target = e.target.closest(selector);
                    if (target) {
                        handler.call(target, e);
                    }
                };
                document.addEventListener(eventType, listener, true);
                this.eventListeners.set(eventType, listener);
            }
        },

        cleanup() {
            this.components.forEach((component, id) => {
                if (component.destroy) {
                    component.destroy();
                }
            });
            this.components.clear();
            this.eventListeners.forEach((listener, eventType) => {
                document.removeEventListener(eventType, listener, true);
            });
            this.eventListeners.clear();
        },

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        throttle(func, limit) {
            let inThrottle;
            return function executedFunction(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };

    window.FwComponentManager = ComponentManager;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ComponentManager.initialize?.();
        });
    } else {
        ComponentManager.initialize?.();
    }
})();
