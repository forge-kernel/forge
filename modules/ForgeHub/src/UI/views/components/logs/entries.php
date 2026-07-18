<?php
/** @var array $entries */
/** @var string|null $selectedFile */
/** @var string|null $error */
/** @var array $filters */
/** @var array $modules */

$basePath = BASE_PATH . '/';
$relativePath = function (?string $path) use ($basePath) {
    if (!$path) return null;
    return str_starts_with($path, $basePath) ? substr($path, strlen($basePath)) : $path;
};

$levelMsgBg = [
    'ERROR'    => 'background:#fff6f6;border:1px solid #f5c6cb;border-left:4px solid #dc3545;',
    'WARNING'  => 'background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;',
    'CRITICAL' => 'background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #991b1b;',
    'DEBUG'    => 'background:#f9fafb;border:1px solid #e5e7eb;border-left:4px solid #6b7280;',
    'INFO'     => 'background:#eff6ff;border:1px solid #bfdbfe;border-left:4px solid #2563eb;',
];
?>

<style>
.log-entries *, .log-entries *::before, .log-entries *::after { box-sizing: border-box; margin: 0; padding: 0; }
.log-entries { font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; }

.log-entries .le-list {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
    border: 1px solid #e0e0e0;
    overflow: hidden;
}

.log-entries .le-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.65rem 1rem;
    cursor: pointer;
    transition: background 0.15s;
    user-select: none;
    border-bottom: 1px solid #f0f0f0;
}
.log-entries .le-row:last-child { border-bottom: none; }
.log-entries .le-row:hover { background: #f8f9fa; }

.log-entries .le-fp {
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
    flex-shrink: 0;
}

.log-entries .le-type {
    display: inline-block;
    background: #1f2937;
    color: #fff;
    padding: 0.15rem 0.55rem;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.78rem;
    text-transform: uppercase;
    flex-shrink: 0;
}

.log-entries .le-module {
    display: inline-block;
    background: #eef2ff;
    color: #6366f1;
    padding: 0.15rem 0.55rem;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.78rem;
    flex-shrink: 0;
}

.log-entries .le-msg-trunc {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.9rem;
    color: #374151;
}

.log-entries .le-time {
    font-size: 0.8rem;
    color: #9ca3af;
    white-space: nowrap;
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    margin-left: auto;
    flex-shrink: 0;
}

.log-entries .le-chevron {
    color: #9ca3af;
    transition: transform 0.2s;
    flex-shrink: 0;
    width: 1rem;
    height: 1rem;
}
.log-entries .le-row.open .le-chevron { transform: rotate(90deg); }

.log-entries .le-card-body {
    display: none;
    border-bottom: 1px solid #f0f0f0;
}
.log-entries .le-row.open + .le-card-body { display: block; }

.log-entries .le-card-inner {
    padding: 1.25rem 1.5rem;
}

.log-entries .le-msg {
    border-radius: 8px;
    padding: 1rem 1.25rem;
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.95rem;
    word-break: break-word;
    margin-bottom: 1rem;
}
.log-entries .le-msg-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #777;
    margin-bottom: 0.25rem;
}

.log-entries .le-chip-full {
    background: #f0f0f0;
    border-radius: 4px;
    padding: 0.3rem 0.7rem;
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}
.log-entries .le-chip-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #777;
}
.log-entries .le-chip-val {
    color: #212936;
    font-weight: 500;
}

.log-entries .le-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #777;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e0e0e0;
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
}

.log-entries details { margin-top: 0.75rem; }
.log-entries details summary {
    font-size: 0.8rem;
    color: #777;
    cursor: pointer;
    font-weight: 500;
}
.log-entries details summary:hover { color: #212936; }

.log-entries .trace-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.log-entries .trace-frame {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e0e0e0;
}
.log-entries .trace-frame:last-child { border-bottom: none; }
.log-entries .trace-call {
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.85rem;
    color: #2563eb;
    font-weight: 500;
}
.log-entries .trace-call .frame-num {
    color: #777;
    font-weight: 400;
    margin-right: 0.5rem;
}
.log-entries .trace-location {
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.8rem;
    color: #777;
    margin-top: 0.15rem;
}

.log-entries .le-ctx-box {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 0.75rem;
    overflow-x: auto;
}
.log-entries .le-ctx-box pre {
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.8rem;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    color: #333;
}

.log-entries .le-error-box {
    background: #fff6f6;
    border: 1px solid #f5c6cb;
    border-left: 4px solid #dc3545;
    border-radius: 8px;
    padding: 1rem 1.25rem;
}
.log-entries .le-error-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #777;
    margin-bottom: 0.25rem;
}
.log-entries .le-error-msg {
    font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
    font-size: 0.95rem;
    word-break: break-word;
}

.log-entries .le-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 1rem;
    border-top: 1px solid #e0e0e0;
    background: #f9fafb;
    font-size: 0.78rem;
    color: #6b7280;
}
.log-entries .le-pagination-btns {
    display: flex;
    gap: 0.25rem;
}
.log-entries .le-pagination-btn {
    padding: 0.25rem 0.6rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
    color: #374151;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.log-entries .le-pagination-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
.log-entries .le-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.log-entries .le-pagination-btn.active {
    background: #1f2937;
    border-color: #1f2937;
    color: #fff;
}

.log-entries .le-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafbfc;
    flex-wrap: wrap;
}
.log-entries .le-filter label {
    font-size: 0.72rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.log-entries .le-filter input,
.log-entries .le-filter select {
    padding: 0.35rem 0.6rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #374151;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.log-entries .le-filter input:focus,
.log-entries .le-filter select:focus {
    border-color: #9ca3af;
    box-shadow: 0 0 0 2px rgba(0,0,0,0.05);
}
.log-entries .le-filter .le-search-wrap {
    position: relative;
    flex: 1;
    min-width: 180px;
    max-width: 320px;
}
.log-entries .le-filter .le-search-wrap input {
    width: 100%;
    padding-left: 2rem;
}
.log-entries .le-filter .le-search-icon {
    position: absolute;
    left: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    width: 0.9rem;
    height: 0.9rem;
    color: #9ca3af;
    pointer-events: none;
}
.log-entries .le-filter input[type="date"] { width: 135px; }
.log-entries .le-filter select { width: 110px; }
.log-entries .le-filter-btn {
    padding: 0.35rem 0.75rem;
    border: 1px solid #1f2937;
    border-radius: 6px;
    background: #1f2937;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: opacity 0.15s;
}
.log-entries .le-filter-btn:hover { opacity: 0.85; }
.log-entries .le-filter-clear {
    font-size: 0.78rem;
    color: #6b7280;
    text-decoration: none;
    transition: color 0.15s;
    padding: 0.35rem 0.5rem;
}
.log-entries .le-filter-clear:hover { color: #374151; }
.log-entries .le-filter-sep {
    width: 1px;
    height: 1.25rem;
    background: #e5e7eb;
    flex-shrink: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var PER_PAGE = 25;
    var container = document.querySelector('.log-entries');
    if (!container) return;

    var list = container.querySelector('.le-list');
    if (!list) return;

    var rows = Array.prototype.slice.call(list.querySelectorAll('.le-row'));
    var bodies = Array.prototype.slice.call(list.querySelectorAll('.le-card-body'));
    var pagination = list.querySelector('.le-pagination');
    var totalPages = Math.ceil(rows.length / PER_PAGE) || 1;
    var current = 1;

    function showPage(page) {
        current = page;
        var start = (page - 1) * PER_PAGE;
        var end = start + PER_PAGE;
        rows.forEach(function(r, i) {
            r.style.display = (i >= start && i < end) ? '' : 'none';
            r.classList.remove('open');
        });
        bodies.forEach(function(b, i) {
            b.style.display = 'none';
            b.classList.remove('open');
        });
        updatePagination();
    }

    function updatePagination() {
        if (!pagination) return;
        var info = pagination.querySelector('.le-page-info');
        var btns = pagination.querySelector('.le-pagination-btns');
        var start = (current - 1) * PER_PAGE + 1;
        var end = Math.min(current * PER_PAGE, rows.length);
        info.textContent = rows.length > 0 ? start + '–' + end + ' of ' + rows.length : 'No entries';

        btns.innerHTML = '';
        var prev = document.createElement('button');
        prev.className = 'le-pagination-btn';
        prev.textContent = '‹';
        prev.disabled = current <= 1;
        prev.onclick = function() { showPage(current - 1); };
        btns.appendChild(prev);

        for (var p = 1; p <= totalPages; p++) {
            if (totalPages > 7 && p > 3 && p < totalPages - 2 && Math.abs(p - current) > 1) {
                if (p === 4 || p === totalPages - 3) {
                    var dots = document.createElement('span');
                    dots.textContent = '…';
                    dots.style.padding = '0 0.3rem';
                    dots.style.color = '#9ca3af';
                    btns.appendChild(dots);
                }
                continue;
            }
            var b = document.createElement('button');
            b.className = 'le-pagination-btn' + (p === current ? ' active' : '');
            b.textContent = p;
            b.onclick = (function(pg) { return function() { showPage(pg); }; })(p);
            btns.appendChild(b);
        }

        var next = document.createElement('button');
        next.className = 'le-pagination-btn';
        next.textContent = '›';
        next.disabled = current >= totalPages;
        next.onclick = function() { showPage(current + 1); };
        btns.appendChild(next);
    }

    rows.forEach(function(row, i) {
        row.addEventListener('click', function() {
            var wasOpen = row.classList.contains('open');
            rows.forEach(function(r) { r.classList.remove('open'); });
            bodies.forEach(function(b) { b.style.display = 'none'; });
            if (!wasOpen) {
                row.classList.add('open');
                bodies[i].style.display = 'block';
            }
        });
    });

    if (rows.length > PER_PAGE) {
        pagination.style.display = '';
        showPage(1);
    } else {
        if (pagination) pagination.style.display = 'none';
    }
});
</script>

<div class="log-entries">

<?php if (!empty($error)): ?>
  <div class="le-error-box">
    <div class="le-error-label">Error</div>
    <div class="le-error-msg"><?= htmlspecialchars($error) ?></div>
  </div>

<?php elseif (empty($entries)): ?>
  <div class="le-list" style="padding:3rem;text-align:center;">
    <svg style="margin:0 auto;height:3rem;width:3rem;color:#999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    <p style="margin-top:0.5rem;font-size:0.85rem;color:#777;">No log entries found</p>
  </div>

<?php else: ?>
  <?php
  $hasFilters = !empty($filters['search']) || !empty($filters['date']) || !empty($filters['level']) || !empty($filters['module']) || !empty($filters['fingerprint']);
  $clearUrl = '?file=' . rawurlencode($selectedFile);
  ?>
  <div class="le-list">

    <form method="get" class="le-filter">
      <div class="le-search-wrap">
        <svg class="le-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <input type="search" name="search" placeholder="Search messages, files, exceptions..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
      </div>

      <span class="le-filter-sep"></span>

      <label>Date</label>
      <input type="date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">

      <span class="le-filter-sep"></span>

      <label>Level</label>
      <select name="level">
        <option value="">All</option>
        <?php foreach (['ERROR', 'WARNING', 'INFO', 'DEBUG', 'CRITICAL'] as $lvl): ?>
          <option value="<?= $lvl ?>" <?= ($filters['level'] ?? '') === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
        <?php endforeach; ?>
      </select>

      <label>Module</label>
      <select name="module">
        <option value="">All</option>
        <?php foreach ($modules as $mod): ?>
          <option value="<?= htmlspecialchars($mod) ?>" <?= ($filters['module'] ?? '') === $mod ? 'selected' : '' ?>><?= htmlspecialchars($mod) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="hidden" name="file" value="<?= htmlspecialchars($selectedFile) ?>">

      <label>Fingerprint</label>
      <input type="search" name="fingerprint" placeholder="#..." value="<?= htmlspecialchars($filters['fingerprint'] ?? '') ?>" style="width:100px;">

      <button type="submit" class="le-filter-btn">Filter</button>
      <?php if ($hasFilters): ?>
        <a href="<?= $clearUrl ?>" class="le-filter-clear">Clear</a>
      <?php endif; ?>
    </form>

    <?php foreach ($entries as $entry): ?>
      <?php
      $level = strtoupper($entry->level);
      $relFile = $relativePath($entry->file);
      $msgBgStyle = $levelMsgBg[$level] ?? $levelMsgBg['INFO'];
      $exceptionType = $entry->exception;
      $shortMsg = mb_strimwidth($entry->message, 0, 40, '…');
      ?>
      <div class="le-row">
        <?php if ($entry->fingerprint): ?>
          <span class="le-fp" title="<?= htmlspecialchars($entry->fingerprint) ?>">#<?= htmlspecialchars(substr($entry->fingerprint, 0, 8)) ?></span>
        <?php endif; ?>

        <?php if ($exceptionType): ?>
          <span class="le-type"><?= htmlspecialchars($exceptionType) ?></span>
        <?php endif; ?>

        <?php if ($entry->module): ?>
          <span class="le-module"><?= htmlspecialchars($entry->module) ?></span>
        <?php endif; ?>

        <?php if ($shortMsg): ?>
          <span class="le-msg-trunc" title="<?= htmlspecialchars($entry->message) ?>"><?= htmlspecialchars($shortMsg) ?></span>
        <?php endif; ?>

        <time class="le-time" datetime="<?= $entry->date->format('Y-m-d\TH:i:s') ?>">
          <?= $entry->date->format('M d, H:i:s') ?>
        </time>

        <svg class="le-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </div>

      <div class="le-card-body">
        <div class="le-card-inner">

          <div class="le-msg" style="<?= $msgBgStyle ?>">
            <div class="le-msg-label">Message</div>
            <?= htmlspecialchars($entry->message) ?>
          </div>

          <?php if ($relFile): ?>
            <div class="le-chip-full">
              <span class="le-chip-label">Origin</span><br>
              <span class="le-chip-val"><?= htmlspecialchars($relFile) ?>:<?= $entry->line ?? '?' ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($entry->trace)): ?>
            <details>
              <summary>Stack Trace &mdash; <?= count($entry->trace) ?> frames</summary>
              <ol class="trace-list" style="margin-top:1rem;">
                <?php foreach ($entry->trace as $frame): ?>
                  <li class="trace-frame">
                    <div class="trace-call">
                      <span class="frame-num">#<?= $frame['index'] ?? '?' ?></span>
                      <?php if (isset($frame['call'])): ?>
                        <?= htmlspecialchars($frame['call']) ?>
                      <?php endif; ?>
                    </div>
                    <?php if (isset($frame['file'])): ?>
                      <div class="trace-location"><?= htmlspecialchars($relativePath($frame['file']) ?? $frame['file']) ?>:<?= $frame['line'] ?? '?' ?></div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ol>
            </details>
          <?php endif; ?>

          <?php if (!empty($entry->context) && count($entry->context) > 1): ?>
            <details>
              <summary>Context</summary>
              <div class="le-ctx-box">
                <pre><?= htmlspecialchars(json_encode($entry->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
              </div>
            </details>
          <?php endif; ?>

          <?php if ($entry->requestId): ?>
            <div class="le-footer">
              <span style="color:#777;">Request <?= htmlspecialchars(substr($entry->requestId, 0, 16)) ?></span>
              <?php if ($entry->fingerprint): ?>
                <span style="color:#999;">Fingerprint <?= htmlspecialchars($entry->fingerprint) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    <?php endforeach; ?>

    <div class="le-pagination" style="display:none;">
      <span class="le-page-info"></span>
      <div class="le-pagination-btns"></div>
    </div>

  </div>
<?php endif; ?>

</div>
