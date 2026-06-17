<div class="grid gap-6">
  <?php if (empty($modules)): ?>
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No modules found</h3>
          <p class="mt-1 text-sm text-gray-500">No modules are currently installed.</p>
        </div>
  <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($modules as $module): ?>
                <div class="bg-white rounded-lg shadow-sm p-6">
                  <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                      <h2 class="text-xl font-medium text-gray-800 break-words"><?= htmlspecialchars($module['name']) ?></h2>
                      <p class="text-sm text-gray-500 mt-1 break-words"><?= htmlspecialchars($module['description']) ?></p>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <?= component(name: 'ForgeHub:badge', props: ['text' => $module['type'], 'type' => 'info']) ?>
                        <?= component(name: 'ForgeHub:badge', props: ['text' => 'v' . $module['version'], 'type' => 'default']) ?>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-sm">
                    <div>
                      <span class="text-gray-500">Author:</span>
                      <span class="ml-2 text-gray-900 font-medium"><?= htmlspecialchars($module['author']) ?></span>
                    </div>
                    <div>
                      <span class="text-gray-500">License:</span>
                      <span class="ml-2 text-gray-900 font-medium break-words"><?= htmlspecialchars($module['license']) ?></span>
                    </div>
                    <div class="md:col-span-2">
                      <span class="text-gray-500">Class:</span>
                      <code
                        class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded-lg font-mono text-gray-800 break-all"><?= htmlspecialchars($module['className']) ?></code>
                    </div>
                  </div>

                    <?php if (!empty($module['tags'])): ?>
                        <div class="mb-4">
                          <span class="text-sm text-gray-500 font-medium">Tags:</span>
                          <div class="flex flex-wrap gap-2 mt-2">
                              <?php foreach ($module['tags'] as $tag): ?>
                                  <span class="px-2 py-1 text-xs rounded-lg bg-gray-100 text-gray-700 font-medium">
                                      <?= htmlspecialchars($tag) ?>
                                  </span>
                              <?php endforeach; ?>
                          </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($module['hubItems'])): ?>
                        <div class="border-t border-gray-200 pt-4">
                          <h3 class="text-sm font-medium text-gray-700 mb-3">HubItems</h3>
                          <ul class="space-y-2">
                              <?php foreach ($module['hubItems'] as $item): ?>
                                  <li class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <a href="<?= htmlspecialchars($item['route']) ?>" class="text-blue-600 hover:text-blue-800 font-medium break-words">
                                        <?= htmlspecialchars($item['label']) ?>
                                    </a>
                                    <span class="text-gray-400 text-xs break-all">(<?= htmlspecialchars($item['route']) ?>)</span>
                                  </li>
                              <?php endforeach; ?>
                          </ul>
                        </div>
                    <?php else: ?>
                        <div class="border-t border-gray-200 pt-4">
                          <p class="text-sm text-gray-500 italic">No HubItems registered</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
  <?php endif; ?>
</div>
