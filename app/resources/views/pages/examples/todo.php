<?php layout('main') ?>

<div fw:shared>
    <div <?= fw_id('todo-app') ?> class="container my-5" fw:depends="counter">
        <h1 class="text-3xl" fw:target>Reactive Todo List counter: <?= $counter ?></h1>

        <div class="p-4 shadow-sm card">
            <div class="mb-3 input-group">
                <input type="text" fw:model.defer="newTask" value="<?= e($newTask) ?>" class="form-control"
                    placeholder="What needs to be done?" fw:keydown.enter="addTodo">
                <p class="text-red-600" fw:validation-error="newTask"></p>
                <button class="btn btn-primary" fw:click="addTodo">Add Task</button>
            </div>

            <div fw:target>
                <ul class="list-group">
                    <?php foreach ($todos as $todo): ?>
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
                    <?php endforeach; ?>
                </ul>

                <?php if (empty($todos)): ?>
                    <p class="mt-3 text-muted">You're all caught up!</p>
                <?php endif; ?>
            </div>

            <div class="mt-3 text-muted">
                Tasks:
                <?= count($todos) ?> |
                Completed:
                <?= count(array_filter($todos, fn($t) => $t['done'])) ?>
            </div>

            <div fw:loading class="mt-2 text-info">
                Saving...
            </div>
        </div>
    </div>

    <div <?= fw_id('counter-app') ?> fw:depends="counter">
        <h1 class="text-3xl">Counter</h1>
        <button fw:click="increment">Increment</button>
        <button fw:click="decrement">Decrement</button>
        <div fw:target>
            <?= $counter ?>
        </div>
    </div>
</div>