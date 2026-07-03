<?php 
 /** @var array $data */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Forge Application</title>
    <style>
    :root {
        --bg: #f1f2f4;
        --surface: #ffffff;
        --text: #222;
        --muted: #777;
        --primary: #212936;
        --red: #dc3545;
        --border: #e0e0e0;
        --radius: 8px;
        --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    * { box-sizing: border-box; margin: 0; }

    body {
        background: var(--bg);
        color: var(--text);
        font-family: system-ui, -apple-system, sans-serif;
        line-height: 1.6;
        padding: 2rem;
    }

    .error-container {
        max-width: 100%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        overflow-x: hidden;
    }

    .card {
        background: var(--surface);
        border-radius: var(--radius);
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
        padding: 2rem;
        max-width: 100%;
        overflow-wrap: break-word;
        overflow-x: auto;
    }

    .type-badge {
        display: inline-block;
        background: var(--primary);
        color: #fff;
        padding: 0.2rem 0.8rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.8rem;
        letter-spacing: 0.02em;
        margin-bottom: 0.75rem;
    }

    .message-box {
        background: #fff6f6;
        border: 1px solid #f5c6cb;
        border-left: 4px solid var(--red);
        border-radius: var(--radius);
        padding: 1rem 1.25rem;
        font-family: var(--mono);
        font-size: 0.95rem;
        word-break: break-word;
        margin-bottom: 1rem;
    }

    .message-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        margin-bottom: 0.25rem;
    }

    .meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: baseline;
        font-family: var(--mono);
        font-size: 0.85rem;
    }

    .chip {
        background: #f0f0f0;
        border-radius: 4px;
        padding: 0.3rem 0.7rem;
        width: 100%;
    }

    .chip-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--muted);
    }

    .chip-value {
        color: var(--primary);
        font-weight: 500;
    }

    .chip-context {
        background: #eef2ff;
    }

    .chip-context .chip-label { color: #6366f1; }

    .footer-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 0.75rem;
    }

    .env-badge {
        background: var(--primary);
        color: #fff;
        padding: 0.15rem 0.6rem;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .section-title {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--muted);
        margin-bottom: 1rem;
    }

    .trace-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .trace-frame {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
    }

    .trace-frame:last-child { border-bottom: none; }

    .trace-call {
        font-family: var(--mono);
        font-size: 0.85rem;
        color: var(--primary);
        font-weight: 500;
    }

    .trace-call .frame-num {
        color: var(--muted);
        font-weight: 400;
        margin-right: 0.5rem;
    }

    .trace-location {
        font-family: var(--mono);
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 0.15rem;
    }

    details {
        margin-top: 0.5rem;
    }

    details summary {
        font-size: 0.8rem;
        color: var(--muted);
        cursor: pointer;
    }

    .flow-list {
        display: flex;
        flex-direction: column;
        gap: 0;
        font-family: var(--mono);
        font-size: 0.85rem;
    }

    .flow-entry {
        padding: 0.3rem 0;
        color: var(--muted);
    }

    .flow-step {
        padding: 0.3rem 0 0.3rem 1.5rem;
        color: var(--primary);
        font-weight: 500;
        position: relative;
    }

    .flow-step::before {
        content: '';
        position: absolute;
        left: 0.25rem;
        top: 0;
        bottom: 0;
        width: 1px;
        background: var(--border);
    }

    .flow-step::after {
        content: '\2192';
        position: absolute;
        left: -0.1rem;
        top: 0.3rem;
        color: var(--muted);
        font-weight: 400;
    }

    .flow-location {
        color: var(--muted);
        font-weight: 400;
        font-size: 0.8rem;
    }

    .flow-group {
        margin-bottom: 0.5rem;
    }

    .flow-group:last-child {
        margin-bottom: 0;
    }

    .flow-group-header {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #999;
        margin-bottom: 0.25rem;
        padding-left: 0.25rem;
    }

    .flow-group-module {
        color: #6366f1;
    }

    .flow-step-module {
        color: #4f46e5;
        font-weight: 600;
    }

    .code-snippet {
        background: #f8f9fa;
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 0.75rem;
        overflow-x: auto;
        font-family: var(--mono);
        font-size: 0.8rem;
        margin-top: 0.5rem;
    }

    .code-line { display: table-row; }
    .line-number {
        display: table-cell;
        text-align: right;
        padding-right: 1em;
        user-select: none;
        opacity: 0.5;
    }
    .highlighted-line .line-number { opacity: 0.8; }
    .highlighted-line {
        background: rgba(220, 53, 69, 0.07);
        border-left: 2px solid var(--red);
        margin: 0 -0.75rem;
        padding: 0 0.75rem;
    }
    </style>
</head>

<body>
    <div class="error-container">

        <div class="card">
            <div class="type-badge"><?= $data['error']['original_type'] ?></div>

            <div class="message-box">
                <div class="message-label">Message</div>
                <?= htmlspecialchars($data['error']['original_message']) ?>
            </div>

            <div class="meta-row">
                <div class="chip">
                    <span class="chip-label">Origin</span><br>
                    <span class="chip-value"><?= $data['error']['origin_file'] ?>:<?= $data['error']['origin_line'] ?></span>
                </div>

                <?php if (!empty($data['error']['context']['module'])): ?>
                <div class="chip chip-context">
                    <span class="chip-label">Module</span><br>
                    <span class="chip-value"><?= $data['error']['context']['module'] ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($data['error']['context']['hook'])): ?>
                <div class="chip chip-context">
                    <span class="chip-label">Hook</span><br>
                    <span class="chip-value"><?= $data['error']['context']['hook'] ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($data['error']['context']['method'])): ?>
                <div class="chip chip-context">
                    <span class="chip-label">Method</span><br>
                    <span class="chip-value"><?= $data['error']['context']['method'] ?>()</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="footer-bar">
                <span><?= $data['error']['type'] ?></span>
                <span>
                    <span class="env-badge"><?= strtoupper($_ENV['APP_ENV'] ?? 'DEBUG') ?></span>
                    PHP <?= phpversion() ?>
                </span>
            </div>
        </div>

        <?php
        $reversed = array_reverse($data['error']['trace']);
        $groups = [];
        $currentGroup = null;
        foreach ($reversed as $frame) {
            $file = $frame['file'] ?? '';
            if ($file === 'public/index.php') {
                $group = 'ENTRYPOINT';
            } elseif (str_starts_with($file, 'kernel/Core/Bootstrap/')) {
                $group = 'BOOTSTRAP';
            } elseif (str_starts_with($file, 'kernel/')) {
                $group = 'KERNEL';
            } elseif (str_starts_with($file, 'modules/')) {
                $group = 'MODULE';
            } else {
                $group = null;
            }
            if ($currentGroup !== $group) {
                $groups[] = ['header' => $group, 'frames' => []];
                $currentGroup = $group;
            }
            $groups[count($groups) - 1]['frames'][] = $frame;
        }
        ?>
        <div class="card">
            <div class="section-title">Execution Flow</div>
            <?php $isFirstFrame = true; ?>
            <?php foreach ($groups as $g): ?>
            <?php if ($g['header'] !== null): ?>
            <?php $isModule = $g['header'] === 'MODULE'; ?>
            <div class="flow-group">
                <div class="flow-group-header <?= $isModule ? 'flow-group-module' : '' ?>"><?= $g['header'] ?></div>
                <div class="flow-list">
            <?php else: ?>
                <div class="flow-list">
            <?php endif; ?>
                    <?php foreach ($g['frames'] as $frame):
                        $short = '';
                        if (!empty($frame['class'])) {
                            $parts = explode('\\', $frame['class']);
                            $short = end($parts) . ($frame['type'] ?? '::') . $frame['function'] . '()';
                        } elseif (!empty($frame['function']) && $frame['function'] !== '{main}') {
                            $short = $frame['function'] . '()';
                        } elseif (!empty($frame['function'])) {
                            $short = $frame['function'];
                        }
                    ?>
                    <?php if ($isFirstFrame): ?>
                    <div class="flow-entry"><?= htmlspecialchars($frame['file'] ?? 'unknown') ?></div>
                    <?php $isFirstFrame = false; ?>
                    <?php else: ?>
                    <div class="flow-step <?= ($g['header'] ?? '') === 'MODULE' ? 'flow-step-module' : '' ?>">
                        <?= htmlspecialchars($short) ?>
                        <?php if (isset($frame['file'])): ?>
                        <span class="flow-location"> — <?= htmlspecialchars(basename($frame['file']) . ':' . ($frame['line'] ?? '?')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php if ($g['header'] !== null): ?>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="section-title">Full Stack Trace</div>
            <details>
                <summary>Show all frames</summary>
                <ol class="trace-list" style="margin-top:1rem;">
                    <?php foreach ($data['error']['trace'] as $index => $trace): ?>
                    <li class="trace-frame">
                        <div class="trace-call">
                            <span class="frame-num">#<?= $index + 1 ?></span>
                            <?php if (!empty($trace['class'])): ?>
                            <?= htmlspecialchars($trace['class'] . ($trace['type'] ?? '::') . $trace['function'] . '()') ?>
                            <?php else: ?>
                            <?= htmlspecialchars($trace['function'] ?? '{main}') ?>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($trace['file'])): ?>
                        <div class="trace-location"><?= htmlspecialchars($trace['file']) ?>:<?= $trace['line'] ?? '?' ?></div>
                        <?php endif; ?>
                        <?php if (!empty($trace['code_snippet'])): ?>
                        <details>
                            <summary>Show code</summary>
                            <div class="code-snippet"><?php foreach ($trace['code_snippet'] as $line => $code): ?>
                                <div class="code-line <?= $line === ($trace['line'] ?? -1) ? 'highlighted-line' : '' ?>">
                                    <span class="line-number"><?= $line ?></span>
                                    <span class="line-content"><?= htmlspecialchars($code) ?></span>
                                </div>
                            <?php endforeach; ?></div>
                        </details>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </details>
        </div>

        <div class="card">
            <div class="section-title">Request</div>
            <details>
                <summary>Show request details</summary>
                <div style="margin-top:0.75rem;font-family:var(--mono);font-size:0.8rem;">
                    <strong>Method:</strong> <?= $data['request']['method'] ?> &mdash;
                    <strong>URI:</strong> <?= htmlspecialchars($data['request']['uri']) ?>
                    <pre style="margin-top:0.5rem;"><?= htmlspecialchars(print_r($data['request']['headers'] ?? [], true)) ?></pre>
                </div>
            </details>
        </div>

    </div>
</body>
</html>
