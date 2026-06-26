<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <input type="checkbox" <?= $todo['done'] ? 'checked' : '' ?> fw:click="toggleTodo"
            fw:param-id="<?= $todo['id'] ?>" class="form-check-input me-2">
        <span style="<?= $todo['done'] ? 'text-decoration: line-through;' : '' ?>">
            <?= e($todo['text']) ?>
        </span>
    </div>
    <button class="btn btn-sm btn-danger" fw:click="removeTodo" fw:param-id="<?= $todo['id'] ?>">
        &times;
    </button>
</li>
