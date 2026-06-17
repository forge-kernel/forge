<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Forge Application</title>
    <style>
    /* /modules/forge-error-handler/error.css - Light Theme */
    :root {
        --bg-light: #e6e7eb;
        --text-light: #444444;
        --bg-dark: #1a1a1a;
        --text-dark: #e0e0e0;
        --primary: #212936;
        --secondary: #4f46e5;
        --border: rgba(0, 0, 0, 0.1);
        --radius: 8px;
        --gap: 1.5rem;
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
            "Liberation Mono", "Courier New", monospace;
    }

    [data-theme="dark"] {
        --bg: var(--bg-dark);
        --text: var(--text-dark);
        --border: rgba(255, 255, 255, 0.1);
    }

    [data-theme="light"] {
        --bg: var(--bg-light);
        --text: var(--text-light);
    }

    * {
        box-sizing: border-box;
        margin: 0;
    }

    body {
        background: var(--bg);
        color: var(--text);
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
            Oxygen, Ubuntu, Cantarell, sans-serif;
        line-height: 1.5;
        min-height: 100vh;
        padding: 2rem;
    }

    .error-container {
        display: flex;
        flex-direction: column;
        max-width: 90vw;
        margin: 0 auto;
        background: var(--bg);
    }

    .error-header {
        padding-bottom: var(--gap);
        margin-bottom: var(--gap);
        background-color: white;
        display: flex;
        padding: 2rem;
        box-shadow: 4px 2px 5px 0px #cdced1;
        width: 100%;
    }

    .error-header .left,
    .error-header .right {
        flex-direction: column;
        width: 50%;
    }

    .error-header .right {
        text-align: right;
        justify-items: end;
        justify-content: center;
        align-content: end !important;
    }

    .layout {
        display: flex;
        flex: 1;
        box-shadow: 4px 2px 5px 0px #cdced1;
        background-color: white;
    }

    .file-list {
        width: 30%;
        background: white;
        padding: 1rem;
        border-right: 1px solid var(--border);
        position: sticky;
        top: 0;
        overflow-y: auto;
    }

    .file-nav {
        display: flex;
        flex-direction: column;
    }

    .file-button {
        background: none;
        border: none;
        padding: 0.75rem 1rem;
        cursor: pointer;
        color: var(--text);
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    .file-button.active,
    .file-button:hover {
        background-color: var(--primary);
        ;
        color: white;
    }

    .main-content {
        flex: 1;
        margin-left: 1rem;
        overflow-y: auto;
        padding: 0.8rem;
    }

    .stack-trace-container {
        display: grid;
        gap: 1rem;
    }

    .stack-trace-item {
        display: none;
        background: white;
        padding: 1rem;
        font-family: var(--font-mono);
        font-size: 0.85em;
        transition: transform 0.1s ease;
    }

    .stack-trace-item.active {
        display: block;
    }

    .trace-header {
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .trace-file {
        color: #666;
        font-size: 0.9em;
        margin-bottom: 0.5rem;
    }

    .error-title {
        font-size: 1.75rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .error-meta {
        color: #666;
        font-size: 0.9em;
    }

    .error-file {
        display: block;
        margin-top: 0.5rem;
        font-family: var(--font-mono);
        font-size: 0.85em;
        color: var(--primary);
    }

    .stack-trace {
        display: grid;
        gap: 1rem;
        margin: var(--gap) 0;
    }

    .trace-item {
        background: white;
        border: 1px solid var(--border);
        padding: 1rem;
        font-family: var(--font-mono);
        font-size: 0.85em;
        transition: transform 0.1s ease;
    }

    [data-theme="dark"] .trace-item {
        background: rgba(255, 255, 255, 0.03);
    }

    .trace-item:hover {
        transform: translateX(2px);
    }

    .trace-header {
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .trace-file {
        color: #666;
        font-size: 0.9em;
        margin-bottom: 0.5rem;
    }

    .code-snippet {
        background: rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border);
        border-radius: calc(var(--radius) - 2px);
        padding: 1rem;
        overflow-x: auto;
        font-size: 1.2em;
        white-space: pre;
    }

    .code-line {
        display: table-row;
    }

    .line-number {
        display: table-cell;
        text-align: right;
        padding-right: 1em;
        user-select: none;
        opacity: 0.6;
    }

    .line-content {
        display: table-cell;
    }

    .highlighted-line {
        background: rgba(255, 0, 0, 0.1);
        border-left: 2px solid var(--primary);
        ;
        margin: 0 -1rem;
        padding: 0 1rem;
    }

    .tab-nav {
        display: flex;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border);
        margin: var(--gap) 0;
    }

    .tab-button {
        background: none;
        border: none;
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        color: var(--text);
        position: relative;
        border-radius: var(--radius) var(--radius) 0 0;
    }

    .tab-button.active {
        background: rgba(var(--primary), 0.1);
        color: var(--primary);
    }

    .tab-button.active::after {
        content: "";
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--primary);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .environment-badge {
        top: 1rem;
        right: 1rem;
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
        background: var(--bg-light);
        border: 1px solid var(--border);
        min-height: 100px;
    }

    .exception-tag {
        font-weight: bold;
        color: var(--primary);
        margin-bottom: 4px;
    }

    .exception-link {
        background-color: #e6e6e6;
        padding: 0.5rem 1rem;
        text-decoration: none;
        color: inherit;
        border-radius: 4px;
    }

    .exception-link:hover {
        background-color: #b0b0b0;
    }

    .php-version {
        margin-left: auto;
        font-size: 0.9em;
        color: #666;
    }

    .request-details {
        width: 100%;
        position: static;
        height: auto;
        border-right: none;
        background-color: white;
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .error-title {
            font-size: 1.5rem;
        }

        .tab-nav {
            overflow-x: auto;
        }

        .layout {
            flex-direction: column;
            box-shadow: 4px 2px 5px 0px #cdced1;
            background-color: white;
        }

        .file-list {
            width: 100%;
            position: static;
            height: auto;
            border-right: none;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        .request-details {
            width: 100%;
            position: static;
            height: auto;
            border-right: none;
            border-bottom: 1px solid var(--border);
        }

        .main-content {
            margin: 0;
            width: 100%;
        }

        .stack-trace-item {
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-header">
            <div class="left">
                <div class="exception-tag">
                    <a href="#" class="exception-link"><?= $data['error']['type'] ?></a>
                </div>
                <h1 class="error-title">
                    <span class="error-code"><?= $data['error']['message'] ?></span>
                </h1>
                <p class="error-meta">
                    <strong>File:</strong> <?= $data['error']['file'] ?>
                    <strong>Line:</strong> <?= $data['error']['line'] ?>
                </p>
            </div>
            <div class="right">
                <?php if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] !== 'production'): ?>
                <div>
                    <div class="environment-badge">
                        <?= strtoupper($_ENV['APP_ENV'] ?? 'DEBUG') ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="php-version">PHP <?= phpversion() ?></div>
            </div>
        </div>

        <div class="layout">
            <aside class="file-list">
                <nav class="file-nav">
                    <?php foreach ($data['error']['trace'] as $index => $trace): ?>
                    <button class="file-button <?= $index === 0 ? 'active' : '' ?>" data-target="trace-<?= $index ?>">
                        <?= $trace['function'] ?>
                        <?php if (isset($trace['file'])): ?>
                        <?= $trace['file'] !== null ? basename($trace['file']) : '' ?>:<?= $trace['line'] ?? '?' ?>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <div class="main-content">
                <div class="stack-trace-container">
                    <?php foreach ($data['error']['trace'] as $index => $trace): ?>
                    <div id="trace-<?= $index ?>" class="stack-trace-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="trace-header">
                            #<?= $index + 1 ?> <?= $trace['function'] ?>
                        </div>
                        <?php if (isset($trace['file'])): ?>
                        <div class="trace-file">
                            <?= $trace['file'] ?>:<?= $trace['line'] ?? '?' ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($trace['code_snippet'])): ?>
                        <pre class="code-snippet"><?php foreach ($trace['code_snippet'] as $line => $code): ?>
									<div
										class="code-line <?= $line === ($trace['line'] ?? -1) ? 'highlighted-line' : '' ?>">
										<span class="line-number"><?= $line ?></span>
										<span class="line-content"><?= $code ?></span>
									</div>
								<?php endforeach; ?></pre>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="request-details">
                    <nav class="tab-nav">
                        <button class="tab-button active" data-target="headers">Headers</button>
                        <button class="tab-button" data-target="parameters">Parameters</button>
                        <button class="tab-button" data-target="session">Session</button>
                    </nav>

                    <div id="headers" class="tab-content active">
                        <pre class="code-snippet"><?= print_r($data['request']['headers'] ?? [], true)?></pre>
                    </div>
                    <div id="parameters" class="tab-content">
                        <pre class="code-snippet"><?= print_r($data['request']['parameters'] ?? [], true) ?></pre>
                    </div>
                    <div id="session" class="tab-content">
                        <?php if (!empty($data['session'])): ?>
                        <pre class="code-snippet"><?= print_r($data['session'], true) ?></pre>
                        <?php else: ?>
                        <div class="code-snippet">No active session</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.file-button').forEach(button => {
            button.addEventListener('click', function() {
                const targetTrace = document.getElementById(button.dataset.target);
                document.querySelectorAll('.file-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.stack-trace-item').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                targetTrace.classList.add('active');
            });
        });

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = document.getElementById(button.dataset.target);
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                targetTab.classList.add('active');
            });
        });
    });
    </script>
</body>

</html>