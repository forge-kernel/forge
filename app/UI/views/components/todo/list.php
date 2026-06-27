<div fw:target>
    <ul class="list-group">
        <?php foreach ($todos as $todo): ?>
               <?= component(name: 'todo/item', props: ['todo' => $todo]) ?>
        <?php endforeach; ?>
    </ul>
    <?= component(name: 'todo/empty', props: ['todos' => $todos]) ?>
</div>
