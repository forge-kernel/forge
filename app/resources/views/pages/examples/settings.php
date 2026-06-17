<?php layout('main') ?>

<div <?= fw_id('settings-form') ?> class="container my-5" style="max-width: 600px;">
    <h1>Account Settings</h1>
    <p class="text-muted">Demonstrates multi-field state sync and actions.</p>

    <div class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" fw:model.defer="username" value="<?= e($username) ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" fw:model.defer="email" value="<?= e($email) ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Bio</label>
            <textarea fw:model.defer="bio" class="form-control"><?= e($bio) ?></textarea>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" fw:model.defer="marketing" id="marketing" <?= $marketing ? 'checked' : '' ?>
                class="form-check-input">
            <label class="form-check-label" for="marketing">Receive marketing emails</label>
        </div>

        <div fw:target class="mb-3">
            <?php if ($message): ?>
                <div class="alert <?= str_contains($message, 'Error') ? 'alert-danger' : 'alert-success' ?>">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-between">
            <button class="btn btn-secondary" fw:click="reset">Reset to Defaults</button>
            <button class="btn btn-primary" fw:click="save">Save Changes</button>
        </div>

        <div fw:loading class="text-center mt-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            Processsing...
        </div>
    </div>
</div>