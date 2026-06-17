<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

use DateTimeInterface;

class SqliteFormatter implements FormatterInterface
{
    public bool $skipForeignKeys = true;
    private array $relationships = [];

    public function formatColumn(string $name, array $attributes): string
    {
        $dbType = $this->formatType($attributes);

        $definition = [
            "\"$name\"",
            $dbType,
            $attributes['nullable'] ? '' : 'NOT NULL',
            $this->getPrimaryKeyClause($attributes),
            $attributes['unique'] ? 'UNIQUE' : '',
            isset($attributes['check']) ? "CHECK ({$attributes['check']})" : '',
            isset($attributes['default']) ?
                $this->formatDefault($attributes['default']) : ''
        ];

        return implode(' ', array_filter($definition));
    }

    private function formatType(array $attributes): string
    {
        $type = $attributes['type'];

        return match ($type) {
            'UUID' => 'TEXT',
            'STRING' => isset($attributes['length']) ? 'TEXT' : 'TEXT',
            'TEXT' => 'TEXT',
            'INTEGER' => 'INTEGER',
            'BOOLEAN' => 'INTEGER',
            'FLOAT' => 'REAL',
            'DECIMAL' => $this->formatDecimal($attributes),
            'DATE' => 'TEXT',
            'DATETIME' => 'TEXT',
            'TIMESTAMP' => 'DATETIME',
            'ENUM' => 'TEXT',
            'JSON' => 'JSON',
            'BLOB' => 'BLOB',
            'ARRAY' => 'TEXT',
            default => $type
        };
    }

    private function formatDecimal(array $attributes): string
    {
        // SQLite doesn't have native DECIMAL, use NUMERIC affinity
        return 'NUMERIC';
    }

    private function getPrimaryKeyClause(array $attributes): string
    {
        if (!$attributes['primary']) {
            return '';
        }

        $clause = 'PRIMARY KEY';

        if ($attributes['autoIncrement'] && $attributes['type'] === 'INT') {
            $clause .= ' AUTOINCREMENT';
        }

        return $clause;
    }

    private function formatDefault(mixed $value): string
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        if (is_bool($value)) {
            return 'DEFAULT ' . ($value ? 1 : 0);
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

        return implode(";\n", array_map(
            fn($rel) => match ($rel['type']) {
                'belongsTo' => $this->formatBelongsTo($table, $rel['config']),
                'hasOne' => $this->formatHasOneOrHasMany($rel['config']),
                'hasMany' => $this->formatHasOneOrHasMany($rel['config']),
                'manyToMany' => $this->formatManyToMany($rel['config']),
                default => ''
            },
            $this->relationships
        ));
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

    private function formatEnum(array $attributes): string
    {
        if ($attributes['type'] !== 'ENUM' || empty($attributes['enum'])) {
            return 'TEXT';
        }

        $values = array_map(fn($v) => "'$v'", $attributes['enum']);
        return 'TEXT CHECK ("' . implode('" OR "', $values) . '")';
    }

    public function formatAddColumn(string $table, string $column, array $attributes, ?string $after = null, bool $first = false): string
    {
        $columnDef = $this->formatColumn($column, $attributes);
        
        // SQLite doesn't support AFTER/FIRST, column is always added at the end
        return sprintf(
            'ALTER TABLE "%s" ADD COLUMN %s',
            $table,
            $columnDef
        );
    }

    public function formatDropColumn(string $table, string $column): string
    {
        // SQLite doesn't support DROP COLUMN directly - requires table recreation
        // Return empty string with a warning comment
        return "-- WARNING: SQLite doesn't support DROP COLUMN directly. Use table recreation instead.";
    }

    public function formatRenameColumn(string $table, string $old, string $new): string
    {
        // SQLite 3.25.0+ supports RENAME COLUMN
        return sprintf(
            'ALTER TABLE "%s" RENAME COLUMN "%s" TO "%s"',
            $table,
            $old,
            $new
        );
    }

    public function formatAlterColumn(string $table, string $column, array $attributes): string
    {
        // SQLite doesn't support ALTER COLUMN directly - requires table recreation
        return "-- WARNING: SQLite doesn't support ALTER COLUMN directly. Use table recreation instead.";
    }
}
