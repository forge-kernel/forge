# ForgeSprinkle

Optional HTML attribute enhancements. Each directive maps an HTML attribute to a small piece of behavior or styling. No JavaScript framework, no dependencies, no build step.

## What this is

- A set of HTML attributes that add UX polish (tooltips, dropdowns, modals, character counters, etc.)
- Graceful degradation: every directive works (or does nothing) when JavaScript is off
- Plain CSS for purely visual directives (avatar, breadcrumb, tooltip positioning)
- Minimal vanilla JavaScript for interactive directives
- A `<script defer>` and `<link>` injected before `</body>` and `</head>`

## What this is not

- Not a UI component library — no prebuilt widgets, no JavaScript framework
- Not a replacement for HTML/CSS — you write the markup and styles, we provide the enhancement layer
- Not JavaScript-first — CSS handles everything it can (avatar, breadcrumb, drawer, modal, dropdown layout)
- Not a polyfill library — relies on modern browser APIs (`MutationObserver`, `IntersectionObserver`, `dialog`, `CSS @starting-style`)
- Not mobile-specific — responsive by nature of using the viewport, but not a mobile framework

## Size

~11KB CSS (minified), ~19KB JS (minified, compressed). Zero external dependencies.

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
| `file-name` | `<input type="file">` | Shows selected filename(s) after file picker |
| `leading` | `<input>` | Inline SVG icon before the input value |
| `suffix` | `<input>` | Inline SVG icon after the input value; on `type="password"` toggles visibility; on `type="search"` clears |
| `prefix` | `<input type="url">` | Prepends `https://` (or `https://{val}.`) — only on typing, not deletion |

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
| `command-for` / `command` | `<button>` | Native Chrome/Edge Invoker Commands API: `command-for="id" command="show-modal\|close"` — no JavaScript needed to open/close dialogs |

### Details / disclosure

| Attribute | On | Behavior |
|---|---|---|
| `accordion` | `<details>` | Animated open/close with ▶ rotation; same `accordion="group"` = exclusive (one open at a time) |
| `dropdown` | `<details>` | Floating menu panel below `<summary>`; left-aligned by default, `dropdown="right"` for right-alignment; fade+slide transition; combine with `close-outside` to close on outside click |
| `close-outside` | `<details>` | Closes the `<details>` (removes `open`) when clicking outside the element |
| `open-group="g"` | `<button>` | Opens all `<details accordion="g">` |
| `close-group="g"` | `<button>` | Closes all `<details accordion="g">` |

### Visual

| Attribute | On | Behavior |
|---|---|---|
| `sticky` | any element | `position: sticky; top: 0` with `.sprinkle-stuck` class + shadow when stuck |
| `zoomable` | `<img>` | Click opens a fullscreen overlay with the image; click overlay to close |
| `copy` / `copy="#id"` | any element | Copies content to clipboard: `.value` for inputs, `.textContent` for others; shows "Copied!" indicator |
| `loading` | `<button>` | Disables button and shows a spinner on click/submit within `<form>` |
| `switch` | `<input type="checkbox">` | Pill switch with `role="switch"` and `aria-checked` replacing the native checkbox |
| `truncate="N"` | any element | Clamps text to N lines with "Show more" / "Show less" toggle; `aria-expanded` |
| `tooltip` | any element | Fade-in tooltip using `attr(tooltip)` as content; auto-positions to available space (top, bottom, left, right); auto-removes native `title` |
| `avatar` | `<img>` | Circular crop with `object-fit: cover`; three size tiers: `sm` (24px), default (40px), `lg` (64px) |
| `breadcrumb` | `<ul>` | Flex row with `/` separators; last item auto-bold |

### Other

| Attribute | On | Behavior |
|---|---|---|
| `confirm-leave` | `<form>` | Warns before navigating away when the form has unsaved changes |
| `otp` | `<input>` | Renders N digit boxes (N = `max` value); single-field submission via hidden host input; paste, keyboard nav, auto-advance |
| `character-count` | `<input>`, `<textarea>` | Live `n / max` counter using `aria-live="polite"`; `maxlength` required |

## Usage

```html
<textarea autosize></textarea>

<nav sticky>
  <a href="/">Home</a>
</nav>

<button copy>Copy my label</button>
<button copy="#email">Copy Email</button>
<span id="email">hello@example.com</span>

<img src="photo.jpg" zoomable alt="Photo" />

<form confirm-leave>
  <textarea></textarea>
  <button loading>Save</button>
</form>

<input clearable placeholder="Search…" />
<input leading="search" suffix="close" placeholder="Search…" />
<input type="password" suffix="eye" />

<textarea character-count maxlength="500"></textarea>

<input value="https://example.com/share/abc" auto-select readonly />

<p truncate="10">Long text clamped to 10 lines.</p>

<textarea enter-submit placeholder="Ctrl+Enter to send"></textarea>

<input type="file" file-name multiple />

<label><input type="checkbox" switch /> Notifications</label>

<input type="url" prefix placeholder="your-site.com" />
<input type="url" prefix="upper.do" />

<input type="tel" mask placeholder="(000) 000-0000" />
<input type="text" mask="00/00/0000" placeholder="DD/MM/YYYY" />

<input type="email" allowed-domains="example.com,upper.do" placeholder="user@example.com" />
<input type="url" allowed-domains="example.com" placeholder="https://example.com" />

<input error-message placeholder="Required field" required />
<input error-message type="email" required />

<input type="text" otp max="6" />

<input type="date" no-past />
<input type="date" disable-days="weekends" />
<input type="datetime-local" business-hours="09:00-18:00" />

<input type="date" date-range="trip" data-range-type="start" name="trip_start" />
<input type="date" date-range="trip" data-range-type="end" name="trip_end" />

<details accordion="faq">
  <summary>Question</summary>
  <p>Answer.</p>
</details>

<details dropdown close-outside>
  <summary>My Account</summary>
  <ul>
    <li><a href="/profile">Profile</a></li>
    <li><a href="/settings">Settings</a></li>
    <hr />
    <li><a href="/logout">Logout</a></li>
  </ul>
</details>

<details dropdown="right" close-outside>
  <summary>☰ Menu</summary>
  <ul>
    <li><a href="/">Home</a></li>
    <li><a href="/about">About</a></li>
  </ul>
</details>

<button open-group="faq">Expand All</button>
<button close-group="faq">Collapse All</button>

<img src="user.jpg" avatar />
<img src="user.jpg" avatar="sm" />
<img src="user.jpg" avatar="lg" />

<button tooltip="This cannot be undone">Delete</button>

<ul breadcrumb>
  <li><a href="/">Home</a></li>
  <li><a href="/blog">Blog</a></li>
  <li>Current Page</li>
</ul>

<dialog drawer="left" id="nav">
  <button command-for="nav" command="close">Close</button>
  <nav><!-- links --></nav>
</dialog>
<button command-for="nav" command="show-modal">Open Menu</button>

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

## Icons

Place SVGs in `/assets/svg/{name}.svg`. Reference by name (no extension):

```html
<input leading="search" placeholder="Search…" />
<input type="password" leading="lock" suffix="eye" />
```

**Visibility toggle**: `suffix` on `type="password"` swaps between `{name}.svg` and `{name}-off.svg` on click.

**Clear button**: `suffix="close"` or `suffix="clear"` clears the input on click.

**Custom path**: Override the SVG directory with a `<meta>` tag:

```html
<meta name="sprinkle-svg-path" content="/custom/path/to/icons">
```

When `suffix` is present, `clearable` is ignored.

## Accessibility

- `aria-live="polite"` on character counters
- `aria-expanded` on truncate toggles and accordion summaries
- `role="switch"` and `aria-checked` on switch toggles
- `role="alert"` and `aria-describedby` on validation errors
- `role="tooltip"` on tooltip elements
- `focus-visible` trigger for tooltip visibility
- Keyboard navigation on OTP (arrow keys, backspace, auto-advance)
- Native `<details>` / `<summary>` keyboard behavior preserved in dropdown mode

## Extending

Custom directives can be added via the global `ForgeSprinkle` namespace:

```js
ForgeSprinkle.register('my-attr', function (el) {
  el.style.border = '2px solid gold'
})
```

Register before `DOMContentLoaded`. The handler runs on matching elements at init and on dynamically added elements via `MutationObserver`.

## Styling

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

## Browser support

Requires `MutationObserver`, `IntersectionObserver`, and ES5. The `dialog` element and `@starting-style` are required for drawer/modal directives. `command-for` / `command` (Invoker Commands) is Chromium-only; a fallback `onclick` handler can be used for other browsers.
