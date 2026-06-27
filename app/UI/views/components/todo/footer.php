<div class="mt-3 text-muted">
    Tasks:
    <?= count($todos) ?> |
    Completed:
    <?= count(array_filter($todos, fn($t) => $t['done'])) ?>
</div>
