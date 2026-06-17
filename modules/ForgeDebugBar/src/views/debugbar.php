<?php
/**
 * @var array $data
 */

use App\Modules\ForgeDebugBar\Collectors\TimeCollector;

$startTime = TimeCollector::getStartTime();
?>
<div class="forge-debugbar" style="display: none;">
  <div class="forge-debugbar-logo">Forge</div>

  <div class="forge-debugbar-metrics">
    <div class="forge-debugbar-tab" data-tab="memory">Memory</div>
    <div class="forge-debugbar-tab" data-tab="messages">Messages <span
        class="forge-debugbar-tab-count">(<?= count($data['messages'] ?? []) ?>)</span></div>
    <div class="forge-debugbar-tab" data-tab="exceptions">Exceptions <span
        class="forge-debugbar-tab-count">(<?= count($data['exceptions'] ?? []) ?>)</span></div>
    <div class="forge-debugbar-tab" data-tab="views">Views</div>
    <div class="forge-debugbar-tab" data-tab="route">Route</div>
    <div class="forge-debugbar-tab" data-tab="queries">Queries <span
        class="forge-debugbar-tab-count">(<?= count($data['Database'] ?? []) ?>)</span></div>
    <div class="forge-debugbar-tab" data-tab="session">Session</div>
    <div class="forge-debugbar-tab" data-tab="request">Request</div>
  </div>

  <div class="forge-debugbar-item">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
      class="forge-debugbar-icon size-6">
      <path stroke-linecap="round" stroke-linejoin="round"
        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>

    <span class="forge-debugbar-item-value" id="debugbar-memory">
      <?php if (isset($data['memory']['current'])): ?>
        <?= htmlspecialchars($data['memory']['current']) ?>
      <?php elseif (isset($data['memory'])): ?>
        <?= htmlspecialchars(is_array($data['memory']) ? json_encode($data['memory']) : $data['memory']) ?>
      <?php else: ?>
        N/A
      <?php endif; ?>
    </span>
  </div>

  <div class="forge-debugbar-item">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
      class="forge-debugbar-icon size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <span class="forge-debugbar-item-value" id="debugbar-time"><?= $data['time'] ?? 'N/A' ?></span>
  </div>

  <div class="forge-debugbar-item">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
      class="forge-debugbar-icon size-6">
      <path stroke-linecap="round" stroke-linejoin="round"
        d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
    </svg>
    <span class="forge-debugbar-item-value" id="debugbar-php-version"><?= $data['php_version'] ?? 'N/A' ?></span>
  </div>
</div>

<div class="forge-debugbar-panel" id="debugbar-panel-memory">
  <?php if (isset($data['memory']) && is_array($data['memory'])): ?>

    <ul class="forge-debugbar-memory-list">

      <li class="forge-debugbar-memory-item">
        <div class="forge-debugbar-memory-label">Memory Limit:</div>
        <div class="forge-debugbar-memory-value"><?= htmlspecialchars(ini_get('memory_limit')) ?></div>
      </li>

      <li class="forge-debugbar-memory-item">
        <div class="forge-debugbar-memory-label">Peak Usage:</div>
        <div class="forge-debugbar-memory-value forge-debugbar-memory-peak">
          <?= htmlspecialchars($data['memory']['peak'] ?? 'N/A') ?></div>
      </li>

      <li class="forge-debugbar-memory-item">
        <div class="forge-debugbar-memory-label">Current Usage:</div>
        <div class="forge-debugbar-memory-value"><?= htmlspecialchars($data['memory']['current'] ?? 'N/A') ?></div>
      </li>

      <li class="forge-debugbar-memory-item">
        <div class="forge-debugbar-memory-label">Memory Used:</div>
        <div class="forge-debugbar-memory-value"><?= htmlspecialchars($data['memory']['used'] ?? 'N/A') ?></div>
      </li>

      <?php
      $percentage = $data['memory']['percentage'] ?? null;
      $percent_value = ($percentage !== 'Unlimited' && $percentage !== null) ? (float) str_replace(['%', ' '], '', $percentage) : null;
      $bar_class = '';

      if ($percent_value !== null) {
        if ($percent_value > 80) {
          $bar_class = 'danger';
        } elseif ($percent_value > 50) {
          $bar_class = 'warning';
        } else {
          $bar_class = 'success';
        }
      }
      ?>

      <?php if ($percentage !== 'Unlimited' && $percentage !== null): ?>
        <li class="forge-debugbar-memory-item usage-bar-item">
          <div class="forge-debugbar-memory-label">Usage Percentage:</div>
          <div class="forge-debugbar-memory-value">
            <div class="forge-debugbar-memory-bar">
              <div class="forge-debugbar-memory-bar-fill <?= $bar_class ?>"
                style="width: <?= htmlspecialchars($percentage) ?>"></div>
            </div>
            <span class="forge-debugbar-memory-percent-text"><?= htmlspecialchars($percentage) ?></span>
          </div>
        </li>
      <?php endif; ?>

    </ul>

  <?php else: ?>
    <p>No memory information available.</p>
  <?php endif; ?>
</div>

<div class="forge-debugbar-panels">
  <div class="forge-debugbar-panel" id="debugbar-panel-messages">
    <?php if (isset($data['messages']) && is_array($data['messages']) && !empty($data['messages'])): ?>
      <ol class="debugbar-messages-list">
        <?php foreach ($data['messages'] as $message): ?>
          <li class="debugbar-message-item">
            <span class="debugbar-message-time">
              [<?= number_format($message['relative_time'] ?? (($message['time'] ?? 0) - $startTime) * 1000, 2) ?>ms]
            </span>
            <strong class="debugbar-message-name"><?= htmlspecialchars($message['message'] ?? '') ?></strong>
            <?php if (in_array($message['label'] ?? '', ['info', 'warning', 'error'])): ?>
              <span class="debugbar-message-label debugbar-message-label-<?= htmlspecialchars($message['label'] ?? '') ?>">
                <?= htmlspecialchars(ucfirst($message['label'] ?? '')) ?>
              </span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p>No messages collected.</p>
    <?php endif; ?>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-exceptions">
    <?php if (isset($data['exceptions']) && is_array($data['exceptions']) && !empty($data['exceptions'])): ?>
      <ol class="debugbar-exceptions-list">
        <?php foreach ($data['exceptions'] as $exception): ?>
          <li class="debugbar-exception-item">
            <strong>Type:</strong> <?= htmlspecialchars($exception['type'] ?? '') ?><br />
            <strong>Message:</strong> <?= htmlspecialchars($exception['message'] ?? '') ?><br />
            <strong>Code:</strong> <?= htmlspecialchars($exception['code'] ?? '') ?><br />
            <strong>File:</strong> <?= htmlspecialchars($exception['file'] ?? '') ?><br />
            <?php if (!empty($exception['trace'] ?? '')): ?>
              <details>
                <summary>Trace</summary>
                <ul class="debugbar-exception-trace-list">
                  <?php foreach (explode("\n", $exception['trace'] ?? '') as $traceLine): ?>
                    <li class="debugbar-exception-trace-item">
                      <?= htmlspecialchars($traceLine) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p>No exceptions caught.</p>
    <?php endif; ?>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-views">
    <?php if (isset($data['views']) && is_array($data['views']) && !empty($data['views'])): ?>
      <ol class="debugbar-views-list">
        <?php foreach ($data['views'] as $view): ?>
          <li class="debugbar-view-item">
            <strong>Path:</strong> <?= htmlspecialchars($view['path'] ?? '') ?>
            <?php if (!empty($view['data'] ?? [])): ?>
              <details>
                <summary>Data</summary>
                <ul class="debugbar-view-data-list">
                  <?php foreach ($view['data'] ?? [] as $key => $value): ?>
                    <li class="debugbar-view-data-item">
                      <strong><?= htmlspecialchars($key) ?>:</strong>
                      <?php if (is_array($value)): ?>
                        <ul class="debugbar-view-data-sublist">
                          <?php foreach ($value as $subKey => $subValue): ?>
                            <li class="debugbar-view-data-subitem">
                              <strong><?= htmlspecialchars($subKey) ?>:</strong>
                              <?= is_array($subValue) ? htmlspecialchars(print_r($subValue, true)) : htmlspecialchars($subValue) ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php else: ?>
                        <?= htmlspecialchars($value) ?>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p>No views rendered.</p>
    <?php endif; ?>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-route">
    <?php if (isset($data['route']) && is_array($data['route']) && !empty($data['route']) && !isset($data['route']['error']) && !isset($data['route']['message'])): ?>
      <div class="forge-debugbar-current-route-table">
        <div class="forge-debugbar-route-row">
          <div class="forge-debugbar-route-label">URI</div>
          <div class="forge-debugbar-route-value">
            <span class="forge-debugbar-route-method method-<?= strtolower($data['route']['method'] ?? '') ?>">
              <?= htmlspecialchars($data['route']['method'] ?? '') ?>
            </span>
            <span class="forge-debugbar-route-uri-value">
              <?= htmlspecialchars($data['route']['uri'] ?? '') ?>
            </span>
          </div>
        </div>
        <?php if (!empty($data['route']['middleware'] ?? [])): ?>
          <div class="forge-debugbar-route-row">
            <div class="forge-debugbar-route-label">Middleware</div>
            <div class="forge-debugbar-route-value">
              <ul class="forge-debugbar-route-middleware-list">
                <?php foreach ($data['route']['middleware'] ?? [] as $middleware): ?>
                  <li class="forge-debugbar-route-middleware-item">
                    <?= htmlspecialchars($middleware) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endif; ?>
        <div class="forge-debugbar-route-row">
          <div class="forge-debugbar-route-label">Uses</div>
          <div class="forge-debugbar-route-value">
            <span
              class="forge-debugbar-route-handler-value"><?= htmlspecialchars($data['route']['handler'] ?? '') ?></span>
          </div>
        </div>
        <?php if (isset($data['route']['file'])): ?>
          <div class="forge-debugbar-route-row">
            <div class="forge-debugbar-route-label">File</div>
            <div class="forge-debugbar-route-value">
              <span class="forge-debugbar-route-file-value"><?= htmlspecialchars($data['route']['file'] ?? '') ?></span>
            </div>
          </div>
        <?php endif; ?>
        <?php if (isset($data['route']['prefix'])): ?>
          <div class="forge-debugbar-route-row">
            <div class="forge-debugbar-route-label">Prefix</div>
            <div class="forge-debugbar-route-value">
              <span class="forge-debugbar-route-prefix-value"><?= htmlspecialchars($data['route']['prefix'] ?? '') ?></span>
            </div>
          </div>
        <?php endif; ?>
        <?php if (isset($data['route']['namespace'])): ?>
          <div class="forge-debugbar-route-row">
            <div class="forge-debugbar-route-label">Namespace</div>
            <div class="forge-debugbar-route-value">
              <span
                class="forge-debugbar-route-namespace-value"><?= htmlspecialchars($data['route']['namespace'] ?? '') ?></span>
            </div>
          </div>
        <?php endif; ?>
        <?php if (isset($data['route']['where'])): ?>
          <div class="forge-debugbar-route-row">
            <div class="forge-debugbar-route-label">Where</div>
            <div class="forge-debugbar-route-value">
              <span class="forge-debugbar-route-where-value"><?= htmlspecialchars($data['route']['where'] ?? '') ?></span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php elseif (isset($data['route']['message'])): ?>
      <p><?= htmlspecialchars($data['route']['message'] ?? '') ?></p>
    <?php elseif (isset($data['route']['error'])): ?>
      <p class="forge-debugbar-route-error">Error: <?= htmlspecialchars($data['route']['error'] ?? '') ?></p>
    <?php else: ?>
      <p>No route information available for the current request.</p>
    <?php endif; ?>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-queries">
    <?php if (isset($data['Database']) && is_array($data['Database']) && !empty($data['Database'])): ?>
      <?php foreach ($data['Database'] ?? [] as $queryData): ?>
        <?php
        $performanceClass = '';
        if ($queryData['performance'] ?? '' === 'slow') {
          $performanceClass = 'forge-debugbar-query-slow';
        } elseif ($queryData['performance'] ?? '' === 'medium') {
          $performanceClass = 'forge-debugbar-query-medium';
        } else {
          $performanceClass = 'forge-debugbar-query-fast';
        }
        ?>
        <div class="forge-debugbar-query-entry <?= $performanceClass; ?>">
          <div class="forge-debugbar-query-main-info">
            <p class="forge-debugbar-query-sql"><?= htmlspecialchars($queryData['query'] ?? '') ?></p>
            <p class="forge-debugbar-query-origin">
              Origin: <?= htmlspecialchars($queryData['origin'] ?? '') ?>
            </p>
          </div>
          <div class="forge-debugbar-query-secondary-info">
            <div class="forge-debugbar-info-item">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="forge-debugbar-icon size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
              <span class="forge-debugbar-query-time"><?= $queryData['time_ms'] ?? 'N/A' ?> ms</span>
            </div>
            <div class="forge-debugbar-info-item">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="forge-debugbar-icon size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
              </svg>
              <span
                class="forge-debugbar-query-database"><?= htmlspecialchars($queryData['connection_name'] ?? '') ?></span>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No queries data available or database not active.</p>
    <?php endif; ?>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-mails">
    <p>Mails Panel Content here.</p>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-auth">
    <p>Auth Panel Content here.</p>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-gate">
    <p>Gate Panel Content here.</p>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-session">
    <?php
    if (!function_exists('debugbar_display_array')) {
      function debugbar_display_array($array, $level = 0)
      {
        $html = '<ul class="debugbar-session-sublist">';
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);

        static $collapse_id_counter = 0;

        foreach ($array as $key => $value) {
          $is_array = is_array($value);
          $item_count = $is_array ? count($value) : 0;
          $unique_id = 'session-sub-item-' . ++$collapse_id_counter;

          $html .= '<li class="debugbar-session-subitem">';

          if ($is_array) {
            $html .= '<span class="debugbar-session-key-toggle clickable-toggle-item" data-target="#' . $unique_id . '">';
            $html .= '<span class="toggle-indicator">►</span>';
            $html .= '<strong>' . htmlspecialchars($key) . ':</strong> ';
            $html .= '<span class="array-indicator">Array (' . $item_count . ' items)</span>';
            $html .= '</span>';

            $html .= '<div id="' . $unique_id . '" class="debugbar-session-data-container is-collapsed">';

            if ($item_count > 0) {
              $html .= debugbar_display_array($value, $level + 1);
            }
            $html .= '</div>';
          } else {
            $html .= $indent . '<strong>' . htmlspecialchars($key) . ':</strong> ';
            $html .= htmlspecialchars((string) $value);
          }

          $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
      }
    }
    ?>

    <?php if (isset($data['session']) && is_array($data['session']) && !empty($data['session'])): ?>

      <?php
      $session_data = $data['session'];
      $session_id = $session_data['session_id'] ?? 'N/A';
      $item_count = $session_data['count'] ?? count($session_data) - 1; // Fallback count
      $complex_data = $session_data['data'] ?? [];

      // Remove processed keys for the general list, if you were to keep one
      unset($session_data['session_id']);
      unset($session_data['count']);
      unset($session_data['data']);
      ?>

      <div class="debugbar-session-summary">
        <div class="debugbar-session-row">
          <div class="debugbar-session-label">Session ID:</div>
          <div class="debugbar-session-value debugbar-session-id"><?= htmlspecialchars($session_id) ?></div>
        </div>
        <div class="debugbar-session-row">
          <div class="debugbar-session-label">Total Keys:</div>
          <div class="debugbar-session-value"><?= htmlspecialchars((string) $item_count) ?></div>
        </div>
      </div>

      <h4 class="debugbar-session-data-title clickable-toggle" data-target="#session-data-details">
        Session Data (<span class="debugbar-item-count"><?= count($complex_data) ?></span> top-level keys)
        <span class="toggle-indicator">►</span>
      </h4>

      <div id="session-data-details" class="debugbar-session-data-wrapper is-collapsed">
        <ol class="debugbar-session-list">
          <?php foreach ($complex_data as $key => $value): ?>
            <li class="debugbar-session-item">
              <strong><?= htmlspecialchars($key) ?>:</strong>
              <?php if (is_array($value)): ?>
                <div class="debugbar-session-data-container">
                  <span class="array-indicator">Array (<?= count($value) ?> items)</span>
                  <?= debugbar_display_array($value) ?>
                </div>
              <?php else: ?>
                <?= htmlspecialchars($value) ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <?php if (!empty($session_data)): ?>
        <h4 class="debugbar-session-data-title">Other Session Keys</h4>
        <ol class="debugbar-session-list">
          <?php foreach ($session_data as $key => $value): ?>
            <li class="debugbar-session-item">
              <strong><?= htmlspecialchars($key) ?>:</strong>
              <?php if (is_array($value)): ?>
                <div class="debugbar-session-data-container">
                  <span class="array-indicator">Array (<?= count($value) ?> items)</span>
                  <?= debugbar_display_array($value) ?>
                </div>
              <?php else: ?>
                <?= htmlspecialchars($value) ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

    <?php else: ?>
      <p>No session data available or session not active.</p>
    <?php endif; ?>
  </div>

  <div class="forge-debugbar-panel" id="debugbar-panel-request">
    <?php if (isset($data['request']) && is_array($data['request']) && !empty($data['request'])): ?>
      <ol class="debugbar-request-list">
        <li class="debugbar-request-item">
          <strong>URL:</strong> <?= htmlspecialchars($data['request']['url'] ?? '') ?>
        </li>
        <li class="debugbar-request-item">
          <strong>Method:</strong> <?= htmlspecialchars($data['request']['method'] ?? '') ?>
        </li>
        <li class="debugbar-request-item">
          <strong>IP:</strong> <?= htmlspecialchars($data['request']['ip'] ?? '') ?>
        </li>
        <li class="debugbar-request-item">
          <strong>Headers:</strong>
          <?php if (!empty($data['request']['headers'] ?? [])): ?>
            <ul class="debugbar-request-headers-list">
              <?php foreach ($data['request']['headers'] ?? [] as $header => $value): ?>
                <li class="debugbar-request-header-item">
                  <strong><?= htmlspecialchars($header) ?>:</strong> <?= htmlspecialchars($value) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No headers available.</p>
          <?php endif; ?>
        </li>
        <li class="debugbar-request-item">
          <strong>Query:</strong>
          <?php if (!empty($data['request']['query'] ?? [])): ?>
            <pre class="debugbar-request-query"><?= htmlspecialchars(print_r($data['request']['query'], true)) ?></pre>
          <?php else: ?>
            <p>No query parameters available.</p>
          <?php endif; ?>
        </li>
        <li class="debugbar-request-item">
          <strong>Body:</strong>
          <?php if (!empty($data['request']['body'] ?? [])): ?>
            <pre class="debugbar-request-body"><?= htmlspecialchars(print_r($data['request']['body'], true)) ?></pre>
          <?php else: ?>
            <p>No body data available.</p>
          <?php endif; ?>
        </li>
        <li class="debugbar-request-item">
          <strong>Cookies:</strong>
          <?php if (!empty($data['request']['cookies'] ?? [])): ?>
            <ul class="debugbar-request-cookies-list">
              <?php foreach ($data['request']['cookies'] ?? [] as $cookie => $value): ?>
                <li class="debugbar-request-cookie-item">
                  <strong><?= htmlspecialchars($cookie) ?>:</strong> <?= htmlspecialchars($value) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No cookies available.</p>
          <?php endif; ?>
        </li>
        <li class="debugbar-request-item">
          <strong>Files:</strong>
          <?php if (!empty($data['request']['files'] ?? [])): ?>
            <pre class="debugbar-request-files"><?= htmlspecialchars(print_r($data['request']['files'], true)) ?></pre>
          <?php else: ?>
            <p>No files uploaded.</p>
          <?php endif; ?>
        </li>
      </ol>
    <?php else: ?>
      <p>No request information available.</p>
    <?php endif; ?>
  </div>

</div>
