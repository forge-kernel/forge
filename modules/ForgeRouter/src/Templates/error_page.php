<?php

declare(strict_types=1);

/** @var int $errorCode */
/** @var string $pageTitle */
/** @var string $errorMessage */

$errorCode = isset($errorCode) ? (int) $errorCode : 500;
$pageTitle = $pageTitle ?? 'Error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $errorCode ?> | <?= htmlspecialchars($pageTitle) ?></title>
    <style>
        html,
        body {
            margin: 0;
            height: 100%;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            color: #636b6f;
            font-family: -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .error {
            display: flex;
            align-items: center;
        }

        .code {
            font-size: 36px;
            font-weight: 300;
            line-height: 1;
        }

        .separator {
            width: 1px;
            height: 48px;
            background: #636b6f;
            margin: 0 18px;
            opacity: .7;
        }

        .message {
            font-size: 24px;
            font-weight: 300;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="error">
        <div class="code"><?= $errorCode ?></div>
        <div class="separator"></div>
        <div class="message"><?= htmlspecialchars($pageTitle) ?></div>
    </div>
</body>
</html>
