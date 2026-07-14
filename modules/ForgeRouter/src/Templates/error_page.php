<?php

declare(strict_types=1);

/** @var int $errorCode */
/** @var string $pageTitle */
/** @var string $errorMessage */

$errorCode = isset($errorCode) ? (int) $errorCode : 500;
$pageTitle = $pageTitle ?? 'Error';
$errorMessage = $errorMessage ?? 'An unexpected error has occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $errorCode ?> <?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #fafafa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .error-page {
            text-align: center;
            max-width: 500px;
        }

        .error-code {
            font-size: 7rem;
            font-weight: 700;
            color: #e5e7eb;
            line-height: 1;
            letter-spacing: -0.05em;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111;
            margin: 1rem 0 0.5rem;
        }

        .error-message {
            font-size: 1rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 0.375rem;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #111;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #333;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-code"><?= $errorCode ?></div>
        <h1 class="error-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="error-message"><?= htmlspecialchars($errorMessage) ?></p>
        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-primary">Go Back</a>
            <a href="/" class="btn btn-secondary">Homepage</a>
        </div>
    </div>
</body>
</html>
