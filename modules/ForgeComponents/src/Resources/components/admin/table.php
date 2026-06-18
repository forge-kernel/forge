<?php
use App\Modules\ForgeComponents\Definitions\Admin\TableDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\TableColumnDefinition;
/** @var array<string, mixed> $props */
$columns = $props['columns'] ?? [];
$rows = $props['rows'] ?? [];
$emptyMessage = $props['emptyMessage'] ?? 'No data available';
?>
<?php if (empty($columns)): ?>
<?php return; ?>
<?php endif; ?>
<div class="fc-table-wrapper">
  <table class="fc-table">
    <thead>
      <tr>
        <?php foreach ($columns as $column): ?>
          <th class="fc-table__th<?= $column['sortable'] ?? false ? ' fc-table__th--sortable' : '' ?>">
            <?= e($column['label'] ?? '') ?>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td class="fc-table__empty" colspan="<?= count($columns) ?>"><?= e($emptyMessage) ?></td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr class="fc-table__row">
            <?php foreach ($columns as $column):
              $key = $column['key'] ?? '';
              $value = $row[$key] ?? '';
            ?>
              <td class="fc-table__td"><?= is_string($value) ? e($value) : (string) $value ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
