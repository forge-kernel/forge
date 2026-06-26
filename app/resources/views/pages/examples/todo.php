<div fw:shared>
    <?= component(name: 'todo/app', props: ['todos' => $todos, 'counter' => $counter, 'newTask' => $newTask]) ?>
    <?= component(name: 'todo/counter', props: ['counter' => $counter]) ?>
</div>
