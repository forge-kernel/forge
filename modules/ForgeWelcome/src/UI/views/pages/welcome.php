<?php

/**
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Welcome To Forge Kernel',
];

$layoutSlots = [
    'header' => component(name: 'ForgeWelcome:nav-bar', props: [
        'links' => [
            ['url' => 'https://forge-kernel.github.io/', 'label' => 'Documentation'],
            ['url' => 'https://github.com/forge-kernel/kernel-module-registry', 'label' => 'Modules'],
            ['url' => 'https://github.com/forge-kernel/forge', 'label' => 'Forge'],
            ['url' => 'https://github.com/forge-kernel', 'label' => 'GitHub'],
        ],
    ]),
    'footer' => component(name: 'ForgeWelcome:footer', props: [
        'text' => 'Forge - A PHP Kernel for Builders.',
    ]),
];
?>
<h1>
    <p class="forge-logo">Forge</p>
</h1>

<p class="forge-welcome-text">
    You've successfully installed the core of your PHP application kernel. <br />
    Forge provides a small, dependency-free foundation — everything else is up to you.
</p>

<p class="forge-welcome-text">
    You can build your application in different ways:<br />
    • Create your entire app inside the /app directory.<br />
    • Build features as self-contained modules.<br />
    • Or structure the whole application itself as a set of modules.
</p>

<p class="forge-welcome-text">
    Modules can contain routes, controllers, views, services, commands, and configuration.<br />
    They can be enabled, disabled, upgraded, or versioned independently.<br />
    There's no required architecture here.<br />
    Use what you need, ignore what you don't, and shape the system around your project.
</p>

<p class="forge-welcome-text">
    This page is composed from several files within the ForgeWelcome module.<br />
    The view declares layoutSlots and layoutProps, the main layout inherits from root.php, and the nav-bar and footer components receive typed props.
</p>

<ul class="forge-file-list">
    <li><span class="forge-file-label">Route:</span> Controllers/WelcomeController.php</li>
    <li><span class="forge-file-label">Layout:</span> UI/views/layouts/main.php, UI/views/layouts/root.php</li>
    <li><span class="forge-file-label">View:</span> UI/views/pages/index.php</li>
    <li><span class="forge-file-label">Components:</span> UI/views/components/nav-bar.php, UI/views/components/footer.php</li>
</ul>
