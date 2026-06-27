<div class="mb-3 input-group">
    <input type="text" fw:model.defer="newTask" value="<?= e($newTask) ?>" class="form-control"
        placeholder="What needs to be done?" fw:keydown.enter="addTodo">
    <p class="text-red-600" fw:validation-error="newTask"></p>
    <button class="btn btn-primary" fw:click="addTodo">Add Task</button>
</div>
