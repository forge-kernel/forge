(() => {
	const POLL_MS       = 1300;
	const CSS_FILE      = 'forgetailwind.css';
	const MAX_BACKOFF_MS = 30000;

	let lastMtime   = 0;
	let lastHash    = '';
	let controller  = null;
	let checking    = false;
	let failCount   = 0;
	
	const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
	
	function serializeForms() {
		const data = {};
		qsa('input, select, textarea').forEach(el => {
			if (!el.name) return;
			const key = el.name;
			
			if (el.type === 'checkbox' || el.type === 'radio') {
				data[key] = { type: el.type, checked: el.checked };
			} else {
				data[key] = { type: el.type, value: el.value };
			}
		});
		return data;
	}
	
	function deserializeForms(data) {
		qsa('input, select, textarea').forEach(el => {
			if (!el.name) return;
			const key = el.name;
			const savedState = data[key];
			
			if (savedState) {
				if (savedState.type === 'checkbox' || savedState.type === 'radio') {
					el.checked = savedState.checked; 
				} else {
					el.value = savedState.value;
				}
			}
		});
	}

	function reloadCSS() {
		qsa(`link[rel="stylesheet"]`)
			.filter(l => l.href.includes(CSS_FILE))
			.forEach(oldLink => {
				const newLink       = oldLink.cloneNode();
				newLink.href        = oldLink.href.split('?')[0] + '?v=' + Date.now(); 
				newLink.onload      = () => oldLink.remove();
				newLink.onerror     = () => oldLink.remove();
				oldLink.parentNode.insertBefore(newLink, oldLink.nextSibling);
			});
	}
	
	function morph(current, updated) {
		if (current.nodeType !== updated.nodeType || current.tagName !== updated.tagName) {
			current.parentNode.replaceChild(updated.cloneNode(true), current);
			return;
		}
	
		if (current.nodeType === Node.TEXT_NODE) {
			if (current.nodeValue !== updated.nodeValue) {
				current.nodeValue = updated.nodeValue;
			}
			return;
		}
		
		const updatedAttrs = Array.from(updated.attributes);
		Array.from(current.attributes).forEach(attr => {
			if (!updated.hasAttribute(attr.name)) {
				current.removeAttribute(attr.name);
			}
		});
		
		updatedAttrs.forEach(attr => {
			if (current.getAttribute(attr.name) !== attr.value) {
				current.setAttribute(attr.name, attr.value);
			}
		});
	
		const currentChildren = Array.from(current.childNodes);
		const updatedChildren = Array.from(updated.childNodes);
		const minLength = Math.min(currentChildren.length, updatedChildren.length);

		for (let i = 0; i < minLength; i++) {
			morph(currentChildren[i], updatedChildren[i]);
		}

		for (let i = minLength; i < currentChildren.length; i++) {
			current.removeChild(currentChildren[i]);
		}
	
		for (let i = minLength; i < updatedChildren.length; i++) {
			current.appendChild(updatedChildren[i].cloneNode(true));
		}
	}

	async function surgicalReload() {
		const html = await fetch(location.href, { cache: 'no-store' }).then(r => r.text());
		const newDoc = new DOMParser().parseFromString(html, 'text/html');
		const scrollPos = { x: scrollX, y: scrollY };
		const activeElement = document.activeElement;
		const formData = serializeForms();
		
		const contentSelectors = ['main', '#content', '.container', '.app-content'];
		
		contentSelectors.forEach(selector => {
			const current = document.querySelector(selector);
			const updated = newDoc.querySelector(selector);
			if (current && updated) {
				if (current.outerHTML !== updated.outerHTML) {
					console.log(`[HMR] Updating content: ${selector}`);
					morph(current, updated);  
				}
			}
		});
		
		deserializeForms(formData);
		scrollTo(scrollPos.x, scrollPos.y);
		
		if (activeElement && document.contains(activeElement) && activeElement.focus) {
			activeElement.focus();
		}
		
		reloadCSS();
	}
	
	async function check() {
		if (document.hidden) { schedule(); return; } 
		if (checking) return;
		checking = true;
		if (controller) controller.abort();
		controller = new AbortController();

		try {
			const res = await fetch('/tailwind-watch.php?_=' + Date.now(), {
				signal: controller.signal,
				cache: 'no-store'
			});
			if (!res.ok) throw new Error(res.status);
			const data = await res.json();
			if (!data || data.mtime === undefined) { checking = false; schedule(); return; }

			if (data.mtime > lastMtime || data.hash !== lastHash) {
				lastMtime = data.mtime;
				lastHash  = data.hash || '';
				failCount = 0;

				if (data.type === 'css') {
					console.log('[HMR] CSS change – hot-swapping stylesheet');
					reloadCSS();
				} else {
					console.log('[HMR] HTML/asset change – surgical update');
					await surgicalReload().catch((e) => {
						console.error('[HMR] Surgical reload failed, forcing full page reload:', e);
						location.reload();
					});
				}
			}
		} catch (e) {
			if (e.name !== 'AbortError') { 
				console.warn('[HMR] poll error', e.message);
				failCount++;
			}
		} finally {
			checking = false;
			schedule();
		}
	}

	function schedule() {
		const delay = Math.min(POLL_MS * Math.pow(1.5, failCount), MAX_BACKOFF_MS);
		setTimeout(check, delay);
	}

	schedule();
})();