<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Schema;

interface FormatterInterface
{
    public function formatColumn(string $name, array $attributes): string;

    public function formatIndex(array $index): string;

    public function formatTableOptions(): string;

    public function addRelationship(string $type, array $config): void;

    public function formatRelationships(string $table): string;

    public function resetRelationships(): void;

    /**
     * Format ALTER TABLE ADD COLUMN statement
     */
    public function formatAddColumn(string $table, string $column, array $attributes, ?string $after = null, bool $first = false): string;

    /**
     * Format ALTER TABLE DROP COLUMN statement
     */
    public function formatDropColumn(string $table, string $column): string;

    /**
     * Format ALTER TABLE RENAME COLUMN statement
     */
    public function formatRenameColumn(string $table, string $old, string $new): string;

    /**
     * Format ALTER TABLE MODIFY COLUMN statement
     */
    public function formatAlterColumn(string $table, string $column, array $attributes): string;
}
