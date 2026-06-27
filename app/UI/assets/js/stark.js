const Stark = (() => {
  const directives = {};
  const components = {};

  function registerDirective(name, factory) {
    directives[name] = factory;
  }

  function define(name, factory) {
    components[name] = factory;
  }

  function init(root) {
    const target = root || document;
    if (!target) return;
    const scopeNodes = target.querySelectorAll('[s-scope]');
    for (let i = 0; i < scopeNodes.length; i++) {
      createComponent(scopeNodes[i]);
    }
  }

  function createComponent(root) {
    const scopeSource = root.getAttribute('s-scope') || '{}';
    const key = scopeSource.trim();
    let rawState;
    const factory = components[key];
    if (factory) {
      rawState = factory(root) || {};
    } else {
      try {
        rawState = new Function('return (' + scopeSource + ')').call(root);
      } catch (e) {
        rawState = {};
      }
    }
    if (!rawState || typeof rawState !== 'object') {
      rawState = {};
    }
    const component = {
      root,
      state: null,
      bindings: [],
      scheduled: false
    };
    component.state = createReactiveState(rawState, component);
    setupBindings(component);
    runComponentBindings(component);
  }

  function setupBindings(component) {
    const root = component.root;
    const selector = '[s-text],[s-show],[s-on],[s-model],[s-scope]';
    const nodes = root.querySelectorAll(selector);
    for (let i = 0; i < nodes.length; i++) {
      const el = nodes[i];
      const owner = el.closest('[s-scope]');
      if (owner !== root) {
        continue;
      }
      applyDirectives(component, el);
    }
    applyDirectives(component, root);
  }

  function applyDirectives(component, el) {
    const attrs = el.attributes;
    if (!attrs) return;
    for (let i = 0; i < attrs.length; i++) {
      const attr = attrs[i];
      const name = attr.name;
      if (name.length < 3) continue;
      if (name[0] !== 's' || name[1] !== '-') continue;
      const key = name.slice(2);
      const factory = directives[key];
      if (!factory) continue;
      factory(component, el, attr.value);
    }
  }

  function createReactiveState(raw, component) {
    let proxy = null;
    const handler = {
      get(target, prop, receiver) {
        if (prop === '__isStark') return true;
        const value = Reflect.get(target, prop, receiver);
        if (typeof value === 'function') {
          return value.bind(proxy);
        }
        return value;
      },
      set(target, prop, value, receiver) {
        const result = Reflect.set(target, prop, value, receiver);
        scheduleComponentUpdate(component);
        return result;
      },
      deleteProperty(target, prop) {
        const result = Reflect.deleteProperty(target, prop);
        scheduleComponentUpdate(component);
        return result;
      }
    };
    proxy = new Proxy(raw, handler);
    return proxy;
  }

  function schedule(fn) {
    if (typeof queueMicrotask === 'function') {
      queueMicrotask(fn);
    } else {
      Promise.resolve().then(fn);
    }
  }

  function scheduleComponentUpdate(component) {
    if (component.scheduled) return;
    component.scheduled = true;
    schedule(function () {
      component.scheduled = false;
      runComponentBindings(component);
    });
  }

  function runComponentBindings(component) {
    const list = component.bindings;
    for (let i = 0; i < list.length; i++) {
      const binding = list[i];
      binding.update();
    }
  }

  function createExpressionFunction(source) {
    try {
      return new Function('state', 'el', 'event', 'with(state){ return (' + source + ') }');
    } catch (e) {
      return function () {
        return undefined;
      };
    }
  }

  function createStatementFunction(source) {
    try {
      return new Function('state', 'el', 'event', 'with(state){ ' + source + ' }');
    } catch (e) {
      return function () {};
    }
  }

  registerDirective('text', function (component, el, value) {
    const expr = createExpressionFunction(value);
    const binding = {
      type: 'text',
      el,
      update() {
        let result;
        try {
          result = expr(component.state, el, null);
        } catch (e) {
          result = '';
        }
        if (result === undefined || result === null) {
          el.textContent = '';
        } else {
          el.textContent = String(result);
        }
      }
    };
    component.bindings.push(binding);
  });

  registerDirective('show', function (component, el, value) {
    const expr = createExpressionFunction(value);
    const originalDisplay = el.style.display || '';
    const binding = {
      type: 'show',
      el,
      update() {
        let result;
        try {
          result = expr(component.state, el, null);
        } catch (e) {
          result = false;
        }
        if (result) {
          el.style.display = originalDisplay;
        } else {
          el.style.display = 'none';
        }
      }
    };
    component.bindings.push(binding);
  });

  registerDirective('on', function (component, el, value) {
    const trimmed = value || '';
    let eventName = '';
    let handlerSource = '';
    const index = trimmed.indexOf(':');
    if (index === -1) {
      eventName = trimmed.trim();
      handlerSource = '';
    } else {
      eventName = trimmed.slice(0, index).trim();
      handlerSource = trimmed.slice(index + 1).trim();
    }
    if (!eventName) return;
    const handler = createStatementFunction(handlerSource);
    const listener = function (event) {
      handler(component.state, el, event);
    };
    el.addEventListener(eventName, listener);
  });

  registerDirective('model', function (component, el, value) {
    const key = (value || '').trim();
    if (!key) return;
    const isCheckbox = el.type === 'checkbox';
    const isRadio = el.type === 'radio';
    const isSelect = el.tagName === 'SELECT';

    function readState() {
      return component.state[key];
    }

    function writeState(next) {
      component.state[key] = next;
    }

    const binding = {
      type: 'model',
      el,
      update() {
        const current = readState();
        if (isCheckbox) {
          el.checked = Boolean(current);
          return;
        }
        if (isRadio) {
          el.checked = String(current) === String(el.value);
          return;
        }
        if (isSelect) {
          if (current === undefined || current === null) {
            el.value = '';
          } else {
            el.value = String(current);
          }
          return;
        }
        if (current === undefined || current === null) {
          el.value = '';
        } else {
          el.value = String(current);
        }
      }
    };
    component.bindings.push(binding);

    function domToState() {
      if (isCheckbox) {
        writeState(el.checked);
        return;
      }
      if (isRadio) {
        if (el.checked) {
          writeState(el.value);
        }
        return;
      }
      writeState(el.value);
    }

    const eventName = isCheckbox || isRadio || isSelect ? 'change' : 'input';
    el.addEventListener(eventName, domToState);
  });

  return {
    init,
    registerDirective,
    define
  };
})();

if (typeof window !== 'undefined') {
  window.Stark = Stark;
  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () {
        Stark.init();
      });
    } else {
      Stark.init();
    }
  }
}
