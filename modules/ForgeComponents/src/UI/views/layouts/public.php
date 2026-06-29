<?php
/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 * @var string $parentLayout
 */
use Modules\ForgeComponents\Definitions\NavbarDefinition;
use Modules\ForgeComponents\Definitions\FooterDefinition;

$parentLayout = 'ForgeComponents:root';

$layoutProps = array_merge($layoutProps ?? [], [
    'bodyClass' => 'fc-public',
]);

$layoutSections = array_merge($layoutSections ?? [], [
    'head_end' => ($layoutSections['head_end'] ?? '') .
        "\n" . '<link rel="stylesheet" href="/assets/modules/forge-components/css/forge-components/_public.css">',
]);

$navbar = $layoutProps['navbar'] ?? null;
$footer = $layoutProps['footer'] ?? null;
?>
<?php if ($navbar instanceof NavbarDefinition): ?>
  <?= component(name: 'ForgeComponents:navbar', props: $navbar) ?>
<?php endif; ?>

<main class="fc-public__main">
  <?= $content ?>
</main>

<?php if ($footer instanceof FooterDefinition): ?>
  <?= component(name: 'ForgeComponents:footer', props: $footer) ?>
<?php endif; ?>
