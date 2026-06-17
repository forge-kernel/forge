<?php

use Forge\Core\Helpers\Format;
?>
<div class="container mt-sm">
    <h1 class="mb-sm">Framework Metrics</h1>

    <!-- Metrics Table -->
    <h2 class="mt-lg mb-sm">Metrics</h2>
    <table class="metrics-table">
        <thead>
            <tr>
                <th>Key</th>
                <th>Duration (sec)</th>
                <th>Memory Used</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['metrics'] as $key => $metric): ?>
                    <tr>
                        <td><?= $key ?></td>
                        <td><?= number_format($metric['duration'] ?? 0, 5) ?></td>
                        <td><?= Format::fileSize(($metric['memory_used'] ?? 0)) ?></td>
                    </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
