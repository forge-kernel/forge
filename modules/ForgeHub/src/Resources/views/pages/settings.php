<?php

use Forge\Core\Helpers\Flash;
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
    <p class="text-sm text-gray-500 mt-1">Manage your account settings</p>
  </div>

  <?= component('ForgeComponents:alert') ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
      <form method="POST" action="/hub/settings/password" class="space-y-6">
        <?= raw(csrf_input()) ?>

        <div>
          <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
            Current Password
          </label>
          <?= component(name: 'ForgeHub:input', props: [
              'id' => 'current_password',
              'name' => 'current_password',
              'type' => 'password',
              'required' => true
          ]) ?>
        </div>

        <div>
          <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
            New Password
          </label>
          <?= component(name: 'ForgeHub:input', props: [
              'id' => 'new_password',
              'name' => 'new_password',
              'type' => 'password',
              'required' => true
          ]) ?>
          <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters</p>
        </div>

        <div>
          <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
            Confirm New Password
          </label>
          <?= component(name: 'ForgeHub:input', props: [
              'id' => 'confirm_password',
              'name' => 'confirm_password',
              'type' => 'password',
              'required' => true
          ]) ?>
        </div>

        <div class="flex justify-end">
          <?= component(name: 'ForgeHub:button', props: [
              'type' => 'submit',
              'variant' => 'primary',
              'children' => 'Update Password'
          ]) ?>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Preferences</h3>
      <div class="space-y-4">
        <div>
          <p class="text-sm text-gray-500 mb-2">Email Notifications</p>
          <p class="text-sm text-gray-700">Manage your email notification preferences</p>
          <p class="text-xs text-gray-500 mt-1">Coming soon</p>
        </div>
        <div class="border-t border-gray-200 pt-4">
          <p class="text-sm text-gray-500 mb-2">Two-Factor Authentication</p>
          <p class="text-sm text-gray-700">Add an extra layer of security to your account</p>
          <p class="text-xs text-gray-500 mt-1">Coming soon</p>
        </div>
      </div>
    </div>
  </div>
</div>
