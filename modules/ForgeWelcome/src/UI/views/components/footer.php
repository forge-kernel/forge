<?php

/**
 * Footer component — renders the page footer text.
 *
 * Props:
 *   text — the footer message string
 *
 * @var array{text: string} $props
 */

$text = $props['text'] ?? 'Forge - A PHP Kernel for Builders.';
?>
<p class="forge-note"><?= $text ?></p>
