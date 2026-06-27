<?php

/**
 * Welcome layout — landing page shell.
 * Inherits from ForgeWelcome:root for the HTML skeleton.
 *
 * Features demonstrated:
 *   - $parentLayout for layout inheritance
 *   - $layoutSlots for placing header / footer content
 *   - $layoutProps['bodyClass'] cascaded to root
 *
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$parentLayout = 'ForgeWelcome:root';

$layoutProps = array_merge($layoutProps ?? [], [
    'bodyClass' => 'forge-welcome-body',
]);
?>
<div class="main">
    <div class="layout-wrapper">
        <div class="landing-wrapper">
            <div class="landing-container">
                <?php if (isset($layoutSlots['header'])): ?>
                    <?= $layoutSlots['header'] ?>
                <?php endif; ?>

                <?= $content ?>

                <?php if (isset($layoutSlots['footer'])): ?>
                    <?= $layoutSlots['footer'] ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
