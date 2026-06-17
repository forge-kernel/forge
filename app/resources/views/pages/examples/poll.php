<?php layout('main') ?>

<div <?= fw_id('poll-component') ?> class="container my-5">
    <h1>Community Poll</h1>
    <p>What is your favorite language for Forge?</p>

    <div class="card p-4 shadow-sm" style="max-width: 400px;">
        <div fw:target>
            <?php if (!$hasVoted): ?>
                <div class="d-grid gap-2">
                    <?php foreach ($votes as $lang => $count): ?>
                        <button class="btn btn-outline-dark" fw:click="vote" fw:param-lang="<?= $lang ?>">
                            <?= $lang ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <h4>Results:</h4>
                <?php foreach ($votes as $lang => $count): ?>
                    <?php
                    $percent = $total > 0 ? round(($count / $total) * 100) : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>
                                <?= $lang ?>
                            </span>
                            <span>
                                <?= $percent ?>% (
                                <?= $count ?>)
                            </span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <p class="text-center text-muted mt-3">Total votes:
                    <?= $total ?>
                </p>
                <div class="alert alert-info py-2 text-center">Thanks for voting!</div>
            <?php endif; ?>
        </div>

        <div fw:loading class="text-center mt-2">
            Recording vote...
        </div>
    </div>
</div>