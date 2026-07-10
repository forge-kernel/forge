# Sprinkle

> high power behaviors for HTML

A set of HTML attributes that upgrade native form controls and layout structures with single attributes. Zero JavaScript boilerplate. Zero dependencies. No build step required.

[~21KB CSS](src/docs/sprinkle.css) · [~34KB JS](src/docs/sprinkle.js) · [Live Docs](https://forge-kernel.github.io/sprinkle/) · [Demo Dashboard](src/docs/examples/dashboard.html)

---

## Quick Start

```html
<link rel="stylesheet" href="path/to/sprinkle.css" />
<script src="path/to/sprinkle.js" defer></script>
```

It works with any HTML — no framework, no bundler, no CLI.

```html
<!-- A tooltip -->
<button tooltip="This cannot be undone">Delete</button>

<!-- A searchable select -->
<select combo-box searchable>
  <option value="apple">Apple</option>
  <option value="banana">Banana</option>
</select>

<!-- An auto-growing textarea -->
<textarea autosize></textarea>

<!-- A slide-in drawer -->
<button command-for="help" command="show-modal">Open Help</button>
<dialog drawer="right" id="help">
  <p>Help content here.</p>
</dialog>
```

---

## What this is

- HTML attributes that add UX polish (tooltips, dropdowns, modals, character counters, etc.)
- Graceful degradation — every directive works (or does nothing) when JavaScript is off
- Plain CSS for purely visual directives (avatar, breadcrumb, tooltip positioning, sidebar layout)
- Minimal vanilla JavaScript for interactive directives
- A `<script defer>` and `<link>` — nothing more

## What this is not

- Not a UI component library — no prebuilt widgets, no JavaScript framework
- Not a replacement for HTML/CSS — you write the markup and styles, we provide the enhancement layer
- Not JavaScript-first — CSS handles everything it can (avatar, breadcrumb, drawer, modal, dropdown layout)
- Not a polyfill library — relies on modern browser APIs (`MutationObserver`, `IntersectionObserver`, `<dialog>`, `CSS @starting-style`)
- Not mobile-specific — responsive by nature, but not a mobile framework

## Size

| File | Minified | Minified + gzipped |
| --- | --- | --- |
| `sprinkle.css` | ~21 KB | ~5 KB |
| `sprinkle.js` | ~34 KB | ~10 KB |

Zero external dependencies.

---

## Directives

### Input & textarea

| Attribute | On | Behavior |
|---|---|---|
| `autosize` | `<textarea>` | Auto-grows height as content is typed |
| `auto-width` | `<input>`, `<textarea>` | Width fills parent with `box-sizing: border-box` |
| `clearable` | `<input>` | Shows an × button to clear the value |
| `character-count` | `<input>`, `<textarea>` | Live `n / max` counter using `aria-live="polite"`; `maxlength` required |
| `auto-select` | `<input>` | Selects all content on focus (readonly inputs) |
| `enter-submit` | `<textarea>` | Ctrl+Enter / Cmd+Enter submits parent form |
| `drop-zone` | `<label drop-zone>` wrapping `<input type="file">` | Drag-and-drop file upload zone with click-to-browse, image preview, file removal, multiple support, and `aria-live` announcements |
| `leading` | `<input>` | Inline SVG icon before the input value |
| `suffix` | `<input>` | Inline SVG icon after the input value; on `type="password"` toggles visibility; on `type="search"` clears |
| `prefix` | `<input type="url">` | Prepends `https://` (or `https://{val}.`) — only on typing, not deletion |
| `combo-box` | `<select>` | Custom select replacement with search, keyboard navigation, categories, multi-select via `multiple`, avatars via `<option data-avatar>`, and aria-combobox/listbox/option pattern. Works inside `<details dropdown>` |

### Validation

| Attribute | On | Behavior |
|---|---|---|
| `error-message` | any validated element | Custom styled validation messages; per-validity overrides via `error-message-required`, `error-message-minlength`, etc. Submits via `novalidate` to suppress native bubbles |
| `allowed-domains` | `<input type="email\|url">` | Restricts to a comma-separated domain list; custom message via `error-message-allowed-domains`; integrates with error display system |
| `mask` | `<input type="tel\|text">` | Digit formatting: `0` in mask = digit placeholder; separators auto-inserted; bare `<input type="tel">` defaults to `(000) 000-0000` |

### Date / time

| Attribute | On | Behavior |
|---|---|---|
| `no-past` | `<input type="date\|datetime-local">` | Sets `min` to today; clears past values on change |
| `no-future` | `<input type="date\|datetime-local">` | Sets `max` to today; clears future values on change |
| `disable-days` | `<input type="date\|datetime-local">` | Blocks specific days: `weekends`, `mon,tue,...`, or list of `YYYY-MM-DD`; re-validates on change |
| `business-hours` | `<input type="datetime-local">` | Snaps time to nearest boundary if outside configured range (e.g. `09:00-18:00`) |
| `date-range` | `<input type="date\|datetime-local">` | Pairs start/end inputs via `data-range-type="start\|end"`; chained picker, delta preservation |
| `date-input` | `<input type="date\|datetime-local">` | Cross-browser visual standardization: consistent font, border, focus ring; retains native calendar picker |

### Dialog

| Attribute | On | Behavior |
|---|---|---|
| `drawer` | `<dialog>` | Sliding side panel: `left` (default), `right`, `top`, `bottom`; `@starting-style` entry animation |
| `modal` | `<dialog>` | Centered popup with scale+fade entry; size variants: `sm`, default, `lg`; `.sprinkle-modal-header/body/footer` structure classes; `.sprinkle-modal-close` button style |
| `command-for` / `command` | `<button>` | Native Invoker Commands API: `command-for="id" command="show-modal\|close"` — no JavaScript needed to open/close dialogs |

### Details / disclosure

| Attribute | On | Behavior |
|---|---|---|
| `accordion` | `<details>` | Animated open/close with ▶ rotation; same `accordion="group"` = exclusive (one open at a time) |
| `dropdown` | `<details>` | Floating menu panel below `<summary>`; left-aligned by default, `dropdown="right"` for right-alignment; fade+slide transition; combine with `close-outside` to close on outside click |
| `close-outside` | `<details>`, `<dialog>` | Closes the element when clicking outside |
| `open-group="g"` | `<button>` | Opens all `<details accordion="g">` |
| `close-group="g"` | `<button>` | Closes all `<details accordion="g">` |

### Visual

| Attribute | On | Behavior |
|---|---|---|
| `sticky` | any element | `position: sticky` with `.sprinkle-stuck` class + shadow when stuck |
| `zoomable` | `<img>` | Click opens a fullscreen overlay with the image; click overlay to close |
| `copy` / `copy="#id"` | any element | Copies content to clipboard: `.value` for inputs, `.textContent` for others; shows "Copied!" indicator |
| `loading` | `<button>` | Disables button and shows a spinner on click/submit within `<form>` |
| `switch` | `<input type="checkbox">` | Pill switch with `role="switch"` and `aria-checked` replacing the native checkbox |
| `truncate="N"` | any element | Clamps text to N lines with "Show more" / "Show less" toggle; `aria-expanded` |
| `tooltip` | any element | Fade-in tooltip using `attr(tooltip)` as content; auto-positions to available space (top, bottom, left, right); auto-removes native `title` |
| `avatar` | `<img>` | Circular crop with `object-fit: cover`; three size tiers: `sm` (24px), default (40px), `lg` (64px) |
| `breadcrumb` | `<ul>` | Flex row with `/` separators; last item auto-bold |
| `nav` | `<ul>` | Vertical (default) or horizontal (`nav="horizontal"`) navigation list with `aria-current="page"` on active links; collapsible groups via `<details nav-group>` and separators via `<hr nav-sep>` |
| `card` | `<fieldset>` | Visual card with border, radius, shadow; optional `<legend>` title, `<header>`/`<main>`/`<footer>` sections; `href` attribute makes the card clickable |
| `count-up` | any element, `<progress>` | Animates from `start` (default 0) to the element's text value using `IntersectionObserver` with ease-out easing; on `<progress>` animates the `value` attribute. Attributes: `duration` (ms), `start` |
| `shell` | any element | Grid layout container. Use `sidebar="left\|right\|top\|bottom"` on child `<aside>` and `content` on child `<article>` |

### Other

| Attribute | On | Behavior |
|---|---|---|
| `confirm-leave` | `<form>` | Warns before navigating away when the form has unsaved changes |
| `otp` | `<input>` | Renders N digit boxes (N = `max` value); single-field submission via hidden host input; paste, keyboard nav, auto-advance |
| `theme-toggle` | any element | Toggles `data-sprinkle-theme` between `light`/`dark` on click; persists to `localStorage` |
| `enhance` | `<form>` | Ajax form submission with loading states, JSON response handling, and server-side error display |

---

## Examples

### Input enhancements

```html
<textarea autosize></textarea>

<input clearable placeholder="Search…" />
<input leading="search" suffix="close" placeholder="Search…" />
<input type="password" suffix="eye" />

<textarea character-count maxlength="500"></textarea>

<input value="https://example.com/share/abc" auto-select readonly />
<textarea enter-submit placeholder="Ctrl+Enter to send"></textarea>

<input type="url" prefix placeholder="your-site.com" />
<input type="tel" mask placeholder="(000) 000-0000" />
<input type="text" mask="00/00/0000" placeholder="DD/MM/YYYY" />

<label drop-zone>
  <span>Drag files here or click to browse</span>
  <input type="file" name="files" multiple accept="image/*" />
</label>

<label><input type="checkbox" switch /> Notifications</label>
```

### Forms & validation

```html
<input error-message placeholder="Required field" required />
<input error-message type="email" required />

<input type="email" allowed-domains="example.com,upper.do" />

<input type="text" otp max="6" />

<form enhance method="post" action="/api/submit">
  <input name="email" required />
  <button loading>Submit</button>
</form>

<form confirm-leave>
  <textarea></textarea>
  <button loading>Save</button>
</form>
```

### Dialogs & drawers

```html
<button command-for="help" command="show-modal">Open Help</button>
<dialog drawer="right" id="help">
  <p>Help content here.</p>
</dialog>

<dialog modal id="confirm">
  <div class="sprinkle-modal-header">
    Confirm
    <button class="sprinkle-modal-close" command-for="confirm" command="close">&times;</button>
  </div>
  <div class="sprinkle-modal-body">Are you sure?</div>
  <div class="sprinkle-modal-footer">
    <button command-for="confirm" command="close">Cancel</button>
    <button>Confirm</button>
  </div>
</dialog>
<button command-for="confirm" command="show-modal">Delete</button>
```

### Disclosure widgets

```html
<details accordion="faq">
  <summary>Question</summary>
  <p>Answer.</p>
</details>
<button open-group="faq">Expand All</button>
<button close-group="faq">Collapse All</button>

<details dropdown close-outside>
  <summary>My Account</summary>
  <ul>
    <li><a href="/profile">Profile</a></li>
    <li><hr /></li>
    <li><a href="/logout">Logout</a></li>
  </ul>
</details>
```

### Navigation & layout

```html
<ul nav>
  <li><a href="/">Home</a></li>
  <li><a href="/about" active>About</a></li>
  <li><a href="/contact">Contact</a></li>
</ul>

<ul nav="horizontal">
  <li><a href="/">Tab 1</a></li>
  <li><a href="/2">Tab 2</a></li>
</ul>

<ul breadcrumb>
  <li><a href="/">Home</a></li>
  <li><a href="/blog">Blog</a></li>
  <li>Current Page</li>
</ul>

<div shell>
  <aside sidebar="left">Sidebar</aside>
  <article content>Main content</article>
</div>
```

### Cards & data

```html
<fieldset card>
  <legend>User</legend>
  <p>Card content here.</p>
</fieldset>

<span count-up duration="3000">7342</span>
<progress count-up value="75" max="100"></progress>
```

### Utilities

```html
<button copy>Copy my label</button>
<button copy="#email">Copy Email</button>
<span id="email">hello@example.com</span>

<button tooltip="This cannot be undone">Delete</button>

<img src="photo.jpg" zoomable alt="Photo" />
<p truncate="10">Long text clamped to 10 lines.</p>

<img src="user.jpg" avatar />
<img src="user.jpg" avatar="sm" />
<img src="user.jpg" avatar="lg" />

<button theme-toggle>Toggle theme</button>
<nav sticky>
  <a href="/">Home</a>
</nav>

<input type="date" no-past />
<input type="date" disable-days="weekends" />
<input type="datetime-local" business-hours="09:00-18:00" />
```

### Combo-box

```html
<select combo-box>
  <option value="">Select a fruit…</option>
  <option value="apple">Apple</option>
  <option value="banana">Banana</option>
</select>

<select combo-box multiple>
  <option value="1">One</option>
  <option value="2">Two</option>
  <option value="3">Three</option>
</select>

<details dropdown close-outside>
  <summary>Filter</summary>
  <select combo-box multiple>
    <option value="1">One</option>
    <option value="2">Two</option>
  </select>
</details>
```

---

## Icons

Place SVGs in `/assets/svg/{name}.svg`. Reference by name (no extension):

```html
<input leading="search" placeholder="Search…" />
<input type="password" leading="lock" suffix="eye" />
```

- **Visibility toggle**: `suffix` on `type="password"` swaps between `{name}.svg` and `{name}-off.svg` on click.
- **Clear button**: `suffix="close"` or `suffix="clear"` clears the input on click.
- **Custom path**: Override the SVG directory with a `<meta>` tag:

```html
<meta name="sprinkle-svg-path" content="/custom/path/to/icons" />
```

When `suffix` is present, `clearable` is ignored.

---

## Accessibility

- `aria-live="polite"` on character counters
- `aria-expanded` on truncate toggles and accordion summaries
- `role="switch"` and `aria-checked` on switch toggles
- `role="alert"` and `aria-describedby` on validation errors
- `role="tooltip"` on tooltip elements
- `focus-visible` trigger for tooltip visibility
- Keyboard navigation on OTP (arrow keys, backspace, auto-advance)
- Native `<details>` / `<summary>` keyboard behavior preserved in dropdown mode
- WAI-ARIA combobox pattern on `[combo-box]`: `role="combobox"` on trigger, `role="listbox"` on options container, `role="option"` on items, `aria-expanded`, `aria-haspopup`, `aria-multiselectable`, `aria-selected`, `aria-posinset`, `aria-setsize`
- `aria-current="page"` automatically set on active links in `ul[nav]`
- `aria-live="polite"` announcements on `<label drop-zone>` for file add/remove feedback
- `aria-label` on `drop-zone` remove buttons

---

## Customizing

Override any `.sprinkle-*` class in your own stylesheet:

```css
.sprinkle-stuck {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
}
.sprinkle-loading {
  opacity: 0.5;
}
.sprinkle-modal-header {
  background: #f8f8f8;
}
```

## Extending

Register custom directives via the global `ForgeSprinkle` namespace:

```js
ForgeSprinkle.register("my-attr", function (el) {
  el.style.border = "2px solid gold";
});
```

Register before `DOMContentLoaded`. The handler runs on matching elements at init and on dynamically added elements via `MutationObserver`.

---

## Browser support

Requires `MutationObserver`, `IntersectionObserver`, and ES5. The `<dialog>` element and `@starting-style` are required for drawer/modal directives. `command-for` / `command` (Invoker Commands) is Chromium-only; a fallback `onclick` handler can be used for other browsers.

Tested in the latest versions of Chrome, Firefox, and Safari.

---

[Documentation](src/docs/docs.html) · [Demo Dashboard](src/docs/examples/dashboard.html) · [GitHub](https://github.com/forge-kernel/sprinkle) · MIT License
