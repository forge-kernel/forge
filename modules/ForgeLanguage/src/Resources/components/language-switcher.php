<?php

$current = current_language();
$languages = available_languages();
?>

<div class="forge-language-switcher">
    <?php foreach ($languages as $code => $language): ?>
        <a href="?lang=<?= $code ?>" class="<?= $current === $code
              ? 'active'
              : '' ?>">
            <?php if ($definition->showFlags): ?>
                <?= $language['flag'] ?>
            <?php endif; ?>

            <?php if ($definition->showLabels): ?>
                <?= $language['label'] ?>
            <?php endif; ?>

            <?php if ($definition->showCodes): ?>
                <?= strtoupper($code) ?>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>