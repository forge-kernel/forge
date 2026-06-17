<div <?= fw_id('counter-app') ?> fw:depends="counter">
    <h1 class="text-3xl">Counter</h1>
    <button fw:click="increment">Increment</button>
    <button fw:click="decrement">Decrement</button>
    <div fw:target>
        <?= $counter ?>
    </div>
</div>