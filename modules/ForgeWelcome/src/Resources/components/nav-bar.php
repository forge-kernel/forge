<?php

/**
 * NavBar component — renders a horizontal navigation list.
 *
 * Props:
 *   links — array of { url: string, label: string }
 *
 * @var array{links: array<array{url: string, label: string}>} $props
 */

$links = $props['links'] ?? [
    ['url' => 'https://forge-kernel.github.io/', 'label' => 'Documentation'],
    ['url' => 'https://github.com/forge-kernel/kernel-module-registry', 'label' => 'Modules'],
    ['url' => 'https://github.com/forge-kenel/forge', 'label' => 'Forge'],
    ['url' => 'https://github.com/forge-kernel', 'label' => 'GitHub'],
];
?>
<nav>
    <ul class="forge-links-list">
        <?php foreach ($links as $link): ?>
                    <li><a href="<?= $link['url'] ?>"><?= $link['label'] ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
