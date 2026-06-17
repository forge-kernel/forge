<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

use DateTimeInterface;

final class PostgreSqlFormatter implements FormatterInterface
{
    public bool $skipForeignKeys = false;
    private array $relationships = [];

    public function formatColumn(string $name, array $attributes): string
    {
        $dbType = $this->formatType($attributes);

        $parts = [
            "\"$name\"",
            $dbType,
            $attributes['nullable'] ? 'NULL' : 'NOT NULL',
            $this->getPrimaryKeyClause($attributes),
            $attributes['unique'] ? 'UNIQUE' : '',
            isset($attributes['check']) ? "CHECK ({$attributes['check']})" : '',
            isset($attributes['default']) ?
                $this->formatDefault($attributes['default']) : ''
        ];

        $definition = implode(' ', array_filter($parts));

        // PostgreSQL uses COMMENT ON statement separately
        if (isset($attributes['comment'])) {
            $this->columnComments[] = [
                'column' => $name,
                'comment' => $attributes['comment']
            ];
        }

        return $definition;
    }

    private array $columnComments = [];

    private function formatType(array $attributes): string
    {
        $type = $attributes['type'];

        return match ($type) {
            'UUID' => 'UUID',
            'STRING' => isset($attributes['length']) ? "VARCHAR({$attributes['length']})" : 'VARCHAR(255)',
            'TEXT' => 'TEXT',
            'INTEGER' => $this->formatInteger($attributes),
            'BOOLEAN' => 'BOOLEAN',
            'FLOAT' => 'REAL',
            'DECIMAL' => $this->formatDecimal($attributes),
            'DATE' => 'DATE',
            'DATETIME' => 'TIMESTAMP',
            'TIMESTAMP' => 'TIMESTAMP',
            'ENUM' => $this->formatEnum($attributes),
            'JSON' => 'JSONB',
            'BLOB' => 'BYTEA',
            'ARRAY' => 'TEXT[]',
            default => $type
        };
    }

    private function formatInteger(array $attributes): string
    {
        return ($attributes['unsigned'] ?? false) ? 'INTEGER' : 'INTEGER';
    }

    private function formatDecimal(array $attributes): string
    {
        $precision = $attributes['precision'] ?? 10;
        $scale = $attributes['scale'] ?? 2;
        return "DECIMAL($precision, $scale)";
    }

    private function formatEnum(array $attributes): string
    {
        if ($attributes['type'] !== 'ENUM' || empty($attributes['enum'])) {
            return 'VARCHAR(255)';
        }

        $values = array_map(fn($v) => "'$v'", $attributes['enum']);
        return 'VARCHAR(255)';
    }

    private function getPrimaryKeyClause(array $attributes): string
    {
        if (!$attributes['primary']) {
            return '';
        }

        return 'PRIMARY KEY';
    }

    private function formatDefault(mixed $value): string
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        if (is_bool($value)) {
            return 'DEFAULT ' . ($value ? 'TRUE' : 'FALSE');
        }

        if (is_string($value)) {
            return "DEFAULT '$value'";
        }

        if ($value instanceof DateTimeInterface) {
            return "DEFAULT '" . $value->format('Y-m-d H:i:s') . "'";
        }

        return "DEFAULT $value";
    }

    public function resetRelationships(): void
    {
        $this->relationships = [];
    }

    public function formatIndex(array $index): string
    {
        $columns = array_map(fn($col) => "\"$col\"", $index['columns']);
        return sprintf(
            'CREATE %sINDEX IF NOT EXISTS "%s" ON "%s" (%s)',
            $index['unique'] ? 'UNIQUE ' : '',
            $index['name'],
            $index['table'],
            implode(', ', $columns)
        );
    }

    public function formatTableOptions(): string
    {
        return '';
    }

    public function addRelationship(string $type, array $config): void
    {
        $this->relationships[] = compact('type', 'config');
    }

    public function formatRelationships(string $table): string
    {
        if ($this->skipForeignKeys ?? false) {
            return '';
        }

        $sql = implode(";\n", array_map(
            fn($rel) => match ($rel['type']) {
                'belongsTo' => $this->formatBelongsTo($table, $rel['config']),
                'hasOne' => $this->formatHasOneOrHasMany($rel['config']),
                'hasMany' => $this->formatHasOneOrHasMany($rel['config']),
                'manyToMany' => $this->formatManyToMany($rel['config']),
                default => ''
            },
            $this->relationships
        ));

        // Add column comments
        if (!empty($this->columnComments)) {
            $commentSql = [];
            foreach ($this->columnComments as $comment) {
                $commentSql[] = sprintf(
                    'COMMENT ON COLUMN "%s"."%s" IS \'%s\'',
                    $table,
                    $comment['column'],
                    $comment['comment']
                );
            }
            $sql .= ";\n" . implode(";\n", $commentSql);
            $this->columnComments = [];
        }

        return $sql;
    }

    private function formatBelongsTo(string $table, array $config): string
    {
        return sprintf(
            'ALTER TABLE "%s" ADD FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE %s',
            $table,
            $config['foreignKey'],
            $config['relatedTable'],
            $config['onDelete']
        );
    }

    private function formatHasOneOrHasMany(array $config): string
    {
        return sprintf(
            'ALTER TABLE "%s" ADD FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE %s',
            $config['relatedTable'],
            $config['foreignKey'],
            $config['sourceTable'],
            $config['onDelete']
        );
    }

    private function formatManyToMany(array $config): string
    {
        return sprintf(
            'CREATE TABLE "%s" (
                "%s" INTEGER NOT NULL,
                "%s" INTEGER NOT NULL,
                PRIMARY KEY ("%s", "%s"),
                FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE CASCADE,
                FOREIGN KEY ("%s") REFERENCES "%s"(id) ON DELETE CASCADE
            )',
            $config['joinTable'],
            $config['foreignKey'],
            $config['relatedKey'],
            $config['foreignKey'],
            $config['relatedKey'],
            $config['foreignKey'],
            $config['sourceTable'],
            $config['relatedKey'],
            $config['relatedTable']
        );
    }

    public function formatAddColumn(string $table, string $column, array $attributes, ?string $after = null, bool $first = false): string
    {
        $columnDef = $this->formatColumn($column, $attributes);
        
        // PostgreSQL doesn't support AFTER/FIRST, column is always added at the end
        return sprintf(
            'ALTER TABLE "%s" ADD COLUMN %s',
            $table,
            $columnDef
        );
    }

    public function formatDropColumn(string $table, string $column): string
    {
        return sprintf(
            'ALTER TABLE "%s" DROP COLUMN "%s"',
            $table,
            $column
        );
    }

    public function formatRenameColumn(string $table, string $old, string $new): string
    {
        return sprintf(
            'ALTER TABLE "%s" RENAME COLUMN "%s" TO "%s"',
            $table,
            $old,
            $new
        );
    }

    public function formatAlterColumn(string $table, string $column, array $attributes): string
    {
        $parts = [];
        
        // Build ALTER COLUMN statements for each changed property
        if (isset($attributes['type'])) {
            $dbType = $this->formatType($attributes);
            $parts[] = sprintf('ALTER COLUMN "%s" SET DATA TYPE %s', $column, $dbType);
        }
        
        if (array_key_exists('nullable', $attributes)) {
            if ($attributes['nullable']) {
                $parts[] = sprintf('ALTER COLUMN "%s" DROP NOT NULL', $column);
            } else {
                $parts[] = sprintf('ALTER COLUMN "%s" SET NOT NULL', $column);
            }
        }
        
        if (array_key_exists('default', $attributes)) {
            if ($attributes['default'] === null) {
                $parts[] = sprintf('ALTER COLUMN "%s" DROP DEFAULT', $column);
            } else {
                $parts[] = sprintf('ALTER COLUMN "%s" SET %s', $column, $this->formatDefault($attributes['default']));
            }
        }
        
        if (empty($parts)) {
            return '';
        }
        
        return sprintf(
            'ALTER TABLE "%s" %s',
            $table,
            implode(', ', $parts)
        );
    }
}
