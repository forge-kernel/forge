<?php $layoutProps = ['title' => 'no style']; ?>
<style>body{margin: 0;}</style>
<div shell>

<aside sidebar="left">
  <div>ForgeSprinkle</div>
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
          <li><a href="#leading-suffix">leading / suffix</a></li>
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
          <li><a href="#group-toggle">open/close-group</a></li>
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

<main content>
<nav>
    <ul breadcrumb boost="hover">
      <li><a href="/sprinkle">Sprinkle styled</a></li>
      <li><a href="/sprinkle/raw">Sprinkle unstyled</a></li>
    </ul>
</nav>
<h1>ForgeSprinkle</h1>

<button theme-toggle aria-label="Switch to dark theme">
  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
</button>

<p>Proof page — every directive rendered with sprinkle defaults only. Zero inline CSS, zero demo classes.</p>

<hr>

<!-- ──── Input ──── -->

<h2 id="input">Input</h2>

<div id="autosize">
  <h3><code>autosize</code></h3>
  <p>On: &lt;textarea&gt;</p>
  <textarea autosize placeholder="Type here — it grows…"></textarea>
  <pre>&lt;textarea autosize placeholder="Type here — it grows…"&gt;&lt;/textarea&gt;</pre>
</div>

<div id="auto-width">
  <h3><code>auto-width</code></h3>
  <p>On: &lt;input&gt;, &lt;textarea&gt;</p>
  <input auto-width placeholder="Full width input" />
  <pre>&lt;input auto-width placeholder="Full width input" /&gt;</pre>
</div>

<div id="clearable">
  <h3><code>clearable</code></h3>
  <p>On: &lt;input&gt;</p>
  <input clearable placeholder="Search…" value="Type and clear" />
  <pre>&lt;input clearable placeholder="Search…" /&gt;</pre>
</div>

<div id="character-count">
  <h3><code>character-count</code></h3>
  <p>On: &lt;input&gt;, &lt;textarea&gt;</p>
  <input character-count maxlength="16" placeholder="Max 16 characters" />
  <textarea character-count maxlength="160" placeholder="Max 160 characters" rows="2"></textarea>
  <pre>&lt;input character-count maxlength="16" /&gt;</pre>
</div>

<div id="auto-select">
  <h3><code>auto-select</code></h3>
  <p>On: &lt;input&gt;</p>
  <input value="https://example.com/share/abc123" auto-select readonly />
  <pre>&lt;input value="https://…" auto-select readonly /&gt;</pre>
</div>

<div id="enter-submit">
  <h3><code>enter-submit</code></h3>
  <p>On: &lt;textarea&gt;</p>
  <textarea enter-submit placeholder="Write a message… Ctrl+Enter to send" rows="2"></textarea>
  <pre>&lt;textarea enter-submit&gt;&lt;/textarea&gt;</pre>
</div>

<div id="file-name">
  <h3><code>file-name</code></h3>
  <p>On: &lt;input type="file"&gt;</p>
  <input type="file" file-name multiple />
  <pre>&lt;input type="file" file-name multiple /&gt;</pre>
</div>

<div id="leading-suffix">
  <h3><code>leading</code> / <code>suffix</code></h3>
  <p>On: &lt;input&gt;</p>
  <input leading="search" placeholder="Search…" />
  <input type="password" leading="lock" suffix="eye" value="secret" />
  <input type="search" suffix="close" placeholder="Type to search…" />
  <pre>&lt;input leading="search" /&gt;
&lt;input type="password" leading="lock" suffix="eye" /&gt;</pre>
</div>

<div id="prefix">
  <h3><code>prefix</code></h3>
  <p>On: &lt;input type="url"&gt;</p>
  <input type="url" prefix placeholder="your-site.com" />
  <input type="url" prefix="upper.do" placeholder="subdomain" />
  <pre>&lt;input type="url" prefix /&gt;
&lt;input type="url" prefix="upper.do" /&gt;</pre>
</div>

<hr>

<!-- ──── Validation ──── -->

<h2 id="validation">Validation</h2>

<div id="error-message">
  <h3><code>error-message</code></h3>
  <p>On: any validated element</p>
  <form>
    <input type="text" minlength="4" maxlength="5" required error-message-maxlength="Too long" error-message-minlength="At least 4 characters!" error-message-required="Can't be blank." placeholder="4–5 chars required" />
    <input type="email" required error-message="We need a valid email." placeholder="Your email" />
    <button type="submit">Submit</button>
  </form>
  <pre>&lt;input required error-message-required="Can't be blank." /&gt;</pre>
</div>

<div id="allowed-domains">
  <h3><code>allowed-domains</code></h3>
  <p>On: &lt;input type="email|url"&gt;</p>
  <form>
    <input type="email" allowed-domains="upper.do,example.com" required placeholder="Only @upper.do or @example.com" />
    <button type="submit">Submit</button>
  </form>
  <pre>&lt;input type="email" allowed-domains="upper.do,example.com" /&gt;</pre>
</div>

<div id="mask">
  <h3><code>mask</code></h3>
  <p>On: &lt;input type="tel|text"&gt;</p>
  <input type="tel" mask placeholder="Phone" />
  <input type="text" mask="00/00/0000" placeholder="DD/MM/YYYY" />
  <pre>&lt;input type="tel" mask /&gt;
&lt;input type="text" mask="00/00/0000" /&gt;</pre>
</div>

<hr>

<!-- ──── Date / Time ──── -->

<h2 id="datetime">Date / Time</h2>

<div id="no-past">
  <h3><code>no-past</code> / <code>no-future</code></h3>
  <p>On: &lt;input type="date|datetime-local"&gt;</p>
  <input type="date" no-past />
  <input type="date" no-future />
  <pre>&lt;input type="date" no-past /&gt;
&lt;input type="date" no-future /&gt;</pre>
</div>

<div id="disable-days">
  <h3><code>disable-days</code></h3>
  <p>On: &lt;input type="date|datetime-local"&gt;</p>
  <input type="date" disable-days="weekends" />
  <input type="date" disable-days="sat,sun" />
  <pre>&lt;input type="date" disable-days="weekends" /&gt;</pre>
</div>

<div id="business-hours">
  <h3><code>business-hours</code></h3>
  <p>On: &lt;input type="datetime-local"&gt;</p>
  <input type="datetime-local" business-hours="09:00-18:00" />
  <pre>&lt;input type="datetime-local" business-hours="09:00-18:00" /&gt;</pre>
</div>

<div id="date-range">
  <h3><code>date-range</code></h3>
  <p>On: &lt;input type="date|datetime-local"&gt;</p>
  <input type="date" date-range="trip" data-range-type="start" name="trip_start" />
  <input type="date" date-range="trip" data-range-type="end" name="trip_end" />
  <pre>&lt;input type="date" date-range="trip" data-range-type="start" /&gt;
&lt;input type="date" date-range="trip" data-range-type="end" /&gt;</pre>
</div>

<div id="date-input">
  <h3><code>date-input</code></h3>
  <p>On: &lt;input type="date|datetime-local"&gt;</p>
  <input type="date" date-input />
  <input type="datetime-local" date-input />
  <pre>&lt;input type="date" date-input /&gt;</pre>
</div>

<hr>

<!-- ──── Dialog ──── -->

<h2 id="dialog">Dialog</h2>

<div id="drawer">
  <h3><code>drawer</code></h3>
  <p>On: &lt;dialog&gt;</p>
  <button command-for="demo-drawer" command="show-modal">Open drawer</button>
  <dialog drawer="left" close-outside id="demo-drawer">
    <header class="sprinkle-modal-header">
      Navigation
      <button class="sprinkle-modal-close" command-for="demo-drawer" command="close">&times;</button>
    </header>
    <div class="sprinkle-modal-body">
      <a href="#">Home</a>
      <a href="#">Blog</a>
      <a href="#">About</a>
    </div>
  </dialog>
  <pre>&lt;dialog drawer="left" close-outside id="nav"&gt;…&lt;/dialog&gt;
&lt;button command-for="nav" command="show-modal"&gt;Open&lt;/button&gt;</pre>
</div>

<div id="modal">
  <h3><code>modal</code></h3>
  <p>On: &lt;dialog&gt;</p>
  <button command-for="demo-modal" command="show-modal">Open modal</button>
  <dialog modal close-outside id="demo-modal">
    <div class="sprinkle-modal-header">
      Confirm Delete
      <button class="sprinkle-modal-close" command-for="demo-modal" command="close">&times;</button>
    </div>
    <div class="sprinkle-modal-body">Are you sure? This action cannot be undone.</div>
    <div class="sprinkle-modal-footer">
      <button command-for="demo-modal" command="close">Cancel</button>
    </div>
  </dialog>
  <pre>&lt;dialog modal id="confirm"&gt;
  &lt;div class="sprinkle-modal-header"&gt;…&lt;/div&gt;
  …
&lt;/dialog&gt;</pre>
</div>

<hr>

<!-- ──── Layout ──── -->

<h2 id="layout">Layout</h2>

<div id="layout">
  <h3><code>shell</code> + <code>sidebar</code> + <code>nav</code> + <code>content</code></h3>
  <p>The page itself uses <code>shell</code> with <code>sidebar="left"</code> and <code>content</code>. Below is a second nested layout example — no inline styles, no extra classes:</p>

  <div shell>
    <aside sidebar="left">
      <div>Dashboard</div>
      <hr nav-sep>
      <ul nav>
        <li><a href="#" active>Overview</a></li>
        <li><a href="#">Analytics</a></li>
        <li><a href="#">Settings</a></li>
      </ul>
    </aside>
    <main content>
      <h4>Welcome to the Dashboard</h4>
      <p>This nested shell uses the same <code>shell</code> grid with <code>sidebar</code> + <code>nav</code>. The sidebar width and content padding come from sprinkle defaults.</p>
    </main>
  </div>

  <pre>&lt;div shell&gt;
  &lt;aside sidebar="left"&gt;
    &lt;ul nav&gt;
      &lt;li&gt;&lt;a href="#" active&gt;Overview&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;hr nav-sep&gt;&lt;/li&gt;
      &lt;li&gt;
        &lt;details nav-group&gt;
          &lt;summary&gt;Settings&lt;/summary&gt;
          &lt;ul&gt;…&lt;/ul&gt;
        &lt;/details&gt;
      &lt;/li&gt;
    &lt;/ul&gt;
  &lt;/aside&gt;
  &lt;main content&gt;…&lt;/main&gt;
&lt;/div&gt;</pre>
</div>

<hr>

<!-- ──── Disclosure ──── -->

<h2 id="disclosure">Disclosure</h2>

<div id="accordion">
  <h3><code>accordion</code></h3>
  <p>On: &lt;details&gt;</p>
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
  <pre>&lt;details accordion="faq"&gt;
  &lt;summary&gt;Question&lt;/summary&gt;
  &lt;p&gt;Answer.&lt;/p&gt;
&lt;/details&gt;</pre>
</div>

<div id="dropdown">
  <h3><code>dropdown</code></h3>
  <p>On: &lt;details&gt;</p>
  <details dropdown close-outside>
    <summary>
      <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar="sm">
      <span>My Account</span>
    </summary>
    <ul>
      <li><a href="#">My Profile</a></li>
      <li><a href="#">Settings</a></li>
      <li><a href="#">Logout</a></li>
    </ul>
  </details>
  <pre>&lt;details dropdown close-outside&gt;
  &lt;summary&gt;My Account&lt;/summary&gt;
  &lt;ul&gt;…&lt;/ul&gt;
&lt;/details&gt;</pre>
</div>

<div id="close-outside">
  <h3><code>close-outside</code></h3>
  <p>On: &lt;details&gt;, &lt;dialog&gt;</p>
  <details close-outside>
    <summary>Click me</summary>
    <p>Try clicking outside to close.</p>
  </details>
  <pre>&lt;details close-outside&gt;
  &lt;summary&gt;Click me&lt;/summary&gt;
  &lt;p&gt;Click outside to close.&lt;/p&gt;
&lt;/details&gt;</pre>
</div>

<div id="group-toggle">
  <h3><code>open-group</code> / <code>close-group</code></h3>
  <p>On: &lt;button&gt;</p>
  <button open-group="demo-grp">Expand all</button>
  <button close-group="demo-grp">Collapse all</button>
  <details accordion="demo-grp">
    <summary>Item A</summary>
    <p>Content A</p>
  </details>
  <details accordion="demo-grp">
    <summary>Item B</summary>
    <p>Content B</p>
  </details>
  <pre>&lt;button open-group="faq"&gt;Expand all&lt;/button&gt;
&lt;button close-group="faq"&gt;Collapse all&lt;/button&gt;</pre>
</div>

<hr>

<!-- ──── Visual ──── -->

<h2 id="visual">Visual</h2>

<div id="sticky">
  <h3><code>sticky</code></h3>
  <p>On: any element</p>
  <div sticky>
    Scroll down — I stick to the top.
  </div>
  <div>Scroll for sticky demo</div>
  <pre>&lt;div sticky&gt;I stick to the top.&lt;/div&gt;</pre>
</div>

<div id="zoomable">
  <h3><code>zoomable</code></h3>
  <p>On: &lt;img&gt;</p>
  <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" zoomable alt="Demo" />
  <pre>&lt;img src="photo.jpg" zoomable /&gt;</pre>
</div>

<div id="copy">
  <h3><code>copy</code> / <code>copy="#id"</code></h3>
  <p>On: any element</p>
  <button copy>Copy my label</button>
  <input value="hello@example.com" copy readonly />
  <pre>&lt;button copy&gt;Copy my label&lt;/button&gt;
&lt;input value="hello@example.com" copy /&gt;</pre>
</div>

<div id="loading">
  <h3><code>loading</code></h3>
  <p>On: &lt;button&gt;</p>
  <form>
    <button loading>Save</button>
  </form>
  <pre>&lt;button loading&gt;Save&lt;/button&gt;</pre>
</div>

<div id="switch">
  <h3><code>switch</code></h3>
  <p>On: &lt;input type="checkbox"&gt;</p>
  <label>
    <input type="checkbox" switch checked />
    Notifications
  </label>
  <pre>&lt;input type="checkbox" switch /&gt;</pre>
</div>

<div id="truncate">
  <h3><code>truncate="N"</code></h3>
  <p>On: any element</p>
  <p truncate="2">
    This is a long paragraph that gets clamped to two lines. The "Show more" button appears at the end of the clamped text. Clicking it reveals the full content, and clicking "Show less" collapses it back. This pattern is useful for card previews, article excerpts, and comment threads where you want to show a preview before expanding. This is a long paragraph that gets clamped to two lines. The "Show more" button appears at the end of the clamped text. Clicking it reveals the full content, and clicking "Show less" collapses it back. This pattern is useful for card previews, article excerpts, and comment threads where you want to show a preview before expanding. This is a long paragraph that gets clamped to two lines. The "Show more" button appears at the end of the clamped text. Clicking it reveals the full content, and clicking "Show less" collapses it back. This pattern is useful for card previews, article excerpts, and comment threads where you want to show a preview before expanding.
  </p>
  <pre>&lt;p truncate="2"&gt;Long text…&lt;/p&gt;</pre>
</div>

<div id="tooltip">
  <h3><code>tooltip</code></h3>
  <p>On: any element</p>
  <button tooltip="This cannot be undone">Delete</button>
  <span tooltip="Verified account">✅ Verified</span>
  <pre>&lt;button tooltip="This cannot be undone"&gt;Delete&lt;/button&gt;
&lt;span tooltip="Verified account"&gt;✅ Verified&lt;/span&gt;</pre>
</div>

<div id="avatar">
  <h3><code>avatar</code></h3>
  <p>On: &lt;img&gt;</p>
  <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar="sm" />
  <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar />
  <img src="https://i.abcnewsfe.com/a/10669fab-5a56-4555-8012-0b3d83369352/avatar-the-way-of-water-07-ht-jt-220907_1662579296232_hpMain_1x1.jpg?w=992" avatar="lg" />
  <pre>&lt;img avatar="sm" /&gt;
&lt;img avatar /&gt;
&lt;img avatar="lg" /&gt;</pre>
</div>

<div id="breadcrumb">
  <h3><code>breadcrumb</code></h3>
  <p>On: &lt;ul&gt;</p>
  <ul breadcrumb>
    <li><a href="#">Home</a></li>
    <li><a href="#">Blog</a></li>
    <li>Current Page</li>
  </ul>
  <pre>&lt;ul breadcrumb&gt;
  &lt;li&gt;&lt;a href="#"&gt;Home&lt;/a&gt;&lt;/li&gt;
  &lt;li&gt;Current Page&lt;/li&gt;
&lt;/ul&gt;</pre>
</div>

<hr>

<!-- ──── Other ──── -->

<h2 id="other">Other</h2>

<div id="confirm-leave">
  <h3><code>confirm-leave</code></h3>
  <p>On: &lt;form&gt;</p>
  <form confirm-leave>
    <textarea placeholder="Type something and try to leave the page…" rows="2"></textarea>
  </form>
  <pre>&lt;form confirm-leave&gt;
  &lt;textarea&gt;&lt;/textarea&gt;
&lt;/form&gt;</pre>
</div>

<div id="otp">
  <h3><code>otp</code></h3>
  <p>On: &lt;input&gt;</p>
  <input type="number" min="6" max="6" otp />
  <pre>&lt;input type="number" min="6" max="6" otp /&gt;</pre>
</div>

</main>

</div>
