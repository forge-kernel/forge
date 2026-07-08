<?php
use Modules\ForgeLanguage\Definitions\LanguageSwitcherDefinition;

/**
@var LanguageSwitcherDefinition $definition
@var array $data
*/
$layoutProps = ['title' => $data['title']];
?>
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
          </path>
        </svg>
      </div>
      <h1 class="text-4xl font-bold text-gray-900 mb-2"><?= languageTerm('welcome', 'Forge Kernel') ?></h1>
      <p class="text-lg text-gray-600"><?= languageTerm('description', 'Welcome to your development playground') ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <button hx-post="/languages" hx-target="#language-switcher">Htmx language</button>
        <div id="language-switcher"></div>
      <!-- Main Content -->
      <?= component(
          name: 'ForgeLanguage:language-switcher',
          props: [
              'definition' => new LanguageSwitcherDefinition(
                  showFlags: true,
                  showLabels: true,
                  showCodes: false,
              )
          ]
      ) ?>
    </div>
  </div>
</div>
