<?php /** @var int $counter */ ?>
<div <?= scope('todo-app') ?> class="container my-5" fw:depends="counter">
    <h1 class="text-3xl" fw:target>Reactive Todo List counter: <?= $counter ?></h1>
    <div class="p-4 shadow-sm card">
        <?= component(name: 'todo/create-form', props: ['newTask' => $newTask]) ?>
        <?= component(name: 'todo/list', props: ['todos' => $todos]) ?>
        <?= component(name: 'todo/footer', props: ['todos' => $todos]) ?>
        <?= component(name: 'todo/loader') ?>
    </div>
</div>
