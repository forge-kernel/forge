<?php

use Forge\Core\Helpers\Flash;
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Profile</h1>
    <p class="text-sm text-gray-500 mt-1">Manage your personal information</p>
  </div>
  <?= component('ForgeComponents:alert') ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
        <form method="POST" action="/hub/profile" class="space-y-6">
          <?= raw(csrf_input()) ?>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="identifier" class="block text-sm font-medium text-gray-700 mb-2">
                Username
              </label>
              <?= component(name: 'ForgeHub:input', props: [
                  'id' => 'identifier',
                  'name' => 'identifier',
                  'type' => 'text',
                  'value' => $user->identifier ?? '',
                  'required' => true
              ]) ?>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email
              </label>
              <?= component(name: 'ForgeHub:input', props: [
                  'id' => 'email',
                  'name' => 'email',
                  'type' => 'email',
                  'value' => $user->email ?? '',
                  'required' => true
              ]) ?>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                First Name
              </label>
              <?= component(name: 'ForgeHub:input', props: [
                  'id' => 'first_name',
                  'name' => 'first_name',
                  'type' => 'text',
                  'value' => $profile?->first_name ?? ''
              ]) ?>
            </div>

            <div>
              <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                Last Name
              </label>
              <?= component(name: 'ForgeHub:input', props: [
                  'id' => 'last_name',
                  'name' => 'last_name',
                  'type' => 'text',
                  'value' => $profile?->last_name ?? ''
              ]) ?>
            </div>
          </div>

          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
              Phone
            </label>
            <?= component(name: 'ForgeHub:input', props: [
                'id' => 'phone',
                'name' => 'phone',
                'type' => 'tel',
                'value' => $profile?->phone ?? ''
            ]) ?>
          </div>

          <div class="flex justify-end">
            <?= component(name: 'ForgeHub:button', props: [
                'type' => 'submit',
                'variant' => 'primary',
                'children' => 'Save Changes'
            ]) ?>
          </div>
        </form>
      </div>
    </div>

    <div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h3>
        <div class="space-y-4">
          <div>
            <p class="text-sm text-gray-500">User ID</p>
            <p class="text-sm font-medium text-gray-900 mt-1"><?= htmlspecialchars((string) ($user->id ?? '')) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Status</p>
            <?php
            $status = $user->status ?? 'unknown';
            $statusColors = [
                'active' => 'bg-green-100 text-green-800',
                'inactive' => 'bg-gray-100 text-gray-800',
                'suspended' => 'bg-red-100 text-red-800',
            ];
            $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
            ?>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?> mt-1">
              <?= htmlspecialchars(ucfirst($status)) ?>
            </span>
          </div>
          <?php if (isset($user->created_at)): ?>
                    <div>
                      <p class="text-sm text-gray-500">Member Since</p>
                      <p class="text-sm font-medium text-gray-900 mt-1">
                        <?= htmlspecialchars($user->created_at->format('F j, Y')) ?>
                      </p>
                    </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
