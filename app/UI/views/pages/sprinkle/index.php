<style>
/* ── doc page custom properties (theme-aware) ── */

.doc-page {
  --doc-text: #1a1a1a;
  --doc-text-secondary: #555;
  --doc-text-muted: #888;
  --doc-border: #e0e0e0;
  --doc-demo-bg: #fafafa;
  --doc-demo-border: #e0e0e0;
  --doc-code-bg: #1a1a1a;
  --doc-code-text: #e0e0e0;
  --doc-btn-bg: #fff;
  --doc-btn-border: #ccc;
  --doc-btn-hover: #f0f0f0;
  --doc-meta-code-bg: #f0f0f0;
  --doc-header-border: #e0e0e0;
  --doc-logo-color: var(--doc-text);
}

@media (prefers-color-scheme: dark) {
  .doc-page {
    --doc-text: #e0e0e0;
    --doc-text-secondary: #aaa;
    --doc-text-muted: #777;
    --doc-border: #444;
    --doc-demo-bg: #2a2a2a;
    --doc-demo-border: #444;
    --doc-code-bg: #111;
    --doc-code-text: #ccc;
    --doc-btn-bg: #333;
    --doc-btn-border: #555;
    --doc-btn-hover: #444;
    --doc-meta-code-bg: #444;
    --doc-header-border: #444;
  }
}

[data-sprinkle-theme="dark"] .doc-page {
  --doc-text: #e0e0e0;
  --doc-text-secondary: #aaa;
  --doc-text-muted: #777;
  --doc-border: #444;
  --doc-demo-bg: #2a2a2a;
  --doc-demo-border: #444;
  --doc-code-bg: #111;
  --doc-code-text: #ccc;
  --doc-btn-bg: #333;
  --doc-btn-border: #555;
  --doc-btn-hover: #444;
  --doc-meta-code-bg: #444;
  --doc-header-border: #444;
}

[data-sprinkle-theme="light"] .doc-page {
  --doc-text: #1a1a1a;
  --doc-text-secondary: #555;
  --doc-text-muted: #888;
  --doc-border: #e0e0e0;
  --doc-demo-bg: #fafafa;
  --doc-demo-border: #e0e0e0;
  --doc-code-bg: #1a1a1a;
  --doc-code-text: #e0e0e0;
  --doc-btn-bg: #fff;
  --doc-btn-border: #ccc;
  --doc-btn-hover: #f0f0f0;
  --doc-meta-code-bg: #f0f0f0;
  --doc-header-border: #e0e0e0;
}

body {margin: 0}

/* ── shell ── */

.doc-page[shell] > [sidebar="left"] {
  width: 240px;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  font-size: 0.9rem;
  color: var(--doc-text);
  position: sticky;
  top: 0;
  align-self: start;
  max-height: 100dvh;
  overflow-y: auto;
}

.doc-page[shell] > [content] {
  overflow-y: visible;
}

/* ── page header ── */

.doc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 1.5rem 0.75rem;
  border-bottom: 1px solid var(--doc-header-border);
  margin-bottom: 1.5rem;
}

.doc-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.02em;
  color: var(--doc-logo-color);
}

.doc-header p {
  margin: 0.25rem 0 0;
  color: var(--doc-text-secondary);
  font-size: 0.9rem;
}

/* ── theme toggle in header ── */

.doc-page [theme-toggle] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid var(--doc-btn-border);
  border-radius: 6px;
  background: var(--doc-btn-bg);
  cursor: pointer;
  color: var(--doc-text-secondary);
  flex-shrink: 0;
}

.doc-page [theme-toggle]:hover {
  background: var(--doc-btn-hover);
}

/* ── content body ── */

.doc-content {
  padding: 0 1.5rem 4rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  font-size: 15px;
  line-height: 1.6;
  color: var(--doc-text);
}

.doc-content input,
.doc-content textarea,
.doc-content select {
  font-family: inherit;
  font-size: inherit;
  color: inherit;
}

.doc-content h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 2.5rem 0 0.5rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid var(--doc-border);
  color: var(--doc-text);
}

.doc-content p {
  margin: 0 0 1rem;
  color: var(--doc-text-secondary);
}

/* ── directive block ── */

.doc-directive {
  margin-bottom: 2rem;
}

.doc-directive h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.15rem;
  font-family: "SF Mono", "Fira Code", "Fira Mono", Menlo, Consolas, monospace;
  color: var(--doc-text);
}

.doc-directive h3 code {
  font-size: inherit;
}

.doc-directive .doc-meta {
  font-size: 0.85rem;
  color: var(--doc-text-muted);
  margin: 0 0 0.5rem;
}

.doc-directive .doc-meta code {
  background: var(--doc-meta-code-bg);
  padding: 0 4px;
  border-radius: 3px;
  font-size: 0.85em;
  color: var(--doc-text);
}

.doc-directive .doc-demo {
  margin: 0.5rem 0;
  padding: 0.75rem 1rem;
  border: 1px solid var(--doc-demo-border);
  border-radius: 6px;
  background: var(--doc-demo-bg);
}

.doc-directive .doc-code {
  margin: 0.5rem 0;
  padding: 0.75rem 1rem;
  background: var(--doc-code-bg);
  color: var(--doc-code-text);
  border-radius: 6px;
  font-family: "SF Mono", "Fira Code", "Fira Mono", Menlo, Consolas, monospace;
  font-size: 0.8rem;
  overflow-x: auto;
  white-space: pre;
  tab-size: 2;
}

.doc-directive .doc-code .doc-tag { color: #569cd6; }
.doc-directive .doc-code .doc-attr { color: #9cdcfe; }
.doc-directive .doc-code .doc-val  { color: #ce9178; }
.doc-directive .doc-code .doc-com  { color: #6a9955; }

/* ── separators ── */

.doc-sep {
  height: 1px;
  background: var(--doc-border);
  margin: 2.5rem 0;
}

/* ── inline overrides for demos ── */

.doc-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.doc-stack {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.doc-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: 320px;
}

.doc-form button[type="submit"] {
  padding: 0.375rem 1rem;
  border: 1px solid var(--doc-btn-border);
  border-radius: 4px;
  background: var(--doc-btn-bg);
  cursor: pointer;
  font: inherit;
  color: var(--doc-text);
}

.doc-form button[type="submit"]:hover {
  background: var(--doc-btn-hover);
}

/* ── dialog open buttons ── */

.doc-btn {
  padding: 0.375rem 1rem;
  border: 1px solid var(--doc-btn-border);
  border-radius: 4px;
  background: var(--doc-btn-bg);
  cursor: pointer;
  font: inherit;
  font-size: 0.9rem;
  color: var(--doc-text);
}

.doc-btn:hover {
  background: var(--doc-btn-hover);
}

.doc-btn-danger {
  background: #d32f2f;
  color: #fff;
  border-color: #d32f2f;
}

@media (prefers-color-scheme: dark) {
    body {background: var(--sprinkle-content-bg);}
    input {background: var(--sprinkle-content-bg);}
    textarea {background: var(--sprinkle-content-bg);}
  .doc-btn-danger {
    background: #b71c1c;
    border-color: #b71c1c;
  }
}

[data-sprinkle-theme="dark"] .doc-btn-danger {
  background: #b71c1c;
  border-color: #b71c1c;
}

[data-sprinkle-theme="light"] .doc-btn-danger {
  background: #d32f2f;
  border-color: #d32f2f;
}

/* ── sidebar logo ── */

.doc-sidebar-logo {
  font-weight: 700;
  font-size: 0.95rem;
  padding: 0.75rem 0.75rem 0.5rem;
  color: var(--doc-logo-color);
}
</style>

<div shell class="doc-page">

<aside sidebar="left">
  <div class="doc-sidebar-logo">ForgeSprinkle</div>
  <ul nav>
    <li>
      <details nav-group="input" open>
        <summary>Input</summary>
        <ul>
          <li><a href="#autosize">autosize</a></li>
          <li><a href="#auto-width">auto-width</a></li>
          <li><a href="#clearable">clearable</a></li>
          <li><a href="#character-count">character-count</a></li>
          <li><a href="#auto-select">auto-select</a></li>
          <li><a href="#enter-submit">enter-submit</a></li>
          <li><a href="#file-name">file-name</a></li>
          <li><a href="#leading">leading / suffix</a></li>
          <li><a href="#prefix">prefix</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="validation" open>
        <summary>Validation</summary>
        <ul>
          <li><a href="#error-message">error-message</a></li>
          <li><a href="#allowed-domains">allowed-domains</a></li>
          <li><a href="#mask">mask</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="datetime" open>
        <summary>Date / Time</summary>
        <ul>
          <li><a href="#no-past">no-past / no-future</a></li>
          <li><a href="#disable-days">disable-days</a></li>
          <li><a href="#business-hours">business-hours</a></li>
          <li><a href="#date-range">date-range</a></li>
          <li><a href="#date-input">date-input</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="dialog" open>
        <summary>Dialog</summary>
        <ul>
          <li><a href="#drawer">drawer</a></li>
          <li><a href="#modal">modal</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="layout" open>
        <summary>Layout</summary>
        <ul>
          <li><a href="#shell">shell</a></li>
          <li><a href="#nav">nav</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="disclosure" open>
        <summary>Disclosure</summary>
        <ul>
          <li><a href="#accordion">accordion</a></li>
          <li><a href="#dropdown">dropdown</a></li>
          <li><a href="#close-outside">close-outside</a></li>
          <li><a href="#group-toggle">open-group / close-group</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="visual" open>
        <summary>Visual</summary>
        <ul>
          <li><a href="#sticky">sticky</a></li>
          <li><a href="#zoomable">zoomable</a></li>
          <li><a href="#copy">copy</a></li>
          <li><a href="#loading">loading</a></li>
          <li><a href="#switch">switch</a></li>
          <li><a href="#truncate">truncate</a></li>
          <li><a href="#tooltip">tooltip</a></li>
          <li><a href="#avatar">avatar</a></li>
          <li><a href="#breadcrumb">breadcrumb</a></li>
        </ul>
      </details>
    </li>
    <li>
      <details nav-group="other" open>
        <summary>Other</summary>
        <ul>
          <li><a href="#confirm-leave">confirm-leave</a></li>
          <li><a href="#otp">otp</a></li>
        </ul>
      </details>
    </li>
  </ul>
</aside>

<main content class="doc-content">

<div class="doc-header">
  <div>
    <h1>ForgeSprinkle</h1>
    <p>HTML attribute enhancements. Each directive maps an attribute to a small behavior or style. Everything works (or does nothing) when JavaScript is off.</p>
  </div>
  <button theme-toggle aria-label="Switch to dark theme">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
  </button>
</div>

<!-- ──── Input ──── -->

<h2 id="input">Input</h2>

<div class="doc-directive" id="autosize">
  <h3><code>autosize</code></h3>
  <p class="doc-meta">On: <code>&lt;textarea&gt;</code></p>
  <p>Auto-grows height as content is typed. No scrollbar needed.</p>
  <div class="doc-demo">
    <textarea autosize placeholder="Type here — it grows…"></textarea>
  </div>
  <div class="doc-code">&lt;textarea <span class="doc-attr">autosize</span> <span class="doc-attr">placeholder</span>=<span class="doc-val">"Type here — it grows…"</span>&gt;&lt;/textarea&gt;</div>
</div>

<div class="doc-directive" id="auto-width">
  <h3><code>auto-width</code></h3>
  <p class="doc-meta">On: <code>&lt;input&gt;</code>, <code>&lt;textarea&gt;</code></p>
  <p>Fills parent width.</p>
  <div class="doc-demo">
    <input auto-width placeholder="Full width input" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">auto-width</span> <span class="doc-attr">placeholder</span>=<span class="doc-val">"Full width input"</span> /&gt;</div>
</div>

<div class="doc-directive" id="clearable">
  <h3><code>clearable</code></h3>
  <p class="doc-meta">On: <code>&lt;input&gt;</code></p>
  <p>Shows an × button to clear the value.</p>
  <div class="doc-demo doc-row">
    <input clearable placeholder="Search…" value="Type and clear" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">clearable</span> <span class="doc-attr">placeholder</span>=<span class="doc-val">"Search…"</span> /&gt;</div>
</div>

<div class="doc-directive" id="character-count">
  <h3><code>character-count</code></h3>
  <p class="doc-meta">On: <code>&lt;input&gt;</code>, <code>&lt;textarea&gt;</code></p>
  <p>Live <code>n / max</code> counter. Requires <code>maxlength</code>.</p>
  <div class="doc-demo doc-stack">
    <input character-count maxlength="16" placeholder="Max 16 characters" />
    <textarea character-count maxlength="160" placeholder="Max 160 characters" rows="2"></textarea>
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">character-count</span> <span class="doc-attr">maxlength</span>=<span class="doc-val">"16"</span> /&gt;</div>
</div>

<div class="doc-directive" id="auto-select">
  <h3><code>auto-select</code></h3>
  <p class="doc-meta">On: <code>&lt;input&gt;</code></p>
  <p>Selects all content on focus. Useful for readonly share links.</p>
  <div class="doc-demo">
    <input value="https://example.com/share/abc123" auto-select readonly />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">value</span>=<span class="doc-val">"https://…"</span> <span class="doc-attr">auto-select</span> <span class="doc-attr">readonly</span> /&gt;</div>
</div>

<div class="doc-directive" id="enter-submit">
  <h3><code>enter-submit</code></h3>
  <p class="doc-meta">On: <code>&lt;textarea&gt;</code></p>
  <p>Ctrl+Enter / Cmd+Enter submits the parent form.</p>
  <div class="doc-demo">
    <form onsubmit="event.preventDefault(); alert('Submitted!')">
      <textarea enter-submit placeholder="Write a message… Ctrl+Enter to send" rows="2"></textarea>
    </form>
  </div>
  <div class="doc-code">&lt;textarea <span class="doc-attr">enter-submit</span>&gt;&lt;/textarea&gt;</div>
</div>

<div class="doc-directive" id="file-name">
  <h3><code>file-name</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="file"&gt;</code></p>
  <p>Shows selected filename(s) after picking a file.</p>
  <div class="doc-demo">
    <input type="file" file-name multiple />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"file"</span> <span class="doc-attr">file-name</span> <span class="doc-attr">multiple</span> /&gt;</div>
</div>

<div class="doc-directive" id="leading">
  <h3><code>leading</code> / <code>suffix</code></h3>
  <p class="doc-meta">On: <code>&lt;input&gt;</code></p>
  <p>Inline SVG icons before (leading) or after (suffix) the input value. On <code>type="password"</code>, suffix toggles visibility. On <code>type="search"</code> or <code>suffix="close"</code>, clears the value.</p>
  <div class="doc-demo doc-stack">
    <input leading="search" placeholder="Search…" />
    <input type="password" leading="lock" suffix="eye" value="secret" />
    <input type="search" suffix="close" placeholder="Type to search…" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">leading</span>=<span class="doc-val">"search"</span> <span class="doc-attr">placeholder</span>=<span class="doc-val">"Search…"</span> /&gt;</div>
</div>

<div class="doc-directive" id="prefix">
  <h3><code>prefix</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="url"&gt;</code></p>
  <p>Prepends <code>https://</code> when typing. With a value like <code>prefix="upper.do"</code>, prepends <code>https://upper.do.</code>. Does not re-add on deletion or pasted URLs with a scheme.</p>
  <div class="doc-demo doc-row">
    <input type="url" prefix placeholder="your-site.com" />
    <input type="url" prefix="upper.do" placeholder="subdomain" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"url"</span> <span class="doc-attr">prefix</span> /&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Validation ──── -->

<h2 id="validation">Validation</h2>

<div class="doc-directive" id="error-message">
  <h3><code>error-message</code></h3>
  <p class="doc-meta">On: any validated element</p>
  <p>Custom styled validation messages. Supports per-validity overrides via <code>error-message-required</code>, <code>error-message-minlength</code>, etc. Submits via <code>novalidate</code> — native bubbles are suppressed, errors appear below the field.</p>
  <div class="doc-demo">
    <form class="doc-form">
      <input type="text" minlength="4" maxlength="5" required error-message-maxlength="Too long" error-message-minlength="At least 4 characters!" error-message-required="Can't be blank." placeholder="4–5 chars required" />
      <input type="email" required error-message="We need a valid email." placeholder="Your email" />
      <button type="submit">Submit</button>
    </form>
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"text"</span> <span class="doc-attr">required</span> <span class="doc-attr">error-message-required</span>=<span class="doc-val">"Can't be blank."</span> /&gt;</div>
</div>

<div class="doc-directive" id="allowed-domains">
  <h3><code>allowed-domains</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="email|url"&gt;</code></p>
  <p>Restricts to a comma-separated domain list. Custom message via <code>error-message-allowed-domains</code>. Integrates with the error display system.</p>
  <div class="doc-demo">
    <form class="doc-form">
      <input type="email" allowed-domains="upper.do,example.com" required placeholder="Only @upper.do or @example.com" />
      <button type="submit">Submit</button>
    </form>
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"email"</span> <span class="doc-attr">allowed-domains</span>=<span class="doc-val">"upper.do,example.com"</span> /&gt;</div>
</div>

<div class="doc-directive" id="mask">
  <h3><code>mask</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="tel|text"&gt;</code></p>
  <p>Digit formatting: <code>0</code> in the mask is a digit placeholder. Bare <code>&lt;input type="tel"&gt;</code> defaults to <code>(000) 000-0000</code>.</p>
  <div class="doc-demo doc-row">
    <input type="tel" mask placeholder="Phone" />
    <input type="text" mask="00/00/0000" placeholder="DD/MM/YYYY" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"tel"</span> <span class="doc-attr">mask</span> /&gt;
&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"text"</span> <span class="doc-attr">mask</span>=<span class="doc-val">"00/00/0000"</span> /&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Date / Time ──── -->

<h2 id="date-time">Date / Time</h2>

<div class="doc-directive" id="no-past">
  <h3><code>no-past</code> / <code>no-future</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="date|datetime-local"&gt;</code></p>
  <p><code>no-past</code> sets <code>min</code> to today and clears past values. <code>no-future</code> sets <code>max</code> to today.</p>
  <div class="doc-demo doc-row">
    <input type="date" no-past />
    <input type="date" no-future />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"date"</span> <span class="doc-attr">no-past</span> /&gt;
&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"date"</span> <span class="doc-attr">no-future</span> /&gt;</div>
</div>

<div class="doc-directive" id="disable-days">
  <h3><code>disable-days</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="date|datetime-local"&gt;</code></p>
  <p>Blocks specific days: <code>weekends</code>, day abbreviations (<code>mon,tue</code>), or <code>YYYY-MM-DD</code> list.</p>
  <div class="doc-demo doc-row">
    <input type="date" disable-days="weekends" />
    <input type="date" disable-days="sat,sun" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"date"</span> <span class="doc-attr">disable-days</span>=<span class="doc-val">"weekends"</span> /&gt;</div>
</div>

<div class="doc-directive" id="business-hours">
  <h3><code>business-hours</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="datetime-local"&gt;</code></p>
  <p>Snaps the time portion to the nearest boundary if outside the configured range (e.g. <code>09:00-18:00</code>).</p>
  <div class="doc-demo">
    <input type="datetime-local" business-hours="09:00-18:00" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"datetime-local"</span> <span class="doc-attr">business-hours</span>=<span class="doc-val">"09:00-18:00"</span> /&gt;</div>
</div>

<div class="doc-directive" id="date-range">
  <h3><code>date-range</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="date|datetime-local"&gt;</code></p>
  <p>Pairs start and end inputs. Both need the same <code>date-range</code> value. Use <code>data-range-type="start|end"</code> to assign roles. Opening the end picker jumps to the start date. Delta between values is preserved when adjusting.</p>
  <div class="doc-demo doc-row">
    <input type="date" date-range="trip" data-range-type="start" name="trip_start" />
    <input type="date" date-range="trip" data-range-type="end" name="trip_end" />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"date"</span> <span class="doc-attr">date-range</span>=<span class="doc-val">"trip"</span> <span class="doc-attr">data-range-type</span>=<span class="doc-val">"start"</span> /&gt;
&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"date"</span> <span class="doc-attr">date-range</span>=<span class="doc-val">"trip"</span> <span class="doc-attr">data-range-type</span>=<span class="doc-val">"end"</span> /&gt;</div>
</div>

<div class="doc-directive" id="date-input">
  <h3><code>date-input</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="date|datetime-local"&gt;</code></p>
  <p>Cross-browser visual standardization: consistent font, border, focus ring. <strong style="color:#d32f2f">(Custom date picker UI is work in progress.)</strong></p>
  <div class="doc-demo doc-row">
    <input type="date" date-input />
    <input type="datetime-local" date-input />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"date"</span> <span class="doc-attr">date-input</span> /&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Dialog ──── -->

<h2 id="dialog">Dialog</h2>

<div class="doc-directive" id="drawer">
  <h3><code>drawer</code></h3>
  <p class="doc-meta">On: <code>&lt;dialog&gt;</code></p>
  <p>Sliding side panel. Default direction is <code>left</code>. Supports <code>right</code>, <code>top</code>, <code>bottom</code>. Entry animation via <code>@starting-style</code>. Use <code>command-for</code> (Chromium) or <code>showModal()</code> to open.</p>
  <div class="doc-demo">
    <button class="doc-btn" command-for="demo-drawer" command="show-modal">Open drawer</button>
    <dialog drawer="left" close-outside id="demo-drawer">
      <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <strong>Navigation</strong>
        <button class="sprinkle-modal-close" command-for="demo-drawer" command="close">&times;</button>
      </header>
      <nav style="display:flex;flex-direction:column;gap:0.5rem">
        <a href="#">Home</a>
        <a href="#">Blog</a>
        <a href="#">About</a>
      </nav>
    </dialog>
  </div>
  <div class="doc-code">&lt;dialog <span class="doc-attr">drawer</span>=<span class="doc-val">"left"</span> <span class="doc-attr">id</span>=<span class="doc-val">"nav"</span>&gt;
  …
&lt;/dialog&gt;
&lt;button <span class="doc-attr">command-for</span>=<span class="doc-val">"nav"</span> <span class="doc-attr">command</span>=<span class="doc-val">"show-modal"</span>&gt;Open&lt;/button&gt;</div>
</div>

<div class="doc-directive" id="modal">
  <h3><code>modal</code></h3>
  <p class="doc-meta">On: <code>&lt;dialog&gt;</code></p>
  <p>Centered popup with scale+fade entry. Size variants via <code>modal="sm"</code>, default, <code>modal="lg"</code>. Structure classes: <code>.sprinkle-modal-header</code>, <code>.sprinkle-modal-body</code>, <code>.sprinkle-modal-footer</code>, <code>.sprinkle-modal-close</code>.</p>
  <div class="doc-demo">
    <button class="doc-btn" command-for="demo-modal" command="show-modal">Open modal</button>
    <dialog modal close-outside id="demo-modal">
      <div class="sprinkle-modal-header">
        Confirm Delete
        <button class="sprinkle-modal-close" command-for="demo-modal" command="close">&times;</button>
      </div>
      <div class="sprinkle-modal-body">Are you sure? This action cannot be undone.</div>
      <div class="sprinkle-modal-footer">
        <button class="doc-btn" command-for="demo-modal" command="close">Cancel</button>
        <button class="doc-btn doc-btn-danger">Delete</button>
      </div>
    </dialog>
  </div>
  <div class="doc-code">&lt;dialog <span class="doc-attr">modal</span> <span class="doc-attr">id</span>=<span class="doc-val">"confirm"</span>&gt;
  &lt;div <span class="doc-attr">class</span>=<span class="doc-val">"sprinkle-modal-header"</span>&gt;…&lt;/div&gt;
  &lt;div <span class="doc-attr">class</span>=<span class="doc-val">"sprinkle-modal-body"</span>&gt;…&lt;/div&gt;
  &lt;div <span class="doc-attr">class</span>=<span class="doc-val">"sprinkle-modal-footer"</span>&gt;…&lt;/div&gt;
&lt;/dialog&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Layout ──── -->

<h2 id="layout">Layout</h2>

<div class="doc-directive" id="shell">
  <h3><code>shell</code></h3>
  <p class="doc-meta">On: <code>&lt;div&gt;</code></p>
  <p>Admin shell grid layout. Place <code>&lt;aside sidebar="left|right|top|bottom"&gt;</code> and <code>&lt;main content&gt;</code> as direct children. The grid adapts to whichever sidebars are present. Sidebars scroll independently. Dark mode supported.</p>
  <div class="doc-demo" style="height:280px;resize:vertical;overflow:hidden">
    <div shell style="height:100%">
      <aside sidebar="left" style="padding:1rem">
        <div style="font-weight:700;margin-bottom:1rem">Nimbus</div>
        <nav style="display:flex;flex-direction:column;gap:0.25rem">
          <a href="#" style="padding:0.375rem 0.5rem;border-radius:4px;text-decoration:none;color:inherit;font-weight:500">Overview</a>
          <a href="#" style="padding:0.375rem 0.5rem;border-radius:4px;text-decoration:none;color:inherit">Tenants</a>
          <a href="#" style="padding:0.375rem 0.5rem;border-radius:4px;text-decoration:none;color:inherit">Plans</a>
        </nav>
      </aside>
      <main content style="padding:1.5rem">
        <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
          <h2 style="margin:0;font-size:1.1rem;font-weight:600">Tenant Overview</h2>
          <span style="font-size:0.85rem;color:#888">Last updated: today</span>
        </header>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem">
          <div style="padding:0.75rem;border:1px solid #e0e0e0;border-radius:6px;"><div style="font-size:0.75rem;color:#888">Total Tenants</div><div style="font-size:1.25rem;font-weight:600">24</div></div>
          <div style="padding:0.75rem;border:1px solid #e0e0e0;border-radius:6px;"><div style="font-size:0.75rem;color:#888">Active</div><div style="font-size:1.25rem;font-weight:600">18</div></div>
          <div style="padding:0.75rem;border:1px solid #e0e0e0;border-radius:6px;"><div style="font-size:0.75rem;color:#888">Pending</div><div style="font-size:1.25rem;font-weight:600">6</div></div>
        </div>
      </main>
    </div>
  </div>
  <div class="doc-code">&lt;div <span class="doc-attr">shell</span>&gt;
  &lt;aside <span class="doc-attr">sidebar</span>=<span class="doc-val">"left"</span>&gt;
    &lt;div <span class="doc-attr">class</span>=<span class="doc-val">"logo"</span>&gt;Nimbus&lt;/div&gt;
    &lt;nav&gt;
      &lt;a href="#"&gt;Overview&lt;/a&gt;
      &lt;a href="#"&gt;Tenants&lt;/a&gt;
      &lt;a href="#"&gt;Plans&lt;/a&gt;
    &lt;/nav&gt;
  &lt;/aside&gt;
  &lt;main <span class="doc-attr">content</span>&gt;
    &lt;header&gt;…&lt;/header&gt;
    &lt;div <span class="doc-attr">class</span>=<span class="doc-val">"body"</span>&gt;…&lt;/div&gt;
  &lt;/main&gt;
&lt;/div&gt;</div>
</div>

<div class="doc-directive" id="nav">
  <h3><code>nav</code></h3>
  <p class="doc-meta">On: <code>&lt;ul&gt;</code></p>
  <p>Sidebar navigation menu inside a <code>[shell]</code>. Supports links, <code>[active]</code> state, <code>&lt;hr nav-sep&gt;</code> separators, and <code>&lt;details nav-group&gt;</code> collapsible groups. Groups are exclusive — only one open at a time. Use <code>nav-group="name"</code> for independent groups.</p>
  <div class="doc-demo" style="height:280px;resize:vertical;overflow:hidden">
    <div shell style="height:100%">
      <aside sidebar="left" style="padding:0.75rem">
        <div style="font-weight:700;margin-bottom:0.75rem;padding:0 0.75rem;font-size:0.9rem">Nimbus</div>
        <ul nav>
          <li><a href="#" active>Overview</a></li>
          <li><a href="#">Tenants</a></li>
          <li><hr nav-sep></li>
          <li>
            <details nav-group>
              <summary>Settings</summary>
              <ul>
                <li><a href="#">Profile</a></li>
                <li><a href="#">Billing</a></li>
                <li><a href="#">Team</a></li>
              </ul>
            </details>
          </li>
          <li>
            <details nav-group>
              <summary>Tools</summary>
              <ul>
                <li><a href="#">Importer</a></li>
                <li><a href="#">Exporter</a></li>
              </ul>
            </details>
          </li>
          <li><hr nav-sep></li>
          <li><a href="#">Logout</a></li>
        </ul>
      </aside>
      <main content style="padding:1.5rem">
        <h2 style="margin:0 0 0.25rem;font-size:1.1rem;font-weight:600">Overview</h2>
        <p style="margin:0;font-size:0.85rem;color:#888">Welcome back! Here's what's happening.</p>
      </main>
    </div>
  </div>
  <div class="doc-code">&lt;ul <span class="doc-attr">nav</span>&gt;
  &lt;li&gt;&lt;a href="#" <span class="doc-attr">active</span>&gt;Overview&lt;/a&gt;&lt;/li&gt;
  &lt;li&gt;&lt;a href="#"&gt;Tenants&lt;/a&gt;&lt;/li&gt;
  &lt;li&gt;&lt;hr <span class="doc-attr">nav-sep</span>&gt;&lt;/li&gt;
  &lt;li&gt;
    &lt;details <span class="doc-attr">nav-group</span>&gt;
      &lt;summary&gt;Settings&lt;/summary&gt;
      &lt;ul&gt;
        &lt;li&gt;&lt;a href="#"&gt;Profile&lt;/a&gt;&lt;/li&gt;
        &lt;li&gt;&lt;a href="#"&gt;Billing&lt;/a&gt;&lt;/li&gt;
      &lt;/ul&gt;
    &lt;/details&gt;
  &lt;/li&gt;
&lt;/ul&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Disclosure ──── -->

<h2 id="disclosure">Disclosure</h2>

<div class="doc-directive" id="accordion">
  <h3><code>accordion</code></h3>
  <p class="doc-meta">On: <code>&lt;details&gt;</code></p>
  <p>Animated disclosure with ▶ rotation. Same <code>accordion="group"</code> on multiple elements makes them exclusive (one open at a time).</p>
  <div class="doc-demo doc-stack">
    <details accordion="demo-faq">
      <summary>What is ForgeSprinkle?</summary>
      <p>A set of HTML attribute enhancements. Each directive is self-contained and degrades gracefully.</p>
    </details>
    <details accordion="demo-faq">
      <summary>Does it require a build step?</summary>
      <p>No. Drop in the CSS and JS files. No bundler, no npm, no configuration.</p>
    </details>
    <details accordion="demo-faq">
      <summary>What about accessibility?</summary>
      <p>Uses native <code>&lt;details&gt;</code> semantics. <code>aria-expanded</code> is handled automatically.</p>
    </details>
  </div>
  <div class="doc-code">&lt;details <span class="doc-attr">accordion</span>=<span class="doc-val">"faq"</span>&gt;
  &lt;summary&gt;Question&lt;/summary&gt;
  &lt;p&gt;Answer.&lt;/p&gt;
&lt;/details&gt;</div>
</div>

<div class="doc-directive" id="dropdown">
  <h3><code>dropdown</code></h3>
  <p class="doc-meta">On: <code>&lt;details&gt;</code></p>
  <p>Floating menu panel below <code>&lt;summary&gt;</code>. Left-aligned by default; <code>dropdown="right"</code> for right-alignment. Fade+slide transition. Combine with <code>close-outside</code> for menu behavior.</p>
  <div class="doc-demo">
    <details dropdown close-outside>
      <summary style="display:inline-flex;align-items:center;gap:0.5em;padding:0.375rem 1rem;border:1px solid var(--doc-border);border-radius:4px;background:var(--sprinkle-content-bg);cursor:pointer">
        <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar="sm" style="margin:0" />
        <span>My Account</span>
      </summary>
      <ul>
        <li><a href="#">My Profile</a></li>
        <li><a href="#">Settings</a></li>
        <hr />
        <li><a href="#">Logout</a></li>
      </ul>
    </details>
  </div>
  <div class="doc-code">&lt;details <span class="doc-attr">dropdown</span> <span class="doc-attr">close-outside</span>&gt;
  &lt;summary&gt;My Account&lt;/summary&gt;
  &lt;ul&gt;
    &lt;li&gt;&lt;a href="#"&gt;Profile&lt;/a&gt;&lt;/li&gt;
    &lt;hr /&gt;
    &lt;li&gt;&lt;a href="#"&gt;Logout&lt;/a&gt;&lt;/li&gt;
  &lt;/ul&gt;
&lt;/details&gt;</div>
</div>

<div class="doc-directive" id="close-outside">
  <h3><code>close-outside</code></h3>
  <p class="doc-meta">On: <code>&lt;details&gt;</code></p>
  <p>Closes the <code>&lt;details&gt;</code> when clicking outside the element. Designed for dropdown menus.</p>
  <div class="doc-demo">
    <details close-outside>
      <summary style="display:inline-flex;align-items:center;gap:0.5em;padding:0.375rem 1rem;border:1px solid var(--doc-border);border-radius:4px;background:var(--sprinkle-content-bg);cursor:pointer">Click me</summary>
      <p style="margin:0.5rem 0 0">Try clicking outside to close.</p>
    </details>
  </div>
  <div class="doc-code">&lt;details <span class="doc-attr">close-outside</span>&gt;
  &lt;summary&gt;Click me&lt;/summary&gt;
  &lt;p&gt;Click outside to close.&lt;/p&gt;
&lt;/details&gt;</div>
</div>

<div class="doc-directive" id="group-toggle">
  <h3><code>open-group</code> / <code>close-group</code></h3>
  <p class="doc-meta">On: <code>&lt;button&gt;</code></p>
  <p>Opens or closes all <code>&lt;details accordion="group"&gt;</code> elements sharing the same group name.</p>
  <div class="doc-demo doc-row">
    <button class="doc-btn" open-group="demo-grp">Expand all</button>
    <button class="doc-btn" close-group="demo-grp">Collapse all</button>
    <div class="doc-stack" style="flex:1">
      <details accordion="demo-grp">
        <summary>Item A</summary>
        <p>Content A</p>
      </details>
      <details accordion="demo-grp">
        <summary>Item B</summary>
        <p>Content B</p>
      </details>
    </div>
  </div>
  <div class="doc-code">&lt;button <span class="doc-attr">open-group</span>=<span class="doc-val">"faq"</span>&gt;Expand all&lt;/button&gt;
&lt;button <span class="doc-attr">close-group</span>=<span class="doc-val">"faq"</span>&gt;Collapse all&lt;/button&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Visual ──── -->

<h2 id="visual">Visual</h2>

<div class="doc-directive" id="sticky">
  <h3><code>sticky</code></h3>
  <p class="doc-meta">On: any element</p>
  <p><code>position: sticky; top: 0</code>. Adds <code>.sprinkle-stuck</code> class (with shadow) when stuck.</p>
  <div class="doc-demo">
    <div sticky style="padding:0.5rem 1rem;background:#f0f0f0;border:1px solid #ddd;border-radius:4px">
      Scroll down — I stick to the top.
    </div>
    <div style="height:60px;display:flex;align-items:end;font-size:0.8rem;color:#999">Scroll container for demo</div>
  </div>
  <div class="doc-code">&lt;div <span class="doc-attr">sticky</span>&gt;I stick to the top.&lt;/div&gt;</div>
</div>

<div class="doc-directive" id="zoomable">
  <h3><code>zoomable</code></h3>
  <p class="doc-meta">On: <code>&lt;img&gt;</code></p>
  <p>Click the image to open a fullscreen overlay. Click the overlay or press Escape to close.</p>
  <div class="doc-demo">
    <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" zoomable alt="Demo" style="width:80px;height:80px;object-fit:cover;border-radius:4px;cursor:zoom-in" />
  </div>
  <div class="doc-code">&lt;img <span class="doc-attr">src</span>=<span class="doc-val">"photo.jpg"</span> <span class="doc-attr">zoomable</span> /&gt;</div>
</div>

<div class="doc-directive" id="copy">
  <h3><code>copy</code> / <code>copy="#id"</code></h3>
  <p class="doc-meta">On: any element</p>
  <p>Copies content to clipboard. Without a value, copies the element's <code>.textContent</code> (or <code>.value</code> for inputs). With <code>copy="#id"</code>, copies the referenced element's content.</p>
  <div class="doc-demo doc-row">
    <button class="doc-btn" copy>Copy my label</button>
    <input value="hello@example.com" copy readonly style="width:180px" />
  </div>
  <div class="doc-code">&lt;button <span class="doc-attr">copy</span>&gt;Copy my label&lt;/button&gt;
&lt;input <span class="doc-attr">value</span>=<span class="doc-val">"hello@example.com"</span> <span class="doc-attr">copy</span> /&gt;</div>
</div>

<div class="doc-directive" id="loading">
  <h3><code>loading</code></h3>
  <p class="doc-meta">On: <code>&lt;button&gt;</code></p>
  <p>Disables the button and shows a spinner on click inside a <code>&lt;form&gt;</code>. Resets after submission.</p>
  <div class="doc-demo">
    <form onsubmit="event.preventDefault(); alert('Saved!')">
      <button loading class="doc-btn">Save</button>
    </form>
  </div>
  <div class="doc-code">&lt;button <span class="doc-attr">loading</span>&gt;Save&lt;/button&gt;</div>
</div>

<div class="doc-directive" id="switch">
  <h3><code>switch</code></h3>
  <p class="doc-meta">On: <code>&lt;input type="checkbox"&gt;</code></p>
  <p>Pill switch with <code>role="switch"</code> and <code>aria-checked</code> replacing the native checkbox.</p>
  <div class="doc-demo">
    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
      <input type="checkbox" switch checked />
      Notifications
    </label>
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"checkbox"</span> <span class="doc-attr">switch</span> /&gt;</div>
</div>

<div class="doc-directive" id="truncate">
  <h3><code>truncate="N"</code></h3>
  <p class="doc-meta">On: any element</p>
  <p>Clamps text to N lines with "Show more" / "Show less" toggle. Uses <code>aria-expanded</code>.</p>
  <div class="doc-demo">
    <p truncate="2">
      This is a long paragraph that gets clamped to two lines. The "Show more" button appears at the end of the clamped text. Clicking it reveals the full content, and clicking "Show less" collapses it back. This pattern is useful for card previews, article excerpts, and comment threads where you want to show a preview before expanding. <br /> This is a long paragraph that gets clamped to two lines. The "Show more" button appears at the end of the clamped text. Clicking it reveals the full content, and clicking "Show less" collapses it back. This pattern is useful for card previews, article excerpts, and comment threads where you want to show a preview before expanding.
    </p>
  </div>
  <div class="doc-code">&lt;p <span class="doc-attr">truncate</span>=<span class="doc-val">"2"</span>&gt;Long text…&lt;/p&gt;</div>
</div>

<div class="doc-directive" id="tooltip">
  <h3><code>tooltip</code></h3>
  <p class="doc-meta">On: any element</p>
  <p>Fade-in tooltip using <code>attr(tooltip)</code> as content. Auto-positions to available space (top, bottom, left, right). Auto-removes native <code>title</code>. Hover or focus-visible to trigger.</p>
  <div class="doc-demo doc-row">
    <button class="doc-btn" tooltip="This cannot be undone">Delete</button>
    <span tooltip="Verified account" style="cursor:help">✅ Verified</span>
  </div>
  <div class="doc-code">&lt;button <span class="doc-attr">tooltip</span>=<span class="doc-val">"This cannot be undone"</span>&gt;Delete&lt;/button&gt;
&lt;span <span class="doc-attr">tooltip</span>=<span class="doc-val">"Verified account"</span>&gt;✅ Verified&lt;/span&gt;</div>
</div>

<div class="doc-directive" id="avatar">
  <h3><code>avatar</code></h3>
  <p class="doc-meta">On: <code>&lt;img&gt;</code></p>
  <p>Circular crop with <code>object-fit: cover</code>. Three size tiers: <code>sm</code> (24px), default (40px), <code>lg</code> (64px). CSS-only.</p>
  <div class="doc-demo doc-row">
    <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar="sm" />
    <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar />
    <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar="lg" />
  </div>
  <div class="doc-code">&lt;img <span class="doc-attr">avatar</span>=<span class="doc-val">"sm"</span> /&gt;
&lt;img <span class="doc-attr">avatar</span> /&gt;
&lt;img <span class="doc-attr">avatar</span>=<span class="doc-val">"lg"</span> /&gt;</div>
</div>

<div class="doc-directive" id="breadcrumb">
  <h3><code>breadcrumb</code></h3>
  <p class="doc-meta">On: <code>&lt;ul&gt;</code></p>
  <p>Flex row with <code>/</code> separators. Last item auto-bold. CSS-only.</p>
  <div class="doc-demo">
    <ul breadcrumb>
      <li><a href="#">Home</a></li>
      <li><a href="#">Blog</a></li>
      <li>Current Page</li>
    </ul>
  </div>
  <div class="doc-code">&lt;ul <span class="doc-attr">breadcrumb</span>&gt;
  &lt;li&gt;&lt;a href="#"&gt;Home&lt;/a&gt;&lt;/li&gt;
  &lt;li&gt;Current Page&lt;/li&gt;
&lt;/ul&gt;</div>
</div>

<div class="doc-sep"></div>

<!-- ──── Other ──── -->

<h2 id="other">Other</h2>

<div class="doc-directive" id="confirm-leave">
  <h3><code>confirm-leave</code></h3>
  <p class="doc-meta">On: <code>&lt;form&gt;</code></p>
  <p>Warns before navigating away when the form has unsaved changes.</p>
  <div class="doc-demo">
    <form confirm-leave>
      <textarea placeholder="Type something and try to leave the page…" rows="2" style="width:100%;box-sizing:border-box"></textarea>
    </form>
  </div>
  <div class="doc-code">&lt;form <span class="doc-attr">confirm-leave</span>&gt;
  &lt;textarea&gt;&lt;/textarea&gt;
&lt;/form&gt;</div>
</div>

<div class="doc-directive" id="otp">
  <h3><code>otp</code></h3>
  <p class="doc-meta">On: <code>&lt;input&gt;</code></p>
  <p>Renders N digit boxes (N = <code>max</code> value). Single-field submission via hidden input. Supports paste, keyboard arrows, backspace navigation, auto-advance.</p>
  <div class="doc-demo">
    <input type="number" min="6" max="6" otp />
  </div>
  <div class="doc-code">&lt;input <span class="doc-attr">type</span>=<span class="doc-val">"number"</span> <span class="doc-attr">min</span>=<span class="doc-val">"6"</span> <span class="doc-attr">max</span>=<span class="doc-val">"6"</span> <span class="doc-attr">otp</span> /&gt;</div>
</div>

</main>
</div>
